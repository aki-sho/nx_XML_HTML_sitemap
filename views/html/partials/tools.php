<?php
$query = $query ?? ['q' => '', 'sort' => 'updated'];
$pagination = $pagination ?? ['total' => 0, 'from' => 0, 'to' => 0];
?>

<form class="nx-sitemap-tools" method="get" action="<?= e(url('sitemap')) ?>" role="search">
    <div>
        <label for="nx-sitemap-query">タイトルで検索</label>
        <input type="search" id="nx-sitemap-query" name="q" maxlength="100"
            value="<?= e($query['q']) ?>" placeholder="キーワードを入力">
    </div>
    <div>
        <label for="nx-sitemap-sort">並び順</label>
        <select id="nx-sitemap-sort" name="sort">
            <option value="updated"<?= $query['sort'] === 'updated' ? ' selected' : '' ?>>更新が新しい順</option>
            <option value="title"<?= $query['sort'] === 'title' ? ' selected' : '' ?>>名前順</option>
        </select>
    </div>
    <button type="submit">表示する</button>
    <?php if ($query['q'] !== ''): ?>
        <a href="<?= e(url('sitemap')) ?>">検索を解除</a>
    <?php endif; ?>
</form>

<p class="nx-sitemap-results" role="status">
    <?php if ($pagination['total'] === 0): ?>
        該当するページがありません。
    <?php else: ?>
        全<?= e(number_format($pagination['total'])) ?>件中
        <?= e((string)$pagination['from']) ?>〜<?= e((string)$pagination['to']) ?>件を表示
    <?php endif; ?>
</p>
