<?php

namespace NexaPressXmlHtmlSitemap\Controller;

use NexaPressXmlHtmlSitemap\Provider\ProviderRegistry;
use NexaPressXmlHtmlSitemap\Settings\Settings;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapService;
use NexaPressXmlHtmlSitemap\Xml\XmlRenderer;
use NexaPressXmlHtmlSitemap\Xml\XmlResponse;

class XmlSitemapController
{
    /*
     * XMLサイトマップを表示
     */
    public function index(): void
    {
        $settings =
            Settings::section('xml');

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

        $renderer =
            new XmlRenderer();

        $xml =
            $renderer->render($entries);

        XmlResponse::send($xml);
    }

    /*
     * 404を返す
     */
    private function notFound(): void
    {
        http_response_code(404);

        echo '404 Not Found';
    }
}