<?php

/*
 * HTMLサイトマップ設定
 */
$values = isset($sectionSettings)
    && is_array($sectionSettings)
        ? $sectionSettings
        : [];

$switches = [
    'enabled' => [
        'label' =>
            'HTMLサイトマップを有効にする',
        'description' =>
            '/sitemapで公開します。',
    ],

    'include_home' => [
        'label' =>
            'トップページを表示する',
        'description' =>
            'トップページへのリンクを表示します。',
    ],

    'include_posts' => [
        'label' =>
            '投稿一覧を表示する',
        'description' =>
            '公開済み投稿へのリンクを表示します。',
    ],

    'include_pages' => [
        'label' =>
            '固定ページ一覧を表示する',
        'description' =>
            '公開済み固定ページへのリンクを表示します。',
    ],

    'include_categories' => [
        'label' =>
            'カテゴリー一覧を表示する',
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
        <h2>HTMLサイトマップ</h2>

        <p>
            閲覧者向けの
            HTMLサイトマップを設定します。
        </p>
    </div>

    <div class="nx-sitemap-url-box">
        <span>公開URL</span>

        <a
            href="<?= e(url('sitemap')) ?>"
            target="_blank"
            rel="noopener noreferrer"
        >
            <?= e(url('sitemap')) ?>
        </a>
    </div>

    <form
        method="post"
        action="<?= e(
            url(
                'admin/extensions/'
                . NX_XML_HTML_SITEMAP_ID
                . '/settings/html'
            )
        ) ?>"
    >
        <div class="nx-sitemap-field">
            <label for="nx-sitemap-html-title">
                ページタイトル
            </label>

            <input
                type="text"
                id="nx-sitemap-html-title"
                name="title"
                value="<?= e(
                    (string)(
                        $values['title']
                        ?? 'サイトマップ'
                    )
                ) ?>"
                maxlength="100"
                required
            >
        </div>

        <?php if (is_file($partialFile)): ?>
            <?php require $partialFile; ?>
        <?php endif; ?>

        <div class="nx-sitemap-form-actions">
            <button
                type="submit"
                class="nx-sitemap-submit"
            >
                HTML設定を保存
            </button>
        </div>
    </form>
</div>