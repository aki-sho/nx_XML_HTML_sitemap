<?php

/*
 * 必要な変数がない場合は終了
 */
if (
    !isset($switches) ||
    !is_array($switches) ||
    !isset($values) ||
    !is_array($values)
) {
    return;
}
?>

<div class="nx-sitemap-switch-list">
    <?php foreach (
        $switches as $key => $switch
    ): ?>
        <?php
        $label = (string)(
            $switch['label'] ?? $key
        );

        $description = (string)(
            $switch['description'] ?? ''
        );

        $checked =
            ($values[$key] ?? false) === true;
        ?>

        <label class="nx-sitemap-switch">
            <span class="nx-sitemap-switch-control">
                <input
                    type="checkbox"
                    name="<?= e((string)$key) ?>"
                    value="1"
                    <?= $checked ? 'checked' : '' ?>
                >

                <span
                    class="nx-sitemap-switch-slider"
                    aria-hidden="true"
                ></span>
            </span>

            <span class="nx-sitemap-switch-content">
                <strong>
                    <?= e($label) ?>
                </strong>

                <?php if ($description !== ''): ?>
                    <small>
                        <?= e($description) ?>
                    </small>
                <?php endif; ?>
            </span>
        </label>
    <?php endforeach; ?>
</div>