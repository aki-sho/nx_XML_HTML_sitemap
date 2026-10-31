<?php

namespace NexaPressXmlHtmlSitemap\Html;

use NexaPressXmlHtmlSitemap\Sitemap\SitemapEntry;
use RuntimeException;

class HtmlEntrySorter
{
    private const CHUNK_SIZE = 500;
    private const MERGE_FAN_IN = 32;

    /* 小さな並び替え済みファイルをマージし、全件をメモリに保持しない。 */
    public function build(iterable $entries, string $directory, string $bundle): array
    {
        if (!preg_match('/^[a-f0-9]{32}$/D', $bundle)) {
            throw new RuntimeException('不正な生成IDです。');
        }

        $files = [];
        $runs = ['updated' => [], 'title' => []];
        $rows = [];
        $bytes = 0;
        $success = false;

        try {
            foreach ($entries as $entry) {
                if (!$entry instanceof SitemapEntry) {
                    continue;
                }

                $line = json_encode([$entry->url(), $entry->title(), $entry->type(), $entry->lastModified()],
                    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
                $bytes += strlen($line);

                if (strlen($line) >= 1048576 || $bytes > 67104768) {
                    throw new RuntimeException('HTML掲載データの容量が上限を超えました。');
                }

                $rows[] = self::row($line);

                if (count($rows) === self::CHUNK_SIZE) {
                    $this->flush($rows, $runs, $files, $directory, $bundle);
                    $rows = [];
                }
            }

            if ($rows !== [] || $runs['updated'] === []) {
                $this->flush($rows, $runs, $files, $directory, $bundle);
            }

            $result = [];

            foreach ($runs as $sort => $paths) {
                while (count($paths) > 1) {
                    $next = [];

                    foreach (array_chunk($paths, self::MERGE_FAN_IN) as $chunk) {
                        $path = $directory . '/.html-' . $bundle . '-' . count($files) . '.run';
                        $files[] = $path;
                        $this->merge($chunk, $path, $sort);
                        $next[] = $path;

                        foreach ($chunk as $old) {
                            @unlink($old);
                        }
                    }

                    $paths = $next;
                }

                $path = $directory . '/html-' . $bundle . '-' . $sort . '.cache';
                $files[] = $path;

                if (!@rename($paths[0], $path)) {
                    throw new RuntimeException('HTMLの並び替え結果を保存できません。');
                }

                $result[$sort] = $path;
            }

            $success = true;
            return $result;
        } finally {
            foreach ($files as $path) {
                if (!$success || str_ends_with($path, '.run')) {
                    @unlink($path);
                }
            }
        }
    }

    private function flush(array $rows, array &$runs, array &$files, string $directory, string $bundle): void
    {
        foreach (array_keys($runs) as $sort) {
            usort($rows, static fn($a, $b) => self::compare($a, $b, $sort));
            $path = $directory . '/.html-' . $bundle . '-' . count($files) . '.run';
            $files[] = $path;
            $stream = $this->create($path);

            try {
                foreach ($rows as $row) {
                    $this->append($stream, $row['line']);
                }
            } finally {
                fclose($stream);
            }

            $runs[$sort][] = $path;
        }
    }

    private function merge(array $paths, string $path, string $sort): void
    {
        $streams = [];
        $output = $this->create($path);
        $heap = new HtmlEntryHeap($sort);

        try {
            foreach ($paths as $index => $source) {
                $stream = @fopen($source, 'rb');

                if ($stream === false) {
                    throw new RuntimeException('HTMLの並び替え元を読み込めません。');
                }

                $streams[$index] = $stream;
                $line = fgets($stream, 1048576);

                if ($line !== false) {
                    $heap->insert(['row' => self::row($line), 'index' => $index]);
                }
            }

            while (!$heap->isEmpty()) {
                $item = $heap->extract();
                $this->append($output, $item['row']['line']);
                $line = fgets($streams[$item['index']], 1048576);

                if ($line !== false) {
                    $heap->insert(['row' => self::row($line), 'index' => $item['index']]);
                }
            }

            foreach ($streams as $stream) {
                if (!feof($stream)) {
                    throw new RuntimeException('HTMLの並び替え元を読み込めません。');
                }
            }
        } finally {
            fclose($output);

            foreach ($streams as $stream) {
                fclose($stream);
            }
        }
    }

    private static function row(string $line): array
    {
        $values = json_decode($line, true, 8, JSON_THROW_ON_ERROR);

        if (!is_array($values) || array_keys($values) !== [0, 1, 2, 3]
            || !is_string($values[0]) || !is_string($values[1]) || !is_string($values[2])
            || ($values[3] !== null && !is_string($values[3]))) {
            throw new RuntimeException('HTMLの掲載データが不正です。');
        }

        return ['line' => $line, 'url' => $values[0], 'title' => mb_strtolower($values[1], 'UTF-8'),
            'time' => $values[3] === null ? 0 : (strtotime($values[3]) ?: 0), 'home' => $values[2] === 'home'];
    }

    public static function compare(array $left, array $right, string $sort): int
    {
        $order = (int)$right['home'] <=> (int)$left['home'];

        if ($order === 0) {
            $order = $sort === 'title' ? strnatcasecmp($left['title'], $right['title'])
                : $right['time'] <=> $left['time'];
        }

        return $order !== 0 ? $order : strcmp($left['url'], $right['url']);
    }

    /** @return resource */
    private function create(string $path)
    {
        $stream = @fopen($path, 'x+b');

        if ($stream === false) {
            throw new RuntimeException('HTMLの作業ファイルを作成できません。');
        }

        return $stream;
    }

    /** @param resource $stream */
    private function append($stream, string $line): void
    {
        if (@fwrite($stream, $line) !== strlen($line)) {
            throw new RuntimeException('HTMLの並び替え結果を書き込めません。');
        }
    }
}

class HtmlEntryHeap extends \SplHeap
{
    private string $sort;

    public function __construct(string $sort) { $this->sort = $sort; }

    protected function compare(mixed $left, mixed $right): int
    {
        return -HtmlEntrySorter::compare($left['row'], $right['row'], $this->sort);
    }
}
