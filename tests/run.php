<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/bootstrap.php';

use NexaPressXmlHtmlSitemap\Controller\HtmlSitemapController;
use NexaPressXmlHtmlSitemap\Controller\XmlSitemapController;
use NexaPressXmlHtmlSitemap\Html\HtmlViewData;
use NexaPressXmlHtmlSitemap\Provider\ProviderRegistry;
use NexaPressXmlHtmlSitemap\Provider\SitemapProviderInterface;
use NexaPressXmlHtmlSitemap\Repository\ContentRepository;
use NexaPressXmlHtmlSitemap\Settings\Settings;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapCache;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapEntry;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapService;
use NexaPressXmlHtmlSitemap\Xml\XmlRenderer;

$core = realpath($argv[1] ?? dirname(__DIR__, 2) . '/nexapress');

if ($core === false || !is_file($core . '/app/Core/Csrf.php')) {
    fwrite(STDERR, 'Usage: php tests/run.php /path/to/nexapress' . PHP_EOL);
    exit(1);
}

$runtime = sys_get_temp_dir() . '/nx-sitemap-tests-' . bin2hex(random_bytes(8));
mkdir($runtime, 0755, true);
$checks = 0;
$exitCode = 0;

function check(bool $condition, string $label): void
{
    global $checks;

    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $label);
    }

    $checks++;
    echo 'PASS: ' . $label . PHP_EOL;
}

function expectFailure(callable $callback, string $class, string $label): void
{
    try {
        $callback();
    } catch (Throwable $exception) {
        check($exception instanceof $class, $label);
        return;
    }

    check(false, $label);
}

function requestScenario(array $scenario): array
{
    global $core, $runtime;
    $directory = $runtime . '/request-' . bin2hex(random_bytes(4));
    $result = sitemapTestProcess(sitemapTestPhpCommand(__DIR__ . '/request.php', [
        $core, $directory, base64_encode(json_encode($scenario, JSON_THROW_ON_ERROR)),
    ]));

    if ($result['status'] !== 0) {
        throw new RuntimeException($result['errors'] . $result['output']);
    }

    return json_decode($result['output'], true, 32, JSON_THROW_ON_ERROR);
}

// 一時テストディレクトリだけを削除し、リンク先を再帰しない。
function removeTestTree(string $path, string $root): void
{
    $resolved = realpath($path);

    if (is_link($path)) {
        unlink($path);
        return;
    }

    if ($resolved === false || ($resolved !== $root
        && !str_starts_with($resolved, $root . DIRECTORY_SEPARATOR))) {
        throw new RuntimeException('Refusing cleanup outside test directory.');
    }

    if (is_dir($path)) {
        foreach (new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS) as $file) {
            removeTestTree($file->getPathname(), $root);
        }

        rmdir($path);
    } else {
        unlink($path);
    }
}

ob_start();

try {
    $pdo = sitemapTestApplication($core, $runtime);
    $validToken = str_repeat('a', 64);

    foreach (['xml', 'html'] as $action) {
        foreach ([null, '', 'wrong-token', ['unexpected' => 'array']] as $token) {
            $result = requestScenario(['action' => $action, 'token' => $token]);
            check($result['status'] === 419 && $result['saved'] === null
                && !$result['invalidated'], $action . ': invalid CSRF never saves or invalidates');
        }

        $result = requestScenario(['action' => $action, 'token' => $validToken, 'logged_in' => false]);
        check($result['status'] === 302 && $result['saved'] === null, $action . ': login required');

        foreach (['editor', 'author', 'contributor', 'viewer'] as $role) {
            $result = requestScenario([
                'action' => $action, 'token' => $validToken,
                'db_role' => $role, 'session_role' => 'administrator',
            ]);
            check($result['status'] === 403 && $result['saved'] === null
                && !$result['invalidated'], $action . ': current DB role blocks ' . $role);
        }

        $result = requestScenario(['action' => $action, 'token' => $validToken]);
        check($result['status'] === 302 && $result['saved'][$action]['enabled'] === true
            && $result['invalidated'], $action . ': administrator saves and invalidates');
    }

    $result = requestScenario(['action' => 'dashboard', 'db_role' => 'viewer']);
    check($result['status'] === 403, 'dashboard rejects insufficient permission');

    foreach (['xml', 'html'] as $section) {
        ob_start();
        $sectionSettings = [];
        require NX_XML_HTML_SITEMAP_PATH . '/admin/sections/' . $section . '.php';
        $document = new DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">' . ob_get_clean());
        $nodes = (new DOMXPath($document))->query('//form/input[@name="_csrf_token"]');
        check($nodes->length === 1 && $nodes->item(0)->getAttribute('value') === $validToken,
            $section . ': form emits actual core CSRF token');
    }

    foreach ([
        ['path' => '/subdir/admin/posts/save/1', 'expected' => true],
        ['path' => '/subdir/admin/pages/status/1', 'expected' => true],
        ['path' => '/subdir/admin/categories/save/1', 'expected' => true],
        ['path' => '/subdir/admin/settings/general/save', 'expected' => true],
        ['path' => '/subdir/admin/posts/save/1', 'status' => 403, 'expected' => false],
        ['path' => '/subdir/admin/posts/save/1', 'token' => 'bad', 'expected' => false],
        ['path' => '/subdir/admin/posts/save/1', 'logged_in' => false, 'expected' => false],
        ['path' => '/subdir/admin/posts-lookalike/save/1', 'expected' => false],
        ['path' => '/unrelated', 'event' => 'page_trashed', 'expected' => true],
    ] as $scenario) {
        $result = requestScenario(array_merge(['action' => 'watch', 'token' => $validToken], $scenario));
        check($result['invalidated'] === $scenario['expected'], 'mutation invalidation: '
            . $scenario['path'] . ' / ' . ($scenario['event'] ?? ($scenario['status'] ?? 302)));
    }

    $pdo->beginTransaction();

    foreach (['posts' => 1105, 'pages' => 1102] as $type => $total) {
        $insert = $pdo->prepare('INSERT INTO nx_test_' . $type . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

        for ($id = 1; $id <= $total; $id++) {
            $date = $id <= 550 ? null : '2026-10-' . str_pad((string)(1 + $id % 3), 2, '0', STR_PAD_LEFT);
            $insert->execute([$id, 'ページ & <' . $id . '>', $type . '-' . $id,
                'published', '本文は取得しない', $date, '2026-01-01', null, null, 1]);
        }

        $insert->execute([$total + 1, 'Draft', 'draft', 'draft', 'secret', null, null, null, null, 1]);
        $insert->execute([$total + 2, 'Trashed', 'trashed', 'published', 'secret', null, null, null, '2026-10-30', 1]);
    }

    $pdo->commit();

    foreach (['posts' => 1105, 'pages' => 1102] as $type => $total) {
        $before = count($pdo->queries);
        $records = iterator_to_array(ContentRepository::published($type), false);
        $expected = $pdo->query('SELECT id FROM nx_test_' . $type
            . " WHERE status = 'published' AND deleted_at IS NULL ORDER BY published_at DESC, id DESC")
            ->fetchAll(PDO::FETCH_COLUMN);
        check(count($records) === $total && array_column($records, 'id') === $expected,
            $type . ': all batches retain date/ID order including NULL dates');
        check(count($pdo->queries) - $before === 3 && !array_key_exists('content', $records[0]),
            $type . ': bounded queries omit content');

        if ($type === 'posts') {
            check($records[0]['category_slug'] === 'news', 'category permalink data retained');
        }
    }

    file_put_contents(BASE_PATH . '/config/url.php', "<?php return ['post_url_type' => 'category_slug'];");
    $post = iterator_to_array(ContentRepository::published('posts'), false)[0];
    check(str_contains(post_url($post), '/news/posts-'), 'core category permalink works');

    $renderer = new XmlRenderer();
    $url = 'https://example.test/日本語?a=1&b=<value>"';
    $document = new DOMDocument();
    check($document->loadXML($renderer->render([
        new SitemapEntry($url, 'Title', 'post', '2026-10-31T00:00:00+09:00'),
        new SitemapEntry("https://example.test/control\x01", 'Title', 'page'),
    ])), 'stream renderer emits valid escaped XML and replaces illegal controls');
    check($document->getElementsByTagName('loc')->item(0)->textContent === $url,
        'XML URLs survive escaping');

    $settings = Settings::section('xml');
    $stream = SitemapCache::open('xml', $settings);
    $xml = stream_get_contents($stream);
    fclose($stream);
    $document->loadXML($xml);
    check($document->getElementsByTagName('url')->length === 2208, 'cached XML contains public content only');
    $before = count($pdo->queries);
    $stream = SitemapCache::open('xml', $settings);
    check(stream_get_contents($stream) === $xml && count($pdo->queries) === $before,
        'warm XML cache performs no content queries');
    fclose($stream);

    ob_start();
    (new XmlSitemapController())->index();
    $publicXml = ob_get_clean();
    check($publicXml === $xml && !str_contains($publicXml, '"expires"'),
        'public XML endpoint streams only the cached XML payload');

    $stream = SitemapCache::open('html', Settings::section('html'));
    $data = HtmlViewData::build(SitemapCache::readEntries($stream), Settings::section('html'));
    fclose($stream);
    check($data['pagination']['total'] === 2208 && array_sum(array_map('count', $data['groups'])) === 50,
        'HTML entry cache paginates all public groups');
    check(str_starts_with($data['groups']['post'][0]->title(), 'ページ & <'), 'HTML cache retains Unicode titles');

    $before = count($pdo->queries);
    ob_start();
    (new HtmlSitemapController())->index();
    $anonymousHtml = ob_get_clean();
    $_SESSION['user'] = ['id' => 1, 'role' => 'administrator'];
    ob_start();
    (new HtmlSitemapController())->index();
    $adminHtml = ob_get_clean();
    unset($_SESSION['user']);
    check(!str_contains($anonymousHtml, 'admin-preview-header')
        && str_contains($adminHtml, 'admin-preview-header') && count($pdo->queries) === $before,
        'warm HTML cache renders the current user header separately');
    check(str_contains($anonymousHtml, e($data['groups']['post'][0]->title())), 'public HTML escapes cached titles');

    $stream = SitemapCache::open('xml', ['include_home' => true]);
    $document->loadXML(stream_get_contents($stream));
    fclose($stream);
    check($document->getElementsByTagName('url')->length === 1, 'settings fingerprint prevents cache mix-ups');

    $cacheDirectory = BASE_PATH . '/storage/extensions/' . NX_XML_HTML_SITEMAP_ID . '/cache';
    $cachePath = $cacheDirectory . '/xml.cache';
    $stream = SitemapCache::open('xml', $settings);
    fclose($stream);
    $before = count($pdo->queries);
    $cached = file_get_contents($cachePath);
    [$header, $payload] = explode("\n", $cached, 2);
    $meta = json_decode($header, true);
    $meta['expires'] = time() - 1;
    file_put_contents($cachePath, json_encode($meta) . "\n" . $payload);
    $stream = SitemapCache::open('xml', $settings);
    fclose($stream);
    $renewedMeta = json_decode(strtok(file_get_contents($cachePath), "\n"), true);
    check(count($pdo->queries) > $before && $renewedMeta['expires'] > time(), 'expired cache regenerates');

    SitemapCache::invalidate();
    $lock = fopen($cacheDirectory . '/generation.lock', 'c');
    flock($lock, LOCK_EX);
    $before = count($pdo->queries);
    $start = microtime(true);
    expectFailure(static fn() => SitemapCache::open('xml', $settings), RuntimeException::class,
        'concurrent regeneration is rejected');
    check(microtime(true) - $start < 1 && count($pdo->queries) === $before,
        'busy generation neither waits nor queries content');

    foreach ([new XmlSitemapController(), new HtmlSitemapController()] as $controller) {
        http_response_code(200);
        ob_start();
        $controller->index();
        $body = ob_get_clean();
        check(http_response_code() === 503 && !str_contains($body, '<?xml'),
            'busy public endpoint returns 503 without partial data');
    }

    flock($lock, LOCK_UN);
    fclose($lock);
    http_response_code(200);
    $stream = SitemapCache::open('xml', $settings);
    fclose($stream);

    $beforeCache = hash_file('sha256', $cachePath);
    SitemapCache::invalidate();
    $pdo->exec('ALTER TABLE nx_test_pages RENAME TO missing_pages');
    expectFailure(static fn() => SitemapCache::open('xml', $settings), PDOException::class,
        'provider failure does not publish an incomplete sitemap');
    check(hash_file('sha256', $cachePath) === $beforeCache
        && glob($cacheDirectory . '/.pending-*') === [], 'failed cache generation cleans temporary data');
    $pdo->exec('ALTER TABLE missing_pages RENAME TO nx_test_pages');

    $race = false;
    add_action('nx_sitemap_register_providers', static function ($registry) use (&$race): void {
        if ($race) {
            $registry->register(new class implements SitemapProviderInterface {
                public function key(): string { return 'home'; }
                public function entries(): iterable {
                    yield new SitemapEntry('https://example.test/old', 'Old', 'home');
                    SitemapCache::invalidate();
                }
            });
        }
    });
    $race = true;
    expectFailure(static fn() => SitemapCache::open('xml', ['include_home' => true]), RuntimeException::class,
        'invalidation during generation prevents stale publication');
    $race = false;

    require __DIR__ . '/features.php';

    foreach (['xml' => new XmlSitemapController(), 'html' => new HtmlSitemapController()] as $format => $controller) {
        Settings::saveSection($format, ['enabled' => false]);
        http_response_code(200);
        ob_start();
        $controller->index();
        $body = ob_get_clean();
        check(http_response_code() === 404 && $body === '404 Not Found',
            $format . ': disabled sitemap is never served from cache');
    }
    http_response_code(200);

    $registry = new ProviderRegistry();
    $registry->register(new class implements SitemapProviderInterface {
        public function key(): string { return 'home'; }
        public function entries(): array {
            return [new SitemapEntry('https://example.test/', 'Home', 'home')];
        }
    });
    check(count((new SitemapService($registry))->collect(['include_home' => true])) === 1,
        'array-returning extension providers remain compatible');

    $registry->register(new class implements SitemapProviderInterface {
        public function key(): string { return 'home'; }
        public function entries(): iterable {
            for ($i = 0; $i <= SitemapService::MAX_ENTRIES; $i++) {
                yield new SitemapEntry('https://example.test/' . $i, 'Page', 'home');
            }
        }
    });
    expectFailure(static fn() => (new SitemapService($registry))->collect(['include_home' => true]),
        LengthException::class, 'entry count has a hard upper bound');

    $stream = fopen('php://temp/maxmemory:2097152', 'w+b');
    $oversized = static function (): iterable {
        $url = 'https://example.test/' . str_repeat('a', 2500);
        for ($i = 0; $i < 22000; $i++) {
            yield new SitemapEntry($url . $i, 'Page', 'post');
        }
    };
    expectFailure(static fn() => $renderer->write($oversized(), $stream), LengthException::class,
        'XML byte size has a hard upper bound');
    check(ftell($stream) <= XmlRenderer::MAX_BYTES, 'oversized XML is stopped before excess bytes are written');
    fclose($stream);

    $command = sitemapTestPhpCommand(__DIR__ . '/memory.php');
    array_splice($command, count($command) - 1, 0, ['-d', 'memory_limit=32M']);
    $result = sitemapTestProcess($command);
    $memory = json_decode($result['output'], true);
    check($result['status'] === 0 && ($memory['entries'] ?? 0) === 50000
        && $memory['peak_bytes'] < 33554432, '50000 URLs stream within a 32 MiB memory limit');
    echo 'Streaming peak: ' . $memory['peak_bytes'] . ' bytes; output: '
        . $memory['bytes'] . ' bytes.' . PHP_EOL;

    $command = sitemapTestPhpCommand(__DIR__ . '/scale.php', [$runtime . '/scale-case']);
    array_splice($command, count($command) - 2, 0, ['-d', 'memory_limit=32M']);
    $result = sitemapTestProcess($command);
    $scale = json_decode($result['output'], true);
    check($result['status'] === 0 && ($scale['parts'] ?? 0) === 2 && $scale['counts'] === [50000, 1],
        '50001 URLs automatically publish a complete XML index and two parts');
    check($scale['html_total'] === 50001 && $scale['html_page_count'] === 1
        && $scale['last_title'] === '項目 50001' && $scale['peak_bytes'] < 33554432,
        '50001-entry HTML sorting and pagination complete within 32 MiB');
    echo 'Split/cache/search peak: ' . $scale['peak_bytes'] . ' bytes.' . PHP_EOL;

    $sandbox = $runtime . '/build-project';
    mkdir($sandbox);
    $buildSource = file_get_contents(NX_XML_HTML_SITEMAP_PATH . '/build.php');
    preg_match('/\$releaseFiles\s*=\s*\[(.*?)\];/s', $buildSource, $matches);
    preg_match_all("/'([^']+)'/", $matches[1], $matches);
    $releaseFiles = $matches[1];

    foreach (array_merge($releaseFiles, ['build.php']) as $relative) {
        if (!is_dir(dirname($sandbox . '/' . $relative))) {
            mkdir(dirname($sandbox . '/' . $relative), 0755, true);
        }
        copy(NX_XML_HTML_SITEMAP_PATH . '/' . $relative, $sandbox . '/' . $relative);
    }

    foreach (['.env', '.env.php', 'src/.env', 'src/credentials.php', 'config/settings.json',
        'assets/js/admin-tabs.js.bak', 'README.md.bak', 'debug.log'] as $secret) {
        file_put_contents($sandbox . '/' . $secret, 'DUMMY_SECRET_DO_NOT_DISTRIBUTE');
    }

    $result = sitemapTestProcess(sitemapTestPhpCommand($sandbox . '/build.php'));
    check($result['status'] === 0, 'release ZIP builds successfully');
    $manifest = json_decode(file_get_contents($sandbox . '/manifest.json'), true);
    $zipPath = $sandbox . '/dist/' . str_replace('{version}', $manifest['version'], $manifest['update']['asset']);
    $zip = new ZipArchive();
    $zip->open($zipPath);
    $names = [];
    $containsSecret = false;

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $names[] = $zip->getNameIndex($i);
        $containsSecret = $containsSecret || str_contains($zip->getFromIndex($i), 'DUMMY_SECRET_DO_NOT_DISTRIBUTE');
    }

    $zip->close();
    $expectedNames = array_map(static fn($file) => $manifest['id'] . '/' . $file, $releaseFiles);
    sort($names);
    sort($expectedNames);
    check($names === $expectedNames && !$containsSecret, 'ZIP excludes secrets, backups and unlisted PHP files');

    $zipHash = hash_file('sha256', $zipPath);
    unlink($sandbox . '/src/Sitemap/SitemapCache.php');
    $result = sitemapTestProcess(sitemapTestPhpCommand($sandbox . '/build.php'));
    check($result['status'] !== 0 && hash_file('sha256', $zipPath) === $zipHash,
        'missing release file fails without replacing an existing ZIP');

    echo $checks . ' checks passed.' . PHP_EOL;
    $output = ob_get_clean();
    fwrite(STDOUT, $output);
} catch (Throwable $exception) {
    fwrite(STDERR, ob_get_clean() . $exception . PHP_EOL);
    $exitCode = 1;
} finally {
    $resolved = realpath($runtime);
    if ($resolved !== false && dirname($resolved) === realpath(sys_get_temp_dir())
        && preg_match('/^nx-sitemap-tests-[a-f0-9]{16}$/D', basename($resolved))) {
        removeTestTree($runtime, $resolved);
    }
}

exit($exitCode);
