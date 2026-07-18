<?php

/*
 * XMLサイトマップ設定
 */
$values = isset($sectionSettings)
    && is_array($sectionSettings)
        ? $sectionSettings
        : [];

$switches = [
    'enabled' => [
        'label' =>
            'XMLサイトマップを有効にする',
        'description' =>
            '/sitemap.xmlで公開します。',
    ],

    'include_home' => [
        'label' =>
            'トップページを掲載する',
        'description' =>
            'サイトのトップページを掲載します。',
    ],

    'include_posts' => [
        'label' =>
            '投稿を掲載する',
        'description' =>
            '公開済みの投稿を掲載します。',
    ],

    'include_pages' => [
        'label' =>
            '固定ページを掲載する',
        'description' =>
            '公開済みの固定ページを掲載します。',
    ],

    'include_categories' => [
        'label' =>
            'カテゴリーを掲載する',
        'description' =>
            '公開カテゴリーページ対応後に使用します。',
    ],
];

$partialFile =
    NX_XML_HTML_SITEMAP_PATH
    . '/admin/partials/content-switches.php';
?>

<div class="nx-sitemap-section">
    <div class="nx-sitemap-section-header">
        <h2>XMLサイトマップ</h2>

        <p>
            検索エンジン向けの
            XMLサイトマップを設定します。
        </p>
    </div>

    <div class="nx-sitemap-url-box">
        <span>公開URL</span>

        <a
            href="<?= e(url('sitemap.xml')) ?>"
            target="_blank"
            rel="noopener noreferrer"
        >
            <?= e(url('sitemap.xml')) ?>
        </a>
    </div>

    <form
        method="post"
        action="<?= e(
            url(
                'admin/extensions/'
                . NX_XML_HTML_SITEMAP_ID
                . '/settings/xml'
            )
        ) ?>"
    >
        <?php if (is_file($partialFile)): ?>
            <?php require $partialFile; ?>
        <?php endif; ?>

        <div class="nx-sitemap-form-actions">
            <button
                type="submit"
                class="nx-sitemap-submit"
            >
                XML設定を保存
            </button>
        </div>
    </form>
</div>