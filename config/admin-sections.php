<?php

/*
 * 管理画面の設定セクション
 */
return [
    'xml' => [
        'label' => 'XMLサイトマップ',
        'description' =>
            '検索エンジン向けのXMLサイトマップを設定します。',
        'view' =>
            'admin/sections/xml.php',
    ],

    'html' => [
        'label' => 'HTMLサイトマップ',
        'description' =>
            '閲覧者向けのHTMLサイトマップを設定します。',
        'view' =>
            'admin/sections/html.php',
    ],
];