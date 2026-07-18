<?php

use app\Core\Router;

/*
 * NexaPress以外から直接実行された場合は終了
 */
if (
    !defined('BASE_PATH') ||
    !isset($router) ||
    !$router instanceof Router
) {
    return;
}

/*
 * 拡張機能の基本情報
 */
if (!defined('NX_XML_HTML_SITEMAP_PATH')) {
    define(
        'NX_XML_HTML_SITEMAP_PATH',
        __DIR__
    );
}

if (!defined('NX_XML_HTML_SITEMAP_ID')) {
    define(
        'NX_XML_HTML_SITEMAP_ID',
        'nx_XML_HTML_sitemap'
    );
}

/*
 * クラスの自動読み込み
 */
require_once __DIR__ . '/autoload.php';

/*
 * 機能ごとのルートを読み込む
 */
$routeFiles = glob(
    __DIR__ . '/routes/*.php'
);

if (is_array($routeFiles)) {
    sort($routeFiles);

    foreach ($routeFiles as $routeFile) {
        require $routeFile;
    }
}

/*
 * NexaPressのフックを登録
 */
$hooksFile = __DIR__ . '/hooks.php';

if (is_file($hooksFile)) {
    require $hooksFile;
}