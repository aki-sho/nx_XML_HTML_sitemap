<?php

$pageEntries =
    $sitemapGroups['page'] ?? [];

if (
    !is_array($pageEntries) ||
    $pageEntries === []
) {
    return;
}
?>

<section class="nx-public-sitemap-section">
    <h2>固定ページ</h2>

    <ul>
        <?php foreach (
            $pageEntries as $entry
        ): ?>
            <li>
                <a href="<?= e($entry->url()) ?>">
                    <?= e($entry->title()) ?>
                </a>

                <?php if (
                    $entry->lastModified() !== null
                ): ?>
                    <time
                        datetime="<?= e(
                            $entry->lastModified()
                        ) ?>"
                    >
                        <?= e(
                            substr(
                                $entry->lastModified(),
                                0,
                                10
                            )
                        ) ?>
                    </time>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</section>