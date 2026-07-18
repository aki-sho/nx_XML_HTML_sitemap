<?php

/*
 * 通知がない場合は何も表示しない
 */
if (
    !isset($notice) ||
    !is_array($notice)
) {
    return;
}

$type =
    ($notice['type'] ?? '') === 'error'
        ? 'error'
        : 'success';

$message = trim(
    (string)($notice['message'] ?? '')
);

if ($message === '') {
    return;
}
?>

<div
    class="nx-sitemap-notice is-<?= e($type) ?>"
    role="status"
>
    <?= e($message) ?>
</div>