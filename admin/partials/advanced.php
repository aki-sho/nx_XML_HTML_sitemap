<?php

$prefix = 'nx-sitemap-' . $sitemapFormat;
$listFields = [
    'excluded_post_ids' => ['除外する投稿ID', '半角カンマまたは改行で区切って入力してください。最大1000件。'],
    'excluded_page_ids' => ['除外する固定ページID', '半角カンマまたは改行で区切って入力してください。最大1000件。'],
    'excluded_urls' => ['除外するURL・パス', '公開URLまたは公開URLのパス（例：/post/example）を1行に1件。完全一致で除外します。最大1000件。'],
];
?>

<div class="nx-sitemap-advanced">
    <h3>掲載除外・表示設定</h3>
    <p>ここで指定した除外設定は、このタブのサイトマップだけに適用します。noindexが設定された投稿・固定ページも除外します。</p>

    <?php foreach ($listFields as $field => [$label, $help]): ?>
        <div class="nx-sitemap-field">
            <label for="<?= e($prefix . '-' . $field) ?>"><?= e($label) ?></label>
            <textarea id="<?= e($prefix . '-' . $field) ?>" name="<?= e($field) ?>"
                rows="3" maxlength="131072" aria-describedby="<?= e($prefix . '-' . $field . '-help') ?>"
            ><?= e(implode($field === 'excluded_urls' ? "\n" : ', ', (array)($values[$field] ?? []))) ?></textarea>
            <small id="<?= e($prefix . '-' . $field . '-help') ?>"><?= e($help) ?></small>
        </div>
    <?php endforeach; ?>

    <div class="nx-sitemap-field">
        <label for="<?= e($prefix . '-cache-ttl') ?>">キャッシュ有効期間（秒）</label>
        <input type="number" id="<?= e($prefix . '-cache-ttl') ?>" name="cache_ttl"
            value="<?= e((string)($values['cache_ttl'] ?? 300)) ?>" min="60" max="3600" required>
        <small>60〜3600秒。設定保存・管理画面でのコンテンツ更新後は、次のアクセスで再生成します。</small>
    </div>

    <?php if ($sitemapFormat === 'xml'): ?>
        <div class="nx-sitemap-field">
            <label for="nx-sitemap-xml-urls-per-file">XML 1ファイルあたりのURL数</label>
            <input type="number" id="nx-sitemap-xml-urls-per-file" name="urls_per_file"
                value="<?= e((string)($values['urls_per_file'] ?? 50000)) ?>" min="1" max="50000" required>
            <small>件数または容量の上限に達すると自動分割し、/sitemap.xmlでインデックスを公開します。</small>
        </div>
    <?php else: ?>
        <div class="nx-sitemap-field">
            <label for="nx-sitemap-html-per-page">1ページあたりの表示件数</label>
            <input type="number" id="nx-sitemap-html-per-page" name="per_page"
                value="<?= e((string)($values['per_page'] ?? 50)) ?>" min="10" max="200" required>
            <small>10〜200件。閲覧ページでタイトル検索と並び替えを利用できます。</small>
        </div>
    <?php endif; ?>
</div>
