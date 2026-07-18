<?php

namespace NexaPressXmlHtmlSitemap\Support;

class ExclusionPolicy
{
    /*
     * サイトマップ掲載可能なデータか確認
     */
    public static function allows(
        array $record
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

    /*
     * 真偽値として有効か確認
     */
    private static function isEnabled(
        mixed $value
    ): bool {
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