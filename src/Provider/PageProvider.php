<?php

namespace NexaPressXmlHtmlSitemap\Provider;

use NexaPressXmlHtmlSitemap\Repository\PageRepository;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapEntry;
use NexaPressXmlHtmlSitemap\Support\DateFormatter;
use NexaPressXmlHtmlSitemap\Support\UrlBuilder;
use NexaPressXmlHtmlSitemap\Support\ExclusionPolicy;

class PageProvider implements SitemapProviderInterface
{
    private array $excludedIds;

    public function __construct(array $settings = [])
    {
        $this->excludedIds = array_fill_keys((array)($settings['excluded_page_ids'] ?? []), true);
    }

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
    public function entries(): iterable
    {
        foreach (PageRepository::published() as $page) {
            if (isset($this->excludedIds[(int)$page['id']]) || !ExclusionPolicy::allows($page)) {
                continue;
            }

            yield new SitemapEntry(
                UrlBuilder::page($page),
                (string)($page['title'] ?? ''),
                'page',
                DateFormatter::fromRecord($page)
            );
        }
    }
}
