<?php

namespace NexaPressXmlHtmlSitemap\Settings;

use InvalidArgumentException;

class SettingsValidator
{
    /*
     * XMLサイトマップ設定を整形
     */
    public static function xml(array $input): array
    {
        return array_merge(self::advanced($input), [
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
            'urls_per_file' => self::integer($input['urls_per_file'] ?? 50000, 1, 50000, 50000),
        ]);
    }

    /*
     * HTMLサイトマップ設定を整形
     */
    public static function html(array $input): array
    {
        $title = trim(
            self::text($input['title'] ?? '')
        );

        if ($title === '') {
            $title = 'サイトマップ';
        }

        $title = mb_substr(
            $title,
            0,
            100
        );

        return array_merge(self::advanced($input), [
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
            'per_page' => self::integer($input['per_page'] ?? 50, 10, 200, 50),
        ]);
    }

    private static function advanced(array $input): array
    {
        return [
            'excluded_post_ids' => self::ids($input['excluded_post_ids'] ?? ''),
            'excluded_page_ids' => self::ids($input['excluded_page_ids'] ?? ''),
            'excluded_urls' => self::urls($input['excluded_urls'] ?? ''),
            'cache_ttl' => self::integer($input['cache_ttl'] ?? 300, 60, 3600, 300),
        ];
    }

    private static function ids(mixed $input): array
    {
        $tokens = preg_split('/[\s,、]+/u', trim(self::text($input)), -1, PREG_SPLIT_NO_EMPTY);
        $ids = [];

        foreach ($tokens ?: [] as $token) {
            $id = filter_var($token, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if ($id === false) {
                throw new InvalidArgumentException('除外IDには正の整数を入力してください。');
            }

            $ids[$id] = $id;
        }

        if (count($ids) > 1000) {
            throw new InvalidArgumentException('除外IDは各1000件までです。');
        }

        sort($ids, SORT_NUMERIC);
        return $ids;
    }

    private static function urls(mixed $input): array
    {
        $urls = [];

        foreach (preg_split('/\R/u', self::text($input)) ?: [] as $line) {
            if (preg_match('/[\x00-\x1f\x7f]/', $line)) {
                throw new InvalidArgumentException('除外URLに制御文字は使用できません。');
            }

            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (strlen($line) > 2048 || preg_match('/[\x00-\x20\x7f]/', $line)
                || (!str_starts_with($line, '/') && !preg_match('#^https?://[^/]+#i', $line))
                || str_starts_with($line, '//') || parse_url($line) === false) {
                throw new InvalidArgumentException('除外URLには公開URLまたは「/」から始まるパスを入力してください。');
            }

            $urls[$line] = $line;
        }

        if (count($urls) > 1000) {
            throw new InvalidArgumentException('除外URLは1000件までです。');
        }

        return array_values($urls);
    }

    private static function text(mixed $value): string
    {
        if (!is_string($value) || strlen($value) > 131072 || !mb_check_encoding($value, 'UTF-8')) {
            throw new InvalidArgumentException('入力内容の形式または長さが不正です。');
        }

        return $value;
    }

    private static function integer(mixed $value, int $min, int $max, int $default): int
    {
        $number = filter_var($value, FILTER_VALIDATE_INT);
        return $number === false ? $default : max($min, min($max, $number));
    }

    /*
     * チェックボックスの値を取得
     */
    private static function checked(
        array $input,
        string $key
    ): bool {
        return ($input[$key] ?? null) === '1';
    }
}
