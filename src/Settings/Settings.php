<?php

namespace NexaPressXmlHtmlSitemap\Settings;

use JsonException;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapCache;
use RuntimeException;

class Settings
{
    /*
     * 全設定を取得
     */
    public static function all(): array
    {
        return self::locked(LOCK_SH, static fn() => self::mergeKnownValues(self::defaults(), self::read()));
    }

    /*
     * 指定された設定グループを取得
     */
    public static function section(
        string $section
    ): array {
        $settings = self::all();

        $values = $settings[$section] ?? [];

        return is_array($values)
            ? $values
            : [];
    }

    /*
     * 設定グループを保存
     */
    public static function saveSection(
        string $section,
        array $values
    ): void {
        $defaults = self::defaults();

        if (!isset($defaults[$section])) {
            throw new RuntimeException(
                '保存対象の設定が存在しません。'
            );
        }

        self::locked(LOCK_EX, static function () use ($defaults, $section, $values): void {
            $settings = self::mergeKnownValues($defaults, self::read());
            $settings[$section] = self::mergeKnownValues($defaults[$section], $values);
            self::write($settings);
        });
        SitemapCache::invalidate();
    }

    /*
     * 初期設定を取得
     */
    private static function defaults(): array
    {
        $filePath =
            NX_XML_HTML_SITEMAP_PATH
            . '/config/defaults.php';

        if (!is_file($filePath)) {
            return [];
        }

        $defaults = require $filePath;

        return is_array($defaults)
            ? $defaults
            : [];
    }

    /*
     * 保存済み設定を読み込む
     */
    private static function read(): array
    {
        $filePath = self::filePath();

        if (!is_file($filePath)) {
            return [];
        }

        if (is_link($filePath) || filesize($filePath) > 4194304) { throw new RuntimeException('設定ファイルの保存先・容量を確認してください。'); }
        $json = file_get_contents($filePath);

        if ($json === false || trim($json) === '') {
            throw new RuntimeException('設定ファイルを読み込めません。');
        }

        try {
            $settings = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('設定JSONが壊れています。元のファイルを保管して復旧してください。', 0, $exception);
        }
        if (!is_array($settings)) { throw new RuntimeException('設定JSONの形式が不正です。'); }
        self::assertTypes(self::defaults(), $settings);
        return $settings;
    }

    /*
     * 設定をJSONファイルへ保存
     */
    private static function write(
        array $settings
    ): void {
        $directory = self::directoryPath();

        if (
            !is_dir($directory) &&
            !mkdir($directory, 0755, true)
        ) {
            throw new RuntimeException(
                '設定保存フォルダを作成できません。'
            );
        }

        try {
            $json = json_encode(
                $settings,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES |
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            throw new RuntimeException(
                '設定データを変換できません。'
            );
        }

        if (strlen($json . PHP_EOL) > 4194304) { throw new RuntimeException('設定ファイルの容量上限を超えました。'); }
        $temporary = tempnam($directory, '.settings-');
        try {
            if ($temporary === false || realpath(dirname($temporary)) !== realpath($directory)
                || file_put_contents($temporary, $json . PHP_EOL) !== strlen($json . PHP_EOL)
                || !rename($temporary, self::filePath())) { throw new RuntimeException('設定を保存できません。'); }
            @chmod(self::filePath(), 0600);
        } finally { if (is_string($temporary) && is_file($temporary)) { unlink($temporary); } }
    }

    private static function locked(int $mode, callable $callback)
    {
        $directory = self::directoryPath();
        if (is_link(BASE_PATH . '/storage') || is_link(BASE_PATH . '/storage/extensions') || is_link($directory)) { throw new RuntimeException('設定保存先が不正です。'); }
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) { throw new RuntimeException('設定保存先を作成できません。'); }
        $path = self::filePath() . '.lock';
        if (is_link($path)) { throw new RuntimeException('設定ロックの保存先が不正です。'); }
        $lock = fopen($path, 'c');
        if (!$lock) { throw new RuntimeException('設定ロックを開けません。'); }
        try { if (!flock($lock, $mode)) { throw new RuntimeException('設定をロックできません。'); } return $callback(); }
        finally { flock($lock, LOCK_UN); fclose($lock); }
    }

    private static function assertTypes(array $defaults, array $saved): void
    {
        foreach ($defaults as $key => $default) {
            if (!array_key_exists($key, $saved)) { continue; }
            $value = $saved[$key];
            if (gettype($default) !== gettype($value)) { throw new RuntimeException('設定項目の型が不正です: ' . $key); }
            if (is_array($default) && $default !== []) { self::assertTypes($default, $value); }
            elseif ($default === []) {
                foreach ($value as $item) { if (!is_string($item) && !is_int($item)) { throw new RuntimeException('除外設定の形式が不正です。'); } }
            }
        }
    }

    /*
     * 初期設定に存在する項目だけ反映
     */
    private static function mergeKnownValues(
        array $defaults,
        array $values
    ): array {
        $result = $defaults;

        foreach ($defaults as $key => $defaultValue) {
            if (!array_key_exists($key, $values)) {
                continue;
            }

            $value = $values[$key];

            if ($defaultValue === [] && is_array($value)) {
                $result[$key] = array_values($value);
                continue;
            }

            if (
                is_array($defaultValue) &&
                is_array($value)
            ) {
                $result[$key] =
                    self::mergeKnownValues(
                        $defaultValue,
                        $value
                    );

                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    /*
     * 設定保存フォルダ
     */
    private static function directoryPath(): string
    {
        return BASE_PATH
            . '/storage/extensions/'
            . NX_XML_HTML_SITEMAP_ID;
    }

    /*
     * 設定保存ファイル
     */
    private static function filePath(): string
    {
        return self::directoryPath()
            . '/settings.json';
    }
}
