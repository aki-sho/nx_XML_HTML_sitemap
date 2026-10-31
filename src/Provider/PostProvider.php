<?php

namespace NexaPressXmlHtmlSitemap\Provider;

use NexaPressXmlHtmlSitemap\Repository\PostRepository;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapEntry;
use NexaPressXmlHtmlSitemap\Support\DateFormatter;
use NexaPressXmlHtmlSitemap\Support\UrlBuilder;
use NexaPressXmlHtmlSitemap\Support\ExclusionPolicy;

class PostProvider implements SitemapProviderInterface
{
    private array $excludedIds;

    public function __construct(array $settings = [])
    {
        $this->excludedIds = array_fill_keys((array)($settings['excluded_post_ids'] ?? []), true);
    }

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
    public function entries(): iterable
    {
        foreach (PostRepository::published() as $post) {
            if (isset($this->excludedIds[(int)$post['id']]) || !ExclusionPolicy::allows($post)) {
                continue;
            }

            yield new SitemapEntry(
                UrlBuilder::post($post),
                (string)($post['title'] ?? ''),
                'post',
                DateFormatter::fromRecord($post)
            );
        }
    }
}
