<?php

$categoryEntries =
    $sitemapGroups['category'] ?? [];

if (
    !is_array($categoryEntries) ||
    $categoryEntries === []
) {
    return;
}
?>

<section class="nx-public-sitemap-section">
    <h2>カテゴリー</h2>

    <ul>
        <?php foreach (
            $categoryEntries as $entry
        ): ?>
            <li>
                <a href="<?= e($entry->url()) ?>">
                    <?= e($entry->title()) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>