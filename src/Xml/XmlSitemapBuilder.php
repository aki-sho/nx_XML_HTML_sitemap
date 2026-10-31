<?php

namespace NexaPressXmlHtmlSitemap\Xml;

use InvalidArgumentException;
use LengthException;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapEntry;
use RuntimeException;
use Throwable;

class XmlSitemapBuilder
{
    private int $maxUrls;
    private int $maxBytes;

    public function __construct(int $maxUrls = 50000, int $maxBytes = XmlRenderer::MAX_BYTES)
    {
        if ($maxUrls < 1 || $maxUrls > 50000 || $maxBytes > XmlRenderer::MAX_BYTES
            || $maxBytes <= strlen(XmlRenderer::HEADER . XmlRenderer::FOOTER)) {
            throw new InvalidArgumentException('XMLの分割条件が不正です。');
        }

        $this->maxUrls = $maxUrls;
        $this->maxBytes = $maxBytes;
    }

    /* 全パートが完成するまで、公開キャッシュから参照されない固有名を使う。 */
    public function build(iterable $entries, string $directory, string $bundle): array
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $bundle)) {
            throw new InvalidArgumentException('不正な生成IDです。');
        }

        $files = [];
        $stream = null;
        $count = 0;
        $bytes = 0;
        $renderer = new XmlRenderer();

        try {
            foreach ($entries as $entry) {
                if (!$entry instanceof SitemapEntry) {
                    continue;
                }

                $fragment = $renderer->entryXml($entry);

                if (strlen(XmlRenderer::HEADER . $fragment . XmlRenderer::FOOTER) > $this->maxBytes) {
                    throw new LengthException('1件のURLデータがXMLの容量上限を超えています。');
                }

                if ($stream !== null && ($count >= $this->maxUrls
                    || $bytes + strlen($fragment . XmlRenderer::FOOTER) > $this->maxBytes)) {
                    $this->append($stream, XmlRenderer::FOOTER);
                    fclose($stream);
                    $stream = null;
                }

                if ($stream === null) {
                    [$stream, $path] = $this->start($directory, $bundle, count($files) + 1);
                    $files[] = $path;
                    $count = 0;
                    $bytes = strlen(XmlRenderer::HEADER);
                }

                $this->append($stream, $fragment);
                $bytes += strlen($fragment);
                $count++;
            }

            if ($stream === null) {
                [$stream, $path] = $this->start($directory, $bundle, 1);
                $files[] = $path;
            }

            $this->append($stream, XmlRenderer::FOOTER);
            fclose($stream);
            $stream = null;
            return $files;
        } catch (Throwable $exception) {
            if (is_resource($stream)) {
                fclose($stream);
            }

            foreach ($files as $path) {
                @unlink($path);
            }

            throw $exception;
        }
    }

    private function start(string $directory, string $bundle, int $part): array
    {
        if ($part > 50000) {
            throw new LengthException('XMLの分割数が上限を超えました。');
        }

        $path = $directory . '/xml-' . $bundle . '-' . $part . '.xml';
        $stream = @fopen($path, 'x+b');

        if ($stream === false) {
            throw new RuntimeException('分割XMLを作成できません。');
        }

        try {
            $this->append($stream, XmlRenderer::HEADER);
        } catch (Throwable $exception) {
            fclose($stream);
            @unlink($path);
            throw $exception;
        }

        return [$stream, $path];
    }

    /** @param resource $stream */
    private function append($stream, string $value): void
    {
        if (@fwrite($stream, $value) !== strlen($value)) {
            throw new RuntimeException('分割XMLを書き込めません。');
        }
    }
}
