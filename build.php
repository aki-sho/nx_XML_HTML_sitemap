<?php

/*
 * NX XML HTML Sitemapの配布用ZIPを作成（CLI専用）。
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit('404 Not Found');
}

$fail = static function (string $message): void {
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
};

$projectDirectory = realpath(__DIR__);
$manifestFile = $projectDirectory . '/manifest.json';

if (!class_exists(ZipArchive::class)) {
    $fail('ZipArchiveが使用できません。');
}

try {
    $json = @file_get_contents($manifestFile);

    if ($json === false) {
        throw new RuntimeException('manifest.jsonを読み込めませんでした。');
    }

    $manifest = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

    if (!is_array($manifest)) {
        throw new RuntimeException('manifest.jsonの形式が正しくありません。');
    }
} catch (Throwable $exception) {
    $fail($exception->getMessage());
}

$extensionId = (string)($manifest['id'] ?? '');
$version = (string)($manifest['version'] ?? '');

if (!preg_match('/^[a-zA-Z0-9_-]+$/D', $extensionId)) {
    $fail('拡張機能IDが正しくありません。');
}

if (!preg_match('/^\\d+\\.\\d+\\.\\d+$/D', $version)) {
    $fail('バージョンが正しくありません。');
}

$outputName = str_replace(
    '{version}',
    $version,
    (string)($manifest['update']['asset'] ?? $extensionId . '-{version}.zip')
);

if (!preg_match('/^[a-zA-Z0-9_.-]+\\.zip$/D', $outputName)) {
    $fail('ZIPファイル名が正しくありません。');
}

/*
 * 配布物の許可リスト。実行時に必要なファイルを追加した場合はここも更新する。
 * フォルダを再帰収録しないため、機密ファイル・ログ・テスト・バックアップは含まれない。
 */
$releaseFiles = [
    'README.md',
    'admin/dashboard.php',
    'admin/partials/content-switches.php',
    'admin/partials/advanced.php',
    'admin/partials/notice.php',
    'admin/sections/html.php',
    'admin/sections/xml.php',
    'assets/css/admin-base.css',
    'assets/css/admin-forms.css',
    'assets/css/admin-tabs.css',
    'assets/css/public-sitemap.css',
    'assets/js/admin-tabs.js',
    'autoload.php',
    'bootstrap.php',
    'config/admin-sections.php',
    'config/defaults.php',
    'hooks.php',
    'img.png',
    'manifest.json',
    'routes/html.php',
    'routes/settings.php',
    'routes/xml.php',
    'src/Admin/Notice.php',
    'src/Admin/SettingsController.php',
    'src/Controller/HtmlSitemapController.php',
    'src/Controller/XmlSitemapController.php',
    'src/Html/HtmlViewData.php',
    'src/Html/HtmlEntrySorter.php',
    'src/Provider/CategoryProvider.php',
    'src/Provider/HomeProvider.php',
    'src/Provider/PageProvider.php',
    'src/Provider/PostProvider.php',
    'src/Provider/ProviderRegistry.php',
    'src/Provider/SitemapProviderInterface.php',
    'src/Repository/CategoryRepository.php',
    'src/Repository/ContentRepository.php',
    'src/Repository/PageRepository.php',
    'src/Repository/PostRepository.php',
    'src/Settings/Settings.php',
    'src/Settings/SettingsValidator.php',
    'src/Sitemap/SitemapCache.php',
    'src/Sitemap/SitemapEntry.php',
    'src/Sitemap/SitemapService.php',
    'src/Support/DateFormatter.php',
    'src/Support/ExclusionPolicy.php',
    'src/Support/UrlBuilder.php',
    'src/Xml/XmlRenderer.php',
    'src/Xml/XmlSitemapBuilder.php',
    'src/Xml/XmlResponse.php',
    'views/html/sections/categories.php',
    'views/html/sections/home.php',
    'views/html/sections/pages.php',
    'views/html/sections/posts.php',
    'views/html/sitemap.php',
    'views/html/partials/tools.php',
    'views/html/partials/pagination.php',
];

$sources = [];

foreach ($releaseFiles as $relativePath) {
    $source = $projectDirectory . '/' . $relativePath;
    $resolved = realpath($source);

    if ($resolved === false || !is_file($resolved) || !is_readable($resolved)
        || is_link($source)
        || !str_starts_with($resolved, $projectDirectory . DIRECTORY_SEPARATOR)) {
        $fail('配布ファイルを安全に読み込めません：' . $relativePath);
    }

    $sources[$relativePath] = $resolved;
}

$outputDirectory = $projectDirectory . '/dist';

if (!is_dir($outputDirectory) && !@mkdir($outputDirectory, 0755, true)) {
    $fail('distフォルダを作成できません。');
}

if (is_link($outputDirectory)
    || realpath($outputDirectory) !== $projectDirectory . DIRECTORY_SEPARATOR . 'dist') {
    $fail('ZIPの出力先がプロジェクト外を指しています。');
}

$temporary = tempnam($outputDirectory, '.sitemap-');

if ($temporary === false) {
    $fail('ZIPの作業ファイルを作成できません。');
}

$zip = new ZipArchive();

try {
    if ($zip->open($temporary, ZipArchive::OVERWRITE) !== true) {
        throw new RuntimeException('ZIPファイルを作成できませんでした。');
    }

    foreach ($sources as $relativePath => $source) {
        if (!$zip->addFile($source, $extensionId . '/' . $relativePath)) {
            throw new RuntimeException('ZIPへの追加に失敗しました：' . $relativePath);
        }
    }

    if (!$zip->close()) {
        throw new RuntimeException('ZIPファイルを保存できませんでした。');
    }

    $outputFile = $outputDirectory . '/' . $outputName;

    if (!@rename($temporary, $outputFile)) {
        throw new RuntimeException('配布用ZIPを配置できませんでした。');
    }
} catch (Throwable $exception) {
    if (is_file($temporary)) {
        @unlink($temporary);
    }

    $fail($exception->getMessage());
}

echo 'ZIPを作成しました。' . PHP_EOL;
echo $outputFile . PHP_EOL;
