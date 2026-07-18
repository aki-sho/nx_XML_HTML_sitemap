<?php

$postEntries =
    $sitemapGroups['post'] ?? [];

if (
    !is_array($postEntries) ||
    $postEntries === []
) {
    return;
}
?>

<section class="nx-public-sitemap-section">
    <h2>投稿</h2>

    <ul>
        <?php foreach (
            $postEntries as $entry
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