<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/bootstrap.php';

$scenario = json_decode(base64_decode($argv[3]), true, 16, JSON_THROW_ON_ERROR);
$pdo = sitemapTestApplication($argv[1], $argv[2]);
$pdo->prepare('UPDATE nx_test_users SET role = ? WHERE id = 1')->execute([
    $scenario['db_role'] ?? 'administrator',
]);

if (($scenario['logged_in'] ?? true) === true) {
    $_SESSION['user'] = ['id' => 1, 'role' => $scenario['session_role'] ?? 'administrator'];
}

$_POST = [
    'enabled' => '1', 'include_home' => '1', 'title' => '保存テスト',
    '_csrf_token' => $scenario['token'] ?? null,
];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = $scenario['path'] ?? '/subdir/admin/posts/save/1';
ob_start();

$cacheDirectory = BASE_PATH . '/storage/extensions/' . NX_XML_HTML_SITEMAP_ID . '/cache';
$stream = NexaPressXmlHtmlSitemap\Sitemap\SitemapCache::open('xml', ['include_home' => true]);
fclose($stream);
$beforeGeneration = is_file($cacheDirectory . '/generation')
    ? file_get_contents($cacheDirectory . '/generation') : '';

if ($scenario['action'] === 'watch') {
    require NX_XML_HTML_SITEMAP_PATH . '/hooks.php';
    http_response_code($scenario['status'] ?? 302);

    if (isset($scenario['event'])) {
        do_action($scenario['event'], 1);
    }
}

register_shutdown_function(static function () use ($cacheDirectory, $beforeGeneration): void {
    $settingsPath = BASE_PATH . '/storage/extensions/' . NX_XML_HTML_SITEMAP_ID . '/settings.json';
    $body = ob_get_clean();
    echo json_encode([
        'status' => http_response_code() ?: 200,
        'saved' => is_file($settingsPath) ? json_decode(file_get_contents($settingsPath), true) : null,
        'body' => $body,
        'invalidated' => $beforeGeneration !== (is_file($cacheDirectory . '/generation')
            ? file_get_contents($cacheDirectory . '/generation') : ''),
        'error' => error_get_last(),
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
});

if ($scenario['action'] === 'watch') {
    exit;
}

if ($scenario['action'] === 'dashboard') {
    require NX_XML_HTML_SITEMAP_PATH . '/admin/dashboard.php';
    exit;
}

$controller = new NexaPressXmlHtmlSitemap\Admin\SettingsController();
$method = $scenario['action'] === 'xml' ? 'saveXml' : 'saveHtml';
$controller->$method();
