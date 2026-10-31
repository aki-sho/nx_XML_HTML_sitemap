<?php if (($pagination['pages'] ?? 1) > 1): ?>
    <nav class="nx-sitemap-pagination" aria-label="サイトマップのページ送り">
        <?php if ($pagination['previous'] !== null): ?>
            <a href="<?= e($pagination['previous']) ?>" rel="prev">前へ</a>
        <?php endif; ?>

        <?php foreach ($pagination['links'] as $link): ?>
            <?php if ($link === null): ?>
                <span aria-hidden="true">…</span>
            <?php elseif ($link['current']): ?>
                <span class="is-current" aria-current="page"><?= e((string)$link['number']) ?></span>
            <?php else: ?>
                <a href="<?= e($link['url']) ?>" aria-label="<?= e((string)$link['number']) ?>ページ目">
                    <?= e((string)$link['number']) ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if ($pagination['next'] !== null): ?>
            <a href="<?= e($pagination['next']) ?>" rel="next">次へ</a>
        <?php endif; ?>
    </nav>
<?php endif; ?>
