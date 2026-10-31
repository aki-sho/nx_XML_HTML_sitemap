<?php

use app\Core\Auth;
use app\Core\Csrf;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapCache;

/*
 * NexaPressのフック機能が使用できない場合は終了
 */
if (!function_exists('add_action')) {
    return;
}

/*
 * コンテンツ・URL設定の変更時に、公開前のキャッシュも含めて無効化する。
 */
$sitemapInvalidated = false;
$invalidateSitemap = static function () use (&$sitemapInvalidated): void {
    if ($sitemapInvalidated) {
        return;
    }

    try {
        SitemapCache::invalidate();
        $sitemapInvalidated = true;
    } catch (Throwable $exception) {
        error_log('NX Sitemap: ' . $exception->getMessage());
    }
};

foreach ([
    'post_saved', 'post_trashed', 'post_restored', 'post_deleted',
    'page_saved', 'page_trashed', 'page_restored', 'page_deleted',
    'site_settings_saved',
] as $sitemapEvent) {
    add_action($sitemapEvent, $invalidateSitemap);
}

// 投稿操作のフックがない本体にも対応する。認証・CSRF検証済みの変更だけを監視する。
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && Auth::check() && Csrf::validate($_POST['_csrf_token'] ?? null)) {
    $sitemapRequestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $sitemapBasePath = rtrim((string)parse_url(url(), PHP_URL_PATH), '/');

    if (is_string($sitemapRequestPath)) {
        if ($sitemapBasePath !== '' && str_starts_with($sitemapRequestPath, $sitemapBasePath . '/')) {
            $sitemapRequestPath = substr($sitemapRequestPath, strlen($sitemapBasePath));
        }

        if (preg_match('#^/admin/(posts|pages|categories|settings)(/|$)#', $sitemapRequestPath)) {
            register_shutdown_function(static function () use ($invalidateSitemap): void {
                if ((int)http_response_code() < 400) {
                    $invalidateSitemap();
                }
            });
        }
    }
}

/*
 * サイトマップ管理画面か確認
 */
$isSitemapAdmin = static function (): bool {
    $requestPath = parse_url(
        $_SERVER['REQUEST_URI'] ?? '',
        PHP_URL_PATH
    );

    if (!is_string($requestPath)) {
        return false;
    }

    return str_contains(
        $requestPath,
        '/admin/extensions/nx_XML_HTML_sitemap/dashboard'
    );
};

/*
 * 管理画面用CSSを読み込む
 */
add_action(
    'admin_head',
    static function () use ($isSitemapAdmin): void {
        if (!$isSitemapAdmin()) {
            return;
        }

        $cssFiles = [
            NX_XML_HTML_SITEMAP_PATH
                . '/assets/css/admin-base.css',

            NX_XML_HTML_SITEMAP_PATH
                . '/assets/css/admin-tabs.css',

            NX_XML_HTML_SITEMAP_PATH
                . '/assets/css/admin-forms.css',
        ];

        echo '<style>';

        foreach ($cssFiles as $cssFile) {
            if (is_file($cssFile)) {
                readfile($cssFile);
            }
        }

        echo '</style>';
    }
);

/*
 * 管理画面用JavaScriptを読み込む
 */
add_action(
    'admin_footer',
    static function () use ($isSitemapAdmin): void {
        if (!$isSitemapAdmin()) {
            return;
        }

        $jsFile = NX_XML_HTML_SITEMAP_PATH
            . '/assets/js/admin-tabs.js';

        if (!is_file($jsFile)) {
            return;
        }

        echo '<script>';
        readfile($jsFile);
        echo '</script>';
    }
);
