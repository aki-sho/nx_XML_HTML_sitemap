<?php

namespace NexaPressXmlHtmlSitemap\Html;

use NexaPressXmlHtmlSitemap\Sitemap\SitemapEntry;

class HtmlViewData
{
    public static function query(array $input): array
    {
        $q = $input['q'] ?? '';
        $q = is_string($q) && mb_check_encoding($q, 'UTF-8')
            ? mb_substr(trim(preg_replace('/[\\x00-\\x1f\\x7f]/u', '', $q)), 0, 100, 'UTF-8') : '';
        $sort = ($input['sort'] ?? '') === 'title' ? 'title' : 'updated';
        $page = filter_var($input['page'] ?? 1, FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 250000]]);

        return ['q' => $q, 'sort' => $sort, 'page' => $page === false ? 1 : $page];
    }

    /*
     * 入力は指定の順序に並んだキャッシュ。検索・ページ送りはストリーム上で行い、
     * 表示ページと最終ページの最大2ページ分だけを保持する。
     */
    public static function build(iterable $entries, array $settings, array $request = []): array
    {
        $query = self::query($request);
        $size = filter_var($settings['per_page'] ?? 50, FILTER_VALIDATE_INT);
        $size = $size === false ? 50 : max(10, min(200, $size));
        $offset = ($query['page'] - 1) * $size;
        $groups = ['home' => [], 'post' => [], 'page' => [], 'category' => []];
        $selected = [];
        $tail = [];
        $total = 0;

        foreach ($entries as $entry) {
            if (!$entry instanceof SitemapEntry || !array_key_exists($entry->type(), $groups)
                || ($query['q'] !== '' && mb_stripos($entry->title(), $query['q'], 0, 'UTF-8') === false)) {
                continue;
            }

            if ($total % $size === 0) {
                $tail = [];
            }

            $tail[] = $entry;

            if ($total >= $offset && $total < $offset + $size) {
                $selected[] = $entry;
            }

            $total++;
        }

        $pages = max(1, (int)ceil($total / $size));
        $page = min($query['page'], $pages);

        if ($page !== $query['page']) {
            $selected = $tail;
        }

        foreach ($selected as $entry) {
            $groups[$entry->type()][] = $entry;
        }

        $pageUrl = static function (int $number) use ($query): string {
            $parameters = ['sort' => $query['sort'], 'page' => $number];

            if ($query['q'] !== '') {
                $parameters['q'] = $query['q'];
            }

            return url('sitemap') . '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
        };

        $numbers = array_unique(array_merge([1, $pages],
            range(max(1, $page - 2), min($pages, $page + 2))));
        sort($numbers, SORT_NUMERIC);
        $links = [];
        $previous = 0;

        foreach ($numbers as $number) {
            if ($previous !== 0 && $number > $previous + 1) {
                $links[] = null;
            }

            $links[] = ['number' => $number, 'url' => $pageUrl($number), 'current' => $number === $page];
            $previous = $number;
        }

        $title = is_string($settings['title'] ?? null) ? trim($settings['title']) : '';

        return [
            'title' => $title !== '' ? $title : 'サイトマップ',
            'groups' => $groups,
            'query' => $query,
            'pagination' => [
                'page' => $page, 'pages' => $pages, 'total' => $total,
                'from' => $total === 0 ? 0 : ($page - 1) * $size + 1,
                'to' => min($page * $size, $total), 'links' => $links,
                'previous' => $page > 1 ? $pageUrl($page - 1) : null,
                'next' => $page < $pages ? $pageUrl($page + 1) : null,
            ],
        ];
    }
}
