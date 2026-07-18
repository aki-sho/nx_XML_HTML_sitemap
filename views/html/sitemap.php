<?php

/*
 * HTMLサイトマップ表示データ
 */
$pageTitle = isset($title)
    ? (string)$title
    : 'サイトマップ';

$sitemapGroups = isset($groups)
    && is_array($groups)
        ? $groups
        : [];

$cssFile =
    NX_XML_HTML_SITEMAP_PATH
    . '/assets/css/public-sitemap.css';
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <?php nx_head($pageTitle); ?>

    <?php if (is_file($cssFile)): ?>
        <style>
            <?php readfile($cssFile); ?>
        </style>
    <?php endif; ?>
</head>

<body>
    <?php
    if (function_exists('nexapress_admin_header')) {
        nexapress_admin_header();
    }
    ?>

    <main class="nx-public-sitemap">
        <header class="nx-public-sitemap-header">
            <h1><?= e($pageTitle) ?></h1>

            <p>
                公開されているページの一覧です。
            </p>
        </header>

        <div class="nx-public-sitemap-sections">
            <?php
            $sectionFiles = [
                'home',
                'posts',
                'pages',
                'categories',
            ];

            foreach ($sectionFiles as $sectionName) {
                $sectionFile =
                    NX_XML_HTML_SITEMAP_PATH
                    . '/views/html/sections/'
                    . $sectionName
                    . '.php';

                if (is_file($sectionFile)) {
                    require $sectionFile;
                }
            }
            ?>
        </div>
    </main>
</body>
</html>