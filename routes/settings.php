<?php

use app\Core\Router;
use NexaPressXmlHtmlSitemap\Admin\SettingsController;

/*
 * Routerが使用できない場合は終了
 */
if (
    !isset($router) ||
    !$router instanceof Router
) {
    return;
}

/*
 * 設定保存URL
 */
$settingsBase =
    '/admin/extensions/nx_XML_HTML_sitemap/settings';

/*
 * XMLサイトマップ設定
 */
$router->post(
    $settingsBase . '/xml',
    SettingsController::class . '@saveXml'
);

/*
 * HTMLサイトマップ設定
 */
$router->post(
    $settingsBase . '/html',
    SettingsController::class . '@saveHtml'
);