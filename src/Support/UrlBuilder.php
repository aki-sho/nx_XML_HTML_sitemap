<?php

namespace NexaPressXmlHtmlSitemap\Support;

class UrlBuilder
{
    /*
     * トップページURL
     */
    public static function home(): string
    {
        return self::absolute(url());
    }

    /*
     * 投稿URL
     */
    public static function post(
        array $post
    ): string {
        return self::absolute(post_url($post));
    }

    /*
     * 固定ページURL
     */
    public static function page(
        array $page
    ): string {
        return self::absolute(page_url($page));
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

        return self::absolute(url(
            'category/' . rawurlencode($slug)
        ));
    }

    /** Core URL helpers may return paths; XML requires a complete public URL. */
    public static function absolute(string $url): string
    {
        if (preg_match('#^https?://#i', $url)) { return $url; }
        if (!str_starts_with($url, '/') || str_starts_with($url, '//')) { throw new \RuntimeException('公開URLを確認できません。'); }
        $host = (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
        if ($host === '' || preg_match('/[\x00-\x20\x7f\/@\\\\?#]/', $host) || !filter_var('http://' . $host, FILTER_VALIDATE_URL)) {
            throw new \RuntimeException('公開サイトのホストを確認できません。');
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (int)($_SERVER['SERVER_PORT'] ?? 0) === 443;
        return ($https ? 'https://' : 'http://') . strtolower($host) . $url;
    }
}
