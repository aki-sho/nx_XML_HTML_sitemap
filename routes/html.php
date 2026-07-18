<?php

use app\Core\Router;
use NexaPressXmlHtmlSitemap\Controller\HtmlSitemapController;

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
 * HTMLサイトマップ
 */
$router->get(
    '/sitemap',
    HtmlSitemapController::class . '@index'
);