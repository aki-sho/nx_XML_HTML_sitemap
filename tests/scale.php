<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

use NexaPressXmlHtmlSitemap\Html\HtmlViewData;
use NexaPressXmlHtmlSitemap\Provider\SitemapProviderInterface;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapCache;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapEntry;

define('BASE_PATH', $argv[1]);
define('NX_XML_HTML_SITEMAP_PATH', dirname(__DIR__));
define('NX_XML_HTML_SITEMAP_ID', 'nx_XML_HTML_sitemap');
require NX_XML_HTML_SITEMAP_PATH . '/autoload.php';

function url(string $path = ''): string { return 'https://example.test/' . $path; }
function do_action(string $name, mixed ...$arguments): void
{
    if ($name === 'nx_sitemap_register_providers') {
        $arguments[0]->register(new class implements SitemapProviderInterface {
            public function key(): string { return 'home'; }
            public function entries(): iterable {
                for ($i = 1; $i <= 50001; $i++) {
                    yield new SitemapEntry(url('record/' . $i), '項目 ' . $i, 'post',
                        gmdate('c', 1700000000 + $i));
                }
            }
        });
    }
}

$start = microtime(true);
$settings = ['include_home' => true];
$stream = SitemapCache::open('xml', $settings);
$index = new DOMDocument();
$index->loadXML(stream_get_contents($stream));
fclose($stream);
$counts = [];

foreach ([1, 2] as $part) {
    $stream = SitemapCache::openPart($settings, (string)$part);
    $reader = new XMLReader();
    $reader->XML(stream_get_contents($stream));
    fclose($stream);
    $count = 0;

    while ($reader->read()) {
        if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'url') {
            $count++;
        }
    }

    $reader->close();
    $counts[] = $count;
}

$stream = SitemapCache::openHtml($settings, 'title');
$html = HtmlViewData::build(SitemapCache::readEntries($stream), ['per_page' => 50],
    ['sort' => 'title', 'page' => 1001]);
fclose($stream);

echo json_encode([
    'parts' => $index->getElementsByTagName('sitemap')->length,
    'counts' => $counts, 'html_total' => $html['pagination']['total'],
    'html_page_count' => count($html['groups']['post']),
    'last_title' => $html['groups']['post'][0]->title(),
    'peak_bytes' => memory_get_peak_usage(true), 'seconds' => microtime(true) - $start,
], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
