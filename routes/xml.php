<?php

use app\Core\Router;
use NexaPressXmlHtmlSitemap\Controller\XmlSitemapController;

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
 * XMLサイトマップ
 */
$router->get(
    '/sitemap.xml',
    XmlSitemapController::class . '@index'
);

$router->get(
    '/sitemap-{part}.xml',
    XmlSitemapController::class . '@part'
);
