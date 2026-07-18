<?php

$homeEntries =
    $sitemapGroups['home'] ?? [];

if (
    !is_array($homeEntries) ||
    $homeEntries === []
) {
    return;
}
?>

<section class="nx-public-sitemap-section">
    <h2>ホーム</h2>

    <ul>
        <?php foreach (
            $homeEntries as $entry
        ): ?>
            <li>
                <a href="<?= e($entry->url()) ?>">
                    <?= e($entry->title()) ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>