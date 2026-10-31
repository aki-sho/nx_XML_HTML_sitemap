<?php

/*
 * サイトマップ設定の初期値
 */
return [
    'xml' => [
        'enabled' => true,
        'include_home' => true,
        'include_posts' => true,
        'include_pages' => true,
        'include_categories' => true,
        'excluded_post_ids' => [],
        'excluded_page_ids' => [],
        'excluded_urls' => [],
        'cache_ttl' => 300,
        'urls_per_file' => 50000,
    ],

    'html' => [
        'enabled' => true,
        'title' => 'サイトマップ',
        'include_home' => true,
        'include_posts' => true,
        'include_pages' => true,
        'include_categories' => true,
        'excluded_post_ids' => [],
        'excluded_page_ids' => [],
        'excluded_urls' => [],
        'cache_ttl' => 300,
        'per_page' => 50,
    ],
];
