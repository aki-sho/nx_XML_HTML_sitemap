<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/autoload.php';

use NexaPressXmlHtmlSitemap\Provider\ProviderRegistry;
use NexaPressXmlHtmlSitemap\Provider\SitemapProviderInterface;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapEntry;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapService;
use NexaPressXmlHtmlSitemap\Xml\XmlRenderer;

$provider = new class implements SitemapProviderInterface {
    public int $emitted = 0;
    public function key(): string { return 'posts'; }
    public function entries(): iterable {
        for ($i = 0; $i < 50000; $i++) {
            $this->emitted++;
            yield new SitemapEntry('https://example.test/' . str_repeat('a', 80) . $i,
                'Page', 'post', '2026-10-31T00:00:00+09:00');
        }
    }
};
$registry = new ProviderRegistry();
$registry->register($provider);
$stream = tmpfile();
$start = microtime(true);
(new XmlRenderer())->write((new SitemapService($registry))->iterate(['include_posts' => true]), $stream);
echo json_encode([
    'entries' => $provider->emitted,
    'bytes' => ftell($stream),
    'peak_bytes' => memory_get_peak_usage(true),
    'seconds' => microtime(true) - $start,
], JSON_THROW_ON_ERROR);
fclose($stream);
