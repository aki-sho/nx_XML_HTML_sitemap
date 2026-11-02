<?php

use NexaPressXmlHtmlSitemap\Admin\Notice;
use NexaPressXmlHtmlSitemap\Settings\Settings;

\app\Core\Permission::require('extensions.manage');

/*
 * 設定セクションを取得
 */
$sectionsFile =
    NX_XML_HTML_SITEMAP_PATH
    . '/config/admin-sections.php';

$sections = is_file($sectionsFile)
    ? require $sectionsFile
    : [];

if (!is_array($sections)) {
    $sections = [];
}

/*
 * 保存済み設定を取得
 */
try { $settings = Settings::all(); }
catch (\Throwable $exception) {
    echo '<div class="nx-sitemap-notice is-error" role="alert">設定を読み込めません。破損した設定を上書きせず、保存先を確認してください。</div>';
    return;
}

/*
 * 保存結果を取得
 */
$notice = Notice::pull();

$firstSection =
    array_key_first($sections);
?>

<div
    class="nx-sitemap-admin"
    data-nx-sitemap-tabs
>
    <div class="nx-sitemap-header">
        <div>
            <h1>サイトマップ</h1>

            <p>
                XMLサイトマップと
                HTMLサイトマップを管理します。
            </p>
        </div>

        <span class="nx-sitemap-version">
            Version <?= e((string)($extensionInfo['version'] ?? '1.0.3')) ?>
        </span>
    </div>

    <?php
    $noticeFile =
        NX_XML_HTML_SITEMAP_PATH
        . '/admin/partials/notice.php';

    if (is_file($noticeFile)) {
        require $noticeFile;
    }
    ?>

    <section class="nx-sitemap-public-status">
        <h2>公開状態の確認</h2>
        <p>拡張機能は有効です。XML出力：<?= !empty($settings['xml']['enabled']) ? '有効' : '停止中（404を返します）' ?> ／ HTML出力：<?= !empty($settings['html']['enabled']) ? '有効' : '停止中（404を返します）' ?></p>
        <p><a href="<?= e(url('sitemap.xml')) ?>" target="_blank" rel="noopener">XMLを開く</a> ／ <a href="<?= e(url('sitemap')) ?>" target="_blank" rel="noopener">HTMLを開く</a></p>
        <button type="button" data-nx-sitemap-check="<?= e(url('sitemap.xml')) ?>">XMLの公開結果を確認</button>
        <p role="status" data-nx-sitemap-result>停止中の404、生成エラーの503、正常なXMLを区別して確認できます。</p>
    </section>
    <?php if ($sections === []): ?>
        <div class="nx-sitemap-notice is-error">
            設定画面を読み込めませんでした。
        </div>
    <?php else: ?>
        <nav
            class="nx-sitemap-tabs"
            aria-label="サイトマップ設定"
        >
            <?php foreach (
                $sections as $sectionKey => $section
            ): ?>
                <button
                    type="button"
                    class="nx-sitemap-tab<?= $sectionKey === $firstSection
                        ? ' is-active'
                        : '' ?>"
                    data-nx-sitemap-tab="<?= e(
                        (string)$sectionKey
                    ) ?>"
                >
                    <?= e(
                        (string)(
                            $section['label']
                            ?? $sectionKey
                        )
                    ) ?>
                </button>
            <?php endforeach; ?>
        </nav>

        <div class="nx-sitemap-panels">
            <?php foreach (
                $sections as $sectionKey => $section
            ): ?>
                <?php
                $sectionSettings =
                    $settings[$sectionKey] ?? [];

                if (!is_array($sectionSettings)) {
                    $sectionSettings = [];
                }

                $view = trim(
                    (string)($section['view'] ?? '')
                );

                $viewFile =
                    NX_XML_HTML_SITEMAP_PATH
                    . '/'
                    . ltrim($view, '/\\');
                ?>

                <section
                    class="nx-sitemap-panel<?= $sectionKey === $firstSection
                        ? ' is-active'
                        : '' ?>"
                    data-nx-sitemap-panel="<?= e(
                        (string)$sectionKey
                    ) ?>"
                >
                    <?php if (
                        $view !== '' &&
                        is_file($viewFile)
                    ): ?>
                        <?php require $viewFile; ?>
                    <?php else: ?>
                        <div
                            class="nx-sitemap-notice is-error"
                        >
                            設定項目を読み込めません。
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
