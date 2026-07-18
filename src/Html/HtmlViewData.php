<?php

namespace NexaPressXmlHtmlSitemap\Html;

use NexaPressXmlHtmlSitemap\Sitemap\SitemapEntry;

class HtmlViewData
{
    /*
     * HTMLサイトマップ用データを作成
     */
    public static function build(
        array $entries,
        array $settings
    ): array {
        $groups = [
            'home' => [],
            'post' => [],
            'page' => [],
            'category' => [],
        ];

        foreach ($entries as $entry) {
            if (
                !$entry instanceof SitemapEntry
            ) {
                continue;
            }

            $type = $entry->type();

            if (!array_key_exists($type, $groups)) {
                continue;
            }

            $groups[$type][] = $entry;
        }

        return [
            'title' => self::title($settings),
            'groups' => $groups,
        ];
    }

    /*
     * ページタイトルを取得
     */
    private static function title(
        array $settings
    ): string {
        $title = trim(
            (string)($settings['title'] ?? '')
        );

        if ($title === '') {
            return 'サイトマップ';
        }

        return $title;
    }
}