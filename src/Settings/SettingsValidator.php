<?php

namespace NexaPressXmlHtmlSitemap\Settings;

class SettingsValidator
{
    /*
     * XMLサイトマップ設定を整形
     */
    public static function xml(array $input): array
    {
        return [
            'enabled' =>
                self::checked($input, 'enabled'),

            'include_home' =>
                self::checked($input, 'include_home'),

            'include_posts' =>
                self::checked($input, 'include_posts'),

            'include_pages' =>
                self::checked($input, 'include_pages'),

            'include_categories' =>
                self::checked(
                    $input,
                    'include_categories'
                ),
        ];
    }

    /*
     * HTMLサイトマップ設定を整形
     */
    public static function html(array $input): array
    {
        $title = trim(
            (string)($input['title'] ?? '')
        );

        if ($title === '') {
            $title = 'サイトマップ';
        }

        $title = mb_substr(
            $title,
            0,
            100
        );

        return [
            'enabled' =>
                self::checked($input, 'enabled'),

            'title' => $title,

            'include_home' =>
                self::checked($input, 'include_home'),

            'include_posts' =>
                self::checked($input, 'include_posts'),

            'include_pages' =>
                self::checked($input, 'include_pages'),

            'include_categories' =>
                self::checked(
                    $input,
                    'include_categories'
                ),
        ];
    }

    /*
     * チェックボックスの値を取得
     */
    private static function checked(
        array $input,
        string $key
    ): bool {
        return isset($input[$key])
            && (string)$input[$key] === '1';
    }
}