<?php

namespace NexaPressXmlHtmlSitemap\Provider;

use NexaPressXmlHtmlSitemap\Repository\PageRepository;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapEntry;
use NexaPressXmlHtmlSitemap\Support\DateFormatter;
use NexaPressXmlHtmlSitemap\Support\UrlBuilder;

class PageProvider implements SitemapProviderInterface
{
    /*
     * 設定項目の識別名
     */
    public function key(): string
    {
        return 'pages';
    }

    /*
     * 公開済み固定ページを取得
     */
    public function entries(): array
    {
        $entries = [];

        foreach (PageRepository::published() as $page) {
            $entries[] = new SitemapEntry(
                UrlBuilder::page($page),
                (string)($page['title'] ?? ''),
                'page',
                DateFormatter::fromRecord($page)
            );
        }

        return $entries;
    }
}