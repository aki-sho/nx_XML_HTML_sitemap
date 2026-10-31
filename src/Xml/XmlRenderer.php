<?php

namespace NexaPressXmlHtmlSitemap\Xml;

use LengthException;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapEntry;
use RuntimeException;

class XmlRenderer
{
    public const MAX_BYTES = 52428800;
    public const HEADER = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    public const FOOTER = '</urlset>' . "\n";

    /*
     * 既存の文字列生成API。公開ルートではwrite()で直接ファイルへ出力する。
     */
    public function render(iterable $entries): string
    {
        $stream = fopen('php://temp/maxmemory:2097152', 'w+b');

        if ($stream === false) {
            throw new RuntimeException('XMLの出力先を作成できません。');
        }

        try {
            $this->write($entries, $stream);
            rewind($stream);
            $xml = stream_get_contents($stream);

            if ($xml === false) {
                throw new RuntimeException('XMLを読み込めません。');
            }

            return $xml;
        } finally {
            fclose($stream);
        }
    }

    /** @param resource $stream */
    public function write(iterable $entries, $stream): void
    {
        $bytes = 0;
        $count = 0;
        $this->append($stream, self::HEADER, $bytes);

        foreach ($entries as $entry) {
            if (!$entry instanceof SitemapEntry) {
                continue;
            }

            if (++$count > 50000) {
                throw new LengthException('サイトマップのURL数が上限を超えました。');
            }

            $this->append($stream, $this->entryXml($entry), $bytes);
        }

        $this->append($stream, self::FOOTER, $bytes);
    }

    public function entryXml(SitemapEntry $entry): string
    {
        $xml = '  <url><loc>' . $this->escape($entry->url()) . '</loc>';

        if ($entry->lastModified() !== null) {
            $xml .= '<lastmod>' . $this->escape($entry->lastModified()) . '</lastmod>';
        }

        return $xml . '</url>' . "\n";
    }

    /** @param resource $stream */
    public function writeIndex(int $parts, $stream): void
    {
        if ($parts < 1 || $parts > 50000) {
            throw new LengthException('XMLの分割数が上限を超えました。');
        }

        $bytes = 0;
        $this->append($stream, '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n", $bytes);

        for ($part = 1; $part <= $parts; $part++) {
            $this->append($stream, '<sitemap><loc>' . $this->escape(url('sitemap-' . $part . '.xml'))
                . '</loc></sitemap>' . "\n", $bytes);
        }

        $this->append($stream, '</sitemapindex>' . "\n", $bytes);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED,
            'UTF-8'
        );
    }

    /** @param resource $stream */
    private function append($stream, string $chunk, int &$bytes): void
    {
        $length = strlen($chunk);

        if ($bytes + $length > self::MAX_BYTES) {
            throw new LengthException('サイトマップの容量が上限を超えました。');
        }

        if (@fwrite($stream, $chunk) !== $length) {
            throw new RuntimeException('XMLを書き込めません。');
        }

        $bytes += $length;
    }
}
