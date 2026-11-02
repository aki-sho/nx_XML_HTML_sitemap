<?php

namespace NexaPressXmlHtmlSitemap\Controller;

use NexaPressXmlHtmlSitemap\Html\HtmlViewData;
use NexaPressXmlHtmlSitemap\Settings\Settings;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapCache;
use Throwable;

class HtmlSitemapController
{
    /*
     * HTMLサイトマップを表示
     */
    public function index(): void
    {
        try {
            $settings = Settings::section('html');
            if (($settings['enabled'] ?? false) !== true) { $this->notFound(); return; }
            $query = HtmlViewData::query($_GET);
            $stream = SitemapCache::openHtml($settings, $query['sort']);

            try {
                $viewData = HtmlViewData::build(SitemapCache::readEntries($stream), $settings, $query);
            } finally {
                fclose($stream);
            }
        } catch (Throwable $exception) {
            error_log('NX Sitemap: ' . $exception->getMessage());
            http_response_code(503);
            header('Cache-Control: no-store');
            header('Retry-After: 5');
            echo 'サイトマップを一時的に表示できません。';
            return;
        }

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
        header('Cache-Control: no-store');

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
