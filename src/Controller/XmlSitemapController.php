<?php

namespace NexaPressXmlHtmlSitemap\Controller;

use NexaPressXmlHtmlSitemap\Settings\Settings;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapCache;
use NexaPressXmlHtmlSitemap\Xml\XmlResponse;
use Throwable;

class XmlSitemapController
{
    /*
     * XMLサイトマップを表示
     */
    public function index(): void
    {
        $this->respond();
    }

    public function part(string $part): void
    {
        $this->respond($part);
    }

    private function respond(?string $part = null): void
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

        try {
            $stream = $part === null ? SitemapCache::open('xml', $settings)
                : SitemapCache::openPart($settings, $part);

            if ($stream === null) {
                $this->notFound();
                return;
            }
        } catch (Throwable $exception) {
            error_log('NX Sitemap: ' . $exception->getMessage());
            http_response_code(503);
            header('Retry-After: 5');
            echo 'サイトマップを一時的に表示できません。';
            return;
        }

        try {
            XmlResponse::sendStream($stream);
        } finally {
            fclose($stream);
        }
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
