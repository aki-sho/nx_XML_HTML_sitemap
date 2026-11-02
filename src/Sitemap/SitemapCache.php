<?php

namespace NexaPressXmlHtmlSitemap\Sitemap;

use NexaPressXmlHtmlSitemap\Html\HtmlEntrySorter;
use NexaPressXmlHtmlSitemap\Provider\ProviderRegistry;
use NexaPressXmlHtmlSitemap\Xml\XmlRenderer;
use NexaPressXmlHtmlSitemap\Xml\XmlSitemapBuilder;
use RuntimeException;

class SitemapCache
{
    public const TTL = 300;
    private const MAX_BYTES = 67108864;
    private const MAX_ENTRY_BYTES = 1048576;

    /** 先頭の管理情報を読み飛ばした本文ストリームを返す。 @return resource */
    public static function open(string $format, array $settings)
    {
        if (!in_array($format, ['xml', 'html'], true)) {
            throw new RuntimeException('不正なキャッシュ種別です。');
        }

        $directory = self::directory();

        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('キャッシュ保存先を作成できません。');
        }

        $key = hash('sha256', json_encode([3, $settings, \NexaPressXmlHtmlSitemap\Support\UrlBuilder::home()], JSON_THROW_ON_ERROR));
        $path = $directory . '/' . $format . '.cache';
        $cached = self::fresh($path, $key, self::generation());

        if ($cached !== null) {
            return $cached;
        }

        $lock = @fopen($directory . '/generation.lock', 'c');

        if ($lock === false) {
            throw new RuntimeException('生成ロックを開けません。');
        }

        $temporary = null;
        $stream = null;
        $generated = [];
        $published = false;

        try {
            if (!flock($lock, LOCK_EX | LOCK_NB)) {
                throw new RuntimeException('サイトマップを生成中です。');
            }

            $generation = self::generation();
            $cached = self::fresh($path, $key, $generation);

            if ($cached !== null) {
                return $cached;
            }

            $temporary = @tempnam($directory, '.pending-');

            if ($temporary === false || realpath(dirname($temporary)) !== realpath($directory)) {
                throw new RuntimeException('キャッシュの作業ファイルを作成できません。');
            }

            $stream = @fopen($temporary, 'w+b');

            if ($stream === false) {
                throw new RuntimeException('キャッシュの作業ファイルを作成できません。');
            }

            $bundle = bin2hex(random_bytes(16));
            $service = new SitemapService(ProviderRegistry::createDefault($settings));
            $entries = $service->iterate($settings);

            if ($format === 'xml') {
                $limit = self::number($settings['urls_per_file'] ?? 50000, 1, 50000, 50000);
                $generated = (new XmlSitemapBuilder($limit))->build($entries, $directory, $bundle);
            } else {
                $variants = (new HtmlEntrySorter())->build($entries, $directory, $bundle);
                $generated = array_values($variants);
            }

            self::append($stream, json_encode([
                'key' => $key, 'generation' => $generation, 'bundle' => $bundle,
                'parts' => $format === 'xml' ? count($generated) : 0,
                'expires' => time() + self::number($settings['cache_ttl'] ?? self::TTL, 60, 3600, self::TTL),
            ], JSON_THROW_ON_ERROR) . "\n");

            if ($format === 'xml' && count($generated) > 1) {
                (new XmlRenderer())->writeIndex(count($generated), $stream);
            } else {
                self::copy($format === 'xml' ? $generated[0] : $variants['updated'], $stream);
            }

            if (ftell($stream) > self::MAX_BYTES || !@fflush($stream)
                || self::generation() !== $generation) {
                throw new RuntimeException('生成結果の容量超過、または生成中にサイト情報が更新されました。');
            }

            fclose($stream);
            $stream = null;

            if (!@rename($temporary, $path)) {
                throw new RuntimeException('キャッシュを保存できません。');
            }

            $temporary = null;
            $published = true;
            self::cleanup($format, $bundle);

            if ($format === 'html') {
                @unlink($variants['updated']);
            }

            $cached = self::fresh($path, $key, self::generation());

            if ($cached === null) {
                throw new RuntimeException('キャッシュを読み込めません。');
            }

            return $cached;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }

            if (is_string($temporary)) {
                @unlink($temporary);
            }

            if (!$published) {
                foreach ($generated as $file) {
                    @unlink($file);
                }
            }

            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /** @return resource */
    public static function openHtml(array $settings, string $sort)
    {
        $stream = self::open('html', $settings);

        if ($sort !== 'title') {
            return $stream;
        }

        $meta = self::metadata($stream);
        fclose($stream);
        return self::openData(self::directory() . '/html-' . $meta['bundle'] . '-title.cache', self::MAX_BYTES);
    }

    /** @return resource|null */
    public static function openPart(array $settings, string $part)
    {
        if (!preg_match('/^[1-9][0-9]{0,4}$/D', $part) || (int)$part > 50000) {
            return null;
        }

        $stream = self::open('xml', $settings);
        $meta = self::metadata($stream);
        fclose($stream);

        if ((int)$part > $meta['parts']) {
            return null;
        }

        return self::openData(self::directory() . '/xml-' . $meta['bundle'] . '-' . $part . '.xml',
            XmlRenderer::MAX_BYTES);
    }

    /** @param resource $stream */
    public static function readEntries($stream): iterable
    {
        $count = 0;

        while (($line = fgets($stream, self::MAX_ENTRY_BYTES)) !== false) {
            $values = json_decode($line, true, 8, JSON_THROW_ON_ERROR);

            if (++$count > SitemapService::MAX_ENTRIES || !is_array($values)
                || array_keys($values) !== [0, 1, 2, 3]
                || !is_string($values[0] ?? null) || !is_string($values[1] ?? null)
                || !is_string($values[2] ?? null)
                || ($values[3] !== null && !is_string($values[3]))) {
                throw new RuntimeException('キャッシュの掲載データが不正です。');
            }

            yield new SitemapEntry($values[0], $values[1], $values[2], $values[3]);
        }

        if (!feof($stream)) {
            throw new RuntimeException('掲載データを読み込めません。');
        }
    }

    public static function invalidate(): void
    {
        $directory = self::directory();

        if (!is_dir($directory)) {
            return;
        }

        $temporary = @tempnam($directory, '.generation-');

        if ($temporary === false) {
            throw new RuntimeException('キャッシュを無効化できません。');
        }

        try {
            if (realpath(dirname($temporary)) !== realpath($directory)) {
                throw new RuntimeException('キャッシュを無効化できません。');
            }

            $version = bin2hex(random_bytes(16));

            if (@file_put_contents($temporary, $version) !== strlen($version)
                || !@rename($temporary, $directory . '/generation')) {
                throw new RuntimeException('キャッシュを無効化できません。');
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }

    /** @return resource|null */
    private static function fresh(string $path, string $key, string $generation)
    {
        $stream = @fopen($path, 'rb');

        if ($stream === false) {
            return null;
        }

        $meta = self::metadata($stream);
        $stat = fstat($stream);

        if (!is_array($meta) || ($meta['key'] ?? null) !== $key
            || ($meta['generation'] ?? null) !== $generation
            || !is_int($meta['expires'] ?? null) || $meta['expires'] <= time()
            || !is_string($meta['bundle'] ?? null) || !preg_match('/^[a-f0-9]{32}$/D', $meta['bundle'])
            || !is_int($meta['parts'] ?? null) || $meta['parts'] < 0 || $meta['parts'] > 50000
            || $stat === false || $stat['size'] > self::MAX_BYTES) {
            fclose($stream);
            return null;
        }

        return $stream;
    }

    /** @param resource $stream */
    private static function metadata($stream): ?array
    {
        rewind($stream);
        $line = fgets($stream, 2048);
        $meta = is_string($line) ? json_decode($line, true) : null;
        return is_array($meta) ? $meta : null;
    }

    /** @return resource */
    private static function openData(string $path, int $maxBytes)
    {
        $stream = @fopen($path, 'rb');

        if ($stream === false) {
            throw new RuntimeException('生成ファイルを読み込めません。');
        }

        $stat = fstat($stream);

        if ($stat === false || $stat['size'] > $maxBytes) {
            fclose($stream);
            throw new RuntimeException('生成ファイルの容量が不正です。');
        }

        return $stream;
    }

    /** @param resource $stream */
    private static function copy(string $path, $stream): void
    {
        $source = self::openData($path, self::MAX_BYTES);

        try {
            if (stream_copy_to_stream($source, $stream) === false) {
                throw new RuntimeException('生成ファイルを読み込めません。');
            }
        } finally {
            fclose($source);
        }
    }

    private static function cleanup(string $format, string $keep): void
    {
        foreach (new \DirectoryIterator(self::directory()) as $file) {
            $pattern = $format === 'xml' ? '/^xml-([a-f0-9]{32})-[1-9][0-9]{0,4}\\.xml$/D'
                : '/^html-([a-f0-9]{32})-(updated|title)\\.cache$/D';

            if (preg_match($pattern, $file->getFilename(), $match) && $match[1] !== $keep) {
                @unlink($file->getPathname());
            }
        }
    }

    /** @param resource $stream */
    private static function append($stream, string $value): void
    {
        $position = ftell($stream);

        if ($position === false || $position + strlen($value) > self::MAX_BYTES
            || @fwrite($stream, $value) !== strlen($value)) {
            throw new RuntimeException('キャッシュを書き込めません。');
        }
    }

    private static function number(mixed $value, int $min, int $max, int $default): int
    {
        $number = filter_var($value, FILTER_VALIDATE_INT);
        return $number === false ? $default : max($min, min($max, $number));
    }

    private static function generation(): string
    {
        $path = self::directory() . '/generation';

        if (!is_file($path)) {
            return '';
        }

        $value = @file_get_contents($path);

        if ($value === false) {
            throw new RuntimeException('キャッシュの世代を読み込めません。');
        }

        return hash('sha256', $value);
    }

    private static function directory(): string
    {
        return BASE_PATH . '/storage/extensions/' . NX_XML_HTML_SITEMAP_ID . '/cache';
    }
}
