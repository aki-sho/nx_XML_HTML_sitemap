<?php

namespace NexaPressXmlHtmlSitemap\Admin;

class Notice
{
    private const SESSION_KEY =
        'nx_xml_html_sitemap_notice';

    /*
     * 成功メッセージを保存
     */
    public static function success(
        string $message
    ): void {
        self::set(
            'success',
            $message
        );
    }

    /*
     * エラーメッセージを保存
     */
    public static function error(
        string $message
    ): void {
        self::set(
            'error',
            $message
        );
    }

    /*
     * メッセージを取得して削除
     */
    public static function pull(): ?array
    {
        $notice =
            $_SESSION[self::SESSION_KEY] ?? null;

        unset(
            $_SESSION[self::SESSION_KEY]
        );

        return is_array($notice)
            ? $notice
            : null;
    }

    /*
     * メッセージをセッションへ保存
     */
    private static function set(
        string $type,
        string $message
    ): void {
        $_SESSION[self::SESSION_KEY] = [
            'type' => $type,
            'message' => $message,
        ];
    }
}