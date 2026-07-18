<?php

namespace NexaPressXmlHtmlSitemap\Provider;

use NexaPressXmlHtmlSitemap\Repository\PostRepository;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapEntry;
use NexaPressXmlHtmlSitemap\Support\DateFormatter;
use NexaPressXmlHtmlSitemap\Support\UrlBuilder;

class PostProvider implements SitemapProviderInterface
{
    /*
     * 設定項目の識別名
     */
    public function key(): string
    {
        return 'posts';
    }

    /*
     * 公開済み投稿を取得
     */
    public function entries(): array
    {
        $entries = [];

        foreach (PostRepository::published() as $post) {
            $entries[] = new SitemapEntry(
                UrlBuilder::post($post),
                (string)($post['title'] ?? ''),
                'post',
                DateFormatter::fromRecord($post)
            );
        }

        return $entries;
    }
}