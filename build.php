<?php

/*
 * NX XML HTML Sitemapの配布用ZIPを作成
 */

$projectDirectory = __DIR__;

$manifestFile =
    $projectDirectory . '/manifest.json';

/*
 * 必須環境を確認
 */
if (!is_file($manifestFile)) {
    exit(
        "manifest.jsonが見つかりません。\n"
    );
}

if (!class_exists(ZipArchive::class)) {
    exit(
        "ZipArchiveが使用できません。\n"
    );
}

/*
 * manifest.jsonを読み込む
 */
try {
    $manifest = json_decode(
        file_get_contents($manifestFile),
        true,
        512,
        JSON_THROW_ON_ERROR
    );
} catch (JsonException) {
    exit(
        "manifest.jsonの形式が正しくありません。\n"
    );
}

if (!is_array($manifest)) {
    exit(
        "manifest.jsonを読み込めませんでした。\n"
    );
}

/*
 * 拡張機能IDを確認
 */
$extensionId = trim(
    (string)($manifest['id'] ?? '')
);

if (
    !preg_match(
        '/^[a-zA-Z0-9_-]+$/',
        $extensionId
    )
) {
    exit(
        "拡張機能IDが正しくありません。\n"
    );
}

/*
 * バージョンを確認
 */
$version = trim(
    (string)($manifest['version'] ?? '')
);

if (
    !preg_match(
        '/^\d+\.\d+\.\d+$/',
        $version
    )
) {
    exit(
        "バージョンが正しくありません。\n"
    );
}

/*
 * ZIPファイル名を作成
 */
$assetPattern = (string)(
    $manifest['update']['asset']
    ?? $extensionId . '-{version}.zip'
);

$outputName = str_replace(
    '{version}',
    $version,
    $assetPattern
);

if (
    basename($outputName) !== $outputName ||
    strtolower(
        pathinfo(
            $outputName,
            PATHINFO_EXTENSION
        )
    ) !== 'zip'
) {
    exit(
        "ZIPファイル名が正しくありません。\n"
    );
}

/*
 * 出力先を準備
 */
$outputDirectory =
    $projectDirectory . '/dist';

$outputFile =
    $outputDirectory . '/' . $outputName;

if (
    !is_dir($outputDirectory) &&
    !mkdir(
        $outputDirectory,
        0755,
        true
    )
) {
    exit(
        "distフォルダを作成できません。\n"
    );
}

if (is_file($outputFile)) {
    unlink($outputFile);
}

/*
 * ZIPを作成
 */
$zip = new ZipArchive();

$result = $zip->open(
    $outputFile,
    ZipArchive::CREATE |
    ZipArchive::OVERWRITE
);

if ($result !== true) {
    exit(
        "ZIPファイルを作成できませんでした。\n"
    );
}

/*
 * ZIPへ含めない項目
 */
$excludedPaths = [
    '.git',
    '.github',
    '.idea',
    '.vscode',
    'dist',
    'build.php',
    '.gitignore',
];

/*
 * プロジェクト内のファイルを追加
 */
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(
        $projectDirectory,
        FilesystemIterator::SKIP_DOTS
    ),
    RecursiveIteratorIterator::LEAVES_ONLY
);

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $relativePath = substr(
        $file->getPathname(),
        strlen($projectDirectory) + 1
    );

    $relativePath = str_replace(
        DIRECTORY_SEPARATOR,
        '/',
        $relativePath
    );

    $topDirectory = explode(
        '/',
        $relativePath
    )[0];

    if (
        in_array(
            $topDirectory,
            $excludedPaths,
            true
        ) ||
        in_array(
            $relativePath,
            $excludedPaths,
            true
        )
    ) {
        continue;
    }

    $zipPath =
        $extensionId
        . '/'
        . $relativePath;

    if (
        !$zip->addFile(
            $file->getPathname(),
            $zipPath
        )
    ) {
        $zip->close();

        if (is_file($outputFile)) {
            unlink($outputFile);
        }

        exit(
            "ZIPへの追加に失敗しました："
            . $relativePath
            . "\n"
        );
    }
}

$zip->close();

echo "ZIPを作成しました。\n";
echo $outputFile . "\n";