<?php

namespace NexaPressXmlHtmlSitemap\Support;

class ExclusionPolicy
{
    /*
     * サイトマップ掲載可能なデータか確認
     */
    public static function allows(
        array $record,
        array $settings = [],
        string $type = ''
    ): bool {
        if (
            isset($record['status']) &&
            (string)$record['status']
                !== 'published'
        ) {
            return false;
        }

        if (
            isset($record['noindex']) &&
            self::isEnabled(
                $record['noindex']
            )
        ) {
            return false;
        }

        $key = $type === 'post' ? 'excluded_post_ids' : 'excluded_page_ids';

        if ($type !== '' && in_array((int)($record['id'] ?? 0), (array)($settings[$key] ?? []), true)) {
            return false;
        }

        if (!empty($record['deleted_at'])) {
            return false;
        }

        $slug = trim(
            (string)($record['slug'] ?? '')
        );

        if (
            array_key_exists('slug', $record) &&
            $slug === ''
        ) {
            return false;
        }

        return true;
    }

    public static function urlRules(array $settings): array
    {
        $rules = [];

        foreach ((array)($settings['excluded_urls'] ?? []) as $excluded) {
            if (is_string($excluded)) {
                $rules[explode('#', trim($excluded), 2)[0]] = true;
            }
        }

        return $rules;
    }

    public static function allowsUrl(string $url, array $rules): bool
    {
        $url = explode('#', $url, 2)[0];
        $parts = parse_url($url);
        $path = is_array($parts) ? ($parts['path'] ?? '/')
            . (isset($parts['query']) ? '?' . $parts['query'] : '') : $url;

        return !isset($rules[$url]) && !isset($rules[$path]);
    }

    /*
     * 真偽値として有効か確認
     */
    private static function isEnabled(
        mixed $value
    ): bool {
        if (is_string($value)) {
            $value = strtolower(trim($value));
        }

        return in_array(
            $value,
            [
                true,
                1,
                '1',
                'true',
                'on',
                'yes',
            ],
            true
        );
    }
}
