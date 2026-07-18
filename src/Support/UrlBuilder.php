<?php

namespace NexaPressXmlHtmlSitemap\Support;

class UrlBuilder
{
    /*
     * トップページURL
     */
    public static function home(): string
    {
        return url();
    }

    /*
     * 投稿URL
     */
    public static function post(
        array $post
    ): string {
        return post_url($post);
    }

    /*
     * 固定ページURL
     */
    public static function page(
        array $page
    ): string {
        return page_url($page);
    }

    /*
     * カテゴリーURL
     *
     * 現在のNexaPress本体には
     * 公開カテゴリーページがないため未使用
     */
    public static function category(
        array $category
    ): string {
        $slug = trim(
            (string)($category['slug'] ?? '')
        );

        if ($slug === '') {
            return '';
        }

        return url(
            'category/' . rawurlencode($slug)
        );
    }
}