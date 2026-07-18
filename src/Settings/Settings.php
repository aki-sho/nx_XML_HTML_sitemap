<?php

namespace NexaPressXmlHtmlSitemap\Settings;

use JsonException;
use RuntimeException;

class Settings
{
    /*
     * 全設定を取得
     */
    public static function all(): array
    {
        return self::mergeKnownValues(
            self::defaults(),
            self::read()
        );
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

        $settings = self::all();

        $settings[$section] =
            self::mergeKnownValues(
                $defaults[$section],
                $values
            );

        self::write($settings);
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

        $json = file_get_contents($filePath);

        if ($json === false || trim($json) === '') {
            return [];
        }

        try {
            $settings = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            return [];
        }

        return is_array($settings)
            ? $settings
            : [];
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

        if (
            file_put_contents(
                self::filePath(),
                $json . PHP_EOL,
                LOCK_EX
            ) === false
        ) {
            throw new RuntimeException(
                '設定を保存できません。'
            );
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