<?php

namespace NexaPressXmlHtmlSitemap\Controller;

use NexaPressXmlHtmlSitemap\Html\HtmlViewData;
use NexaPressXmlHtmlSitemap\Provider\ProviderRegistry;
use NexaPressXmlHtmlSitemap\Settings\Settings;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapService;

class HtmlSitemapController
{
    /*
     * HTMLサイトマップを表示
     */
    public function index(): void
    {
        $settings =
            Settings::section('html');

        if (
            ($settings['enabled'] ?? false)
            !== true
        ) {
            $this->notFound();
            return;
        }

        $registry =
            ProviderRegistry::createDefault();

        $service =
            new SitemapService($registry);

        $entries =
            $service->collect($settings);

        $viewData =
            HtmlViewData::build(
                $entries,
                $settings
            );

        $viewFile =
            NX_XML_HTML_SITEMAP_PATH
            . '/views/html/sitemap.php';

        if (!is_file($viewFile)) {
            $this->serverError();
            return;
        }

        extract(
            $viewData,
            EXTR_SKIP
        );

        header(
            'Content-Type: text/html; charset=UTF-8'
        );

        require $viewFile;
    }

    /*
     * 404を返す
     */
    private function notFound(): void
    {
        http_response_code(404);

        echo '404 Not Found';
    }

    /*
     * 500を返す
     */
    private function serverError(): void
    {
        http_response_code(500);

        echo 'サイトマップを表示できません。';
    }
}