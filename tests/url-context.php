<?php
namespace NexaPressXmlHtmlSitemap\Support {
    function url(string $path = ''): string { return '/subdir/' . $path; }
    function post_url(array $post): string { return '/subdir/post/' . $post['slug']; }
    function page_url(array $page): string { return '/subdir/page/' . $page['slug']; }
}
namespace {
    if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
    require dirname(__DIR__) . '/autoload.php';
    use NexaPressXmlHtmlSitemap\Support\UrlBuilder;
    $_SERVER['HTTP_HOST']='example.test';$_SERVER['HTTPS']='on';
    if (UrlBuilder::home() !== 'https://example.test/subdir/' || UrlBuilder::post(['slug'=>'hello']) !== 'https://example.test/subdir/post/hello') { throw new \RuntimeException('Relative core URLs must become absolute XML URLs'); }
    $first=UrlBuilder::home();$_SERVER['HTTP_HOST']='other.test';
    if ($first===UrlBuilder::home()) { throw new \RuntimeException('Cache origins must differ by host'); }
    $_SERVER['HTTP_HOST']='user@bad.test';$rejected=false;
    try { UrlBuilder::home(); } catch(\RuntimeException $e) { $rejected=true; }
    if (!$rejected) { throw new \RuntimeException('Malformed host must fail closed'); }
    echo "Sitemap URL-context checks: 4 passed\n";
}
