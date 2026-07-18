<?php

namespace NexaPressXmlHtmlSitemap\Provider;

use NexaPressXmlHtmlSitemap\Sitemap\SitemapEntry;
use NexaPressXmlHtmlSitemap\Support\UrlBuilder;

class HomeProvider implements SitemapProviderInterface
{
    /*
     * 設定項目の識別名
     */
    public function key(): string
    {
        return 'home';
    }

    /*
     * トップページを取得
     */
    public function entries(): array
    {
        return [
            new SitemapEntry(
                UrlBuilder::home(),
                site_title(),
                'home'
            ),
        ];
    }
}