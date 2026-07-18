<?php

/*
 * NexaPressのフック機能が使用できない場合は終了
 */
if (!function_exists('add_action')) {
    return;
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