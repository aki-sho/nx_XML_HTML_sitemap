<?php

use NexaPressXmlHtmlSitemap\Controller\HtmlSitemapController;
use NexaPressXmlHtmlSitemap\Controller\XmlSitemapController;
use NexaPressXmlHtmlSitemap\Html\HtmlEntrySorter;
use NexaPressXmlHtmlSitemap\Html\HtmlViewData;
use NexaPressXmlHtmlSitemap\Provider\ProviderRegistry;
use NexaPressXmlHtmlSitemap\Settings\Settings;
use NexaPressXmlHtmlSitemap\Settings\SettingsValidator;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapCache;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapEntry;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapService;
use NexaPressXmlHtmlSitemap\Support\ExclusionPolicy;
use NexaPressXmlHtmlSitemap\Xml\XmlRenderer;
use NexaPressXmlHtmlSitemap\Xml\XmlSitemapBuilder;

if (PHP_SAPI !== 'cli' || !defined('BASE_PATH')) {
    http_response_code(404);
    exit;
}

$originalSettings = Settings::all();
$parsed = SettingsValidator::xml([
    'excluded_post_ids' => "3, 1\n2、3", 'excluded_page_ids' => '4',
    'excluded_urls' => url('news/posts-3') . "\n/subdir/page/pages-4\n" . url('news/posts-3'),
    'cache_ttl' => '1', 'urls_per_file' => '99999',
]);
check($parsed['excluded_post_ids'] === [1, 2, 3] && count($parsed['excluded_urls']) === 2
    && $parsed['cache_ttl'] === 60 && $parsed['urls_per_file'] === 50000,
    'settings normalize and bound exclusion lists and generation limits');

foreach ([['excluded_post_ids' => '-1'], ['excluded_page_ids' => ['1']],
    ['excluded_urls' => 'javascript:alert(1)'], ['excluded_urls' => '//foreign.test/path'],
    ['excluded_urls' => "https://example.test/\x00"],
    ['excluded_post_ids' => implode(',', range(1, 1001))]] as $invalid) {
    expectFailure(static fn() => SettingsValidator::xml($invalid), InvalidArgumentException::class,
        'invalid exclusion input is rejected');
}
expectFailure(static fn() => SettingsValidator::html(['title' => ['bad']]), InvalidArgumentException::class,
    'array title is rejected without conversion warnings');

$excluded = array_replace($originalSettings['xml'], [
    'excluded_post_ids' => [1], 'excluded_page_ids' => [2],
    'excluded_urls' => [url('news/posts-3'), '/subdir/page/pages-4'], 'cache_ttl' => 60,
]);
Settings::saveSection('xml', $excluded);
check(Settings::section('xml')['excluded_post_ids'] === [1]
    && Settings::section('html')['excluded_post_ids'] === [], 'XML and HTML exclusion settings persist independently');

foreach (['posts', 'pages'] as $type) {
    $pdo->exec('ALTER TABLE nx_test_' . $type . " ADD COLUMN noindex TEXT DEFAULT '0'");
}
$pdo->exec("UPDATE nx_test_posts SET noindex = 'YES' WHERE id = 5");
$pdo->exec("UPDATE nx_test_pages SET noindex = '1' WHERE id = 6");
$entries = (new SitemapService(ProviderRegistry::createDefault($excluded)))->collect($excluded);
$urls = array_map(static fn($entry) => $entry->url(), $entries);
check(count($urls) === 2202 && !in_array(url('news/posts-1'), $urls, true)
    && !in_array(url('page/pages-2'), $urls, true) && !in_array(url('news/posts-3'), $urls, true)
    && !in_array(url('page/pages-4'), $urls, true) && !in_array(url('news/posts-5'), $urls, true)
    && !in_array(url('page/pages-6'), $urls, true), 'IDs, full URLs, paths and noindex are excluded');
check(in_array(url('page/pages-1'), $urls, true) && in_array(url('news/posts-2'), $urls, true),
    'post/page ID exclusions do not affect the other content type');
check(!ExclusionPolicy::allowsUrl(url('news/posts-3') . '#section', ExclusionPolicy::urlRules($excluded)),
    'URL fragments do not bypass exclusions');

$stream = SitemapCache::open('xml', $excluded);
fclose($stream);
$meta = json_decode(strtok(file_get_contents($cacheDirectory . '/xml.cache'), "\n"), true);
check($meta['expires'] - time() >= 58 && $meta['expires'] - time() <= 60, 'configured XML cache lifetime is honored');
$htmlSettings = array_replace($originalSettings['html'], ['cache_ttl' => 3600]);
$stream = SitemapCache::open('html', $htmlSettings);
$htmlUrls = [];
foreach (SitemapCache::readEntries($stream) as $entry) { $htmlUrls[] = $entry->url(); }
fclose($stream);
check(in_array(url('news/posts-1'), $htmlUrls, true) && !in_array(url('news/posts-5'), $htmlUrls, true),
    'HTML respects noindex while retaining XML-only exclusions');
$meta = json_decode(strtok(file_get_contents($cacheDirectory . '/html.cache'), "\n"), true);
check($meta['expires'] - time() >= 3598, 'configured HTML cache lifetime is honored');

foreach (['posts', 'pages'] as $type) {
    $pdo->exec('UPDATE nx_test_' . $type . " SET noindex = '0'");
}

$splitSettings = array_replace($originalSettings['xml'], ['urls_per_file' => 700]);
Settings::saveSection('xml', $splitSettings);
$stream = SitemapCache::open('xml', $splitSettings);
$index = new DOMDocument();
$index->loadXML(stream_get_contents($stream));
fclose($stream);
check($index->documentElement->localName === 'sitemapindex'
    && $index->getElementsByTagName('sitemap')->length === 4
    && $index->getElementsByTagName('loc')->item(0)->textContent === url('sitemap-1.xml'),
    'XML index references all split files at stable public URLs');
$allUrls = [];
for ($part = 1; $part <= 4; $part++) {
    $stream = SitemapCache::openPart($splitSettings, (string)$part);
    $partXml = stream_get_contents($stream);
    fclose($stream);
    $document = new DOMDocument();
    $document->loadXML($partXml);
    check($document->getElementsByTagName('url')->length <= 700 && strlen($partXml) <= XmlRenderer::MAX_BYTES,
        'XML part respects count and byte limits: ' . $part);
    foreach ($document->getElementsByTagName('loc') as $location) { $allUrls[] = $location->textContent; }
}
check(count($allUrls) === 2208 && count(array_unique($allUrls)) === 2208,
    'split XML has no missing or duplicated URLs');

foreach (['0', '01', '5', '50001', '../settings.json'] as $invalid) {
    $before = count($pdo->queries);
    ob_start();
    (new XmlSitemapController())->part($invalid);
    $body = ob_get_clean();
    check(http_response_code() === 404 && $body === '404 Not Found' && count($pdo->queries) === $before,
        'invalid or nonexistent XML part returns 404: ' . $invalid);
    http_response_code(200);
}
ob_start();
(new XmlSitemapController())->part('4');
$partBody = ob_get_clean();
check(str_contains($partBody, '<urlset') && !str_contains($partBody, '"bundle"'),
    'public part endpoint emits only XML');

$smallDirectory = BASE_PATH . '/builder-tests';
mkdir($smallDirectory);
$small = [];
for ($i = 0; $i < 5; $i++) { $small[] = new SitemapEntry('https://example.test/p' . $i . '?a=1&b=2', 'Page', 'post'); }
$byteBoundary = strlen(XmlRenderer::HEADER . XmlRenderer::FOOTER) + 2 * strlen((new XmlRenderer())->entryXml($small[0]));
$files = (new XmlSitemapBuilder(50000, $byteBoundary))->build($small, $smallDirectory, bin2hex(random_bytes(16)));
check(count($files) === 3 && max(array_map('filesize', $files)) <= $byteBoundary,
    'byte boundary automatically splits XML before exceeding the limit');
$files = (new XmlSitemapBuilder(2))->build(array_slice($small, 0, 4), $smallDirectory, bin2hex(random_bytes(16)));
check(count($files) === 2, 'an exact URL-count boundary does not add an empty part');
$files = (new XmlSitemapBuilder())->build([], $smallDirectory, bin2hex(random_bytes(16)));
check(count($files) === 1 && str_contains(file_get_contents($files[0]), '<urlset'), 'empty XML remains valid');
$before = glob($smallDirectory . '/*.xml');
expectFailure(static fn() => (new XmlSitemapBuilder(2, $byteBoundary))->build([
    new SitemapEntry('https://example.test/' . str_repeat('x', 1000), '', 'post'),
], $smallDirectory, bin2hex(random_bytes(16))), LengthException::class, 'oversized single entry fails safely');
check(glob($smallDirectory . '/*.xml') === $before, 'failed XML splitting leaves no partial files');

$sortedDirectory = BASE_PATH . '/sort-tests';
mkdir($sortedDirectory);
$sample = static function (): iterable {
    for ($i = 17003; $i >= 1; $i--) {
        yield new SitemapEntry('https://example.test/item/' . $i, '項目 ' . $i, 'post',
            gmdate('c', 1700000000 + $i));
    }
};
$sorted = (new HtmlEntrySorter())->build($sample(), $sortedDirectory, bin2hex(random_bytes(16)));
foreach (['title' => 1, 'updated' => 17003] as $sort => $expected) {
    $stream = fopen($sorted[$sort], 'rb');
    $count = 0;
    $ordered = true;
    foreach (SitemapCache::readEntries($stream) as $entry) {
        $ordered = $ordered && $entry->title() === '項目 ' . $expected;
        $expected += $sort === 'title' ? 1 : -1;
        $count++;
    }
    fclose($stream);
    check($ordered && $count === 17003, 'multi-pass external sorting is complete and ordered: ' . $sort);
}
check(glob($sortedDirectory . '/*.run') === [], 'external sorting cleans merge files');

$stream = fopen($sorted['title'], 'rb');
$filtered = HtmlViewData::build(SitemapCache::readEntries($stream), ['per_page' => 10],
    ['q' => '項目 17', 'sort' => 'title', 'page' => '2']);
fclose($stream);
check($filtered['pagination']['total'] > 10 && $filtered['pagination']['from'] === 11
    && count($filtered['groups']['post']) === 10 && $filtered['groups']['post'][0]->title() === '項目 179',
    'Unicode search, natural name sort and page offsets compose correctly');
parse_str((string)parse_url($filtered['pagination']['next'], PHP_URL_QUERY), $parameters);
check($parameters === ['sort' => 'title', 'page' => '3', 'q' => '項目 17'],
    'pagination retains search and sort parameters');
$stream = fopen($sorted['title'], 'rb');
$last = HtmlViewData::build(SitemapCache::readEntries($stream), ['per_page' => 10], ['page' => 250000]);
fclose($stream);
check($last['pagination']['page'] === 1701 && count($last['groups']['post']) === 3
    && $last['groups']['post'][2]->title() === '項目 17003', 'out-of-range HTML page clamps to the last page');
$empty = HtmlViewData::build($small, ['per_page' => 10], ['q' => 'no such title']);
check($empty['pagination']['total'] === 0 && $empty['pagination']['from'] === 0
    && $empty['pagination']['pages'] === 1, 'empty search has valid pagination');
check(HtmlViewData::query(['q' => [], 'sort' => [], 'page' => []]) === ['q' => '', 'sort' => 'updated', 'page' => 1],
    'malformed public query parameters are bounded and normalized');

Settings::saveSection('html', $originalSettings['html']);
$_GET = ['q' => '"><script>alert(1)</script>', 'sort' => 'title'];
ob_start();
(new HtmlSitemapController())->index();
$html = ob_get_clean();
$_GET = [];
check(!str_contains($html, '<script>alert(1)</script>') && str_contains($html, '該当するページがありません。'),
    'public search escapes reflected input and displays empty results');
$before = count($pdo->queries);
$_GET = ['q' => 'ページ', 'sort' => 'title', 'page' => '2'];
ob_start();
(new HtmlSitemapController())->index();
$html = ob_get_clean();
$_GET = [];
check(count($pdo->queries) === $before && str_contains($html, 'aria-current="page"')
    && str_contains($html, 'rel="prev"') && str_contains($html, 'rel="next"'),
    'filtered HTML uses cached sort data and renders accessible navigation');

foreach ($originalSettings as $format => $values) { Settings::saveSection($format, $values); }
