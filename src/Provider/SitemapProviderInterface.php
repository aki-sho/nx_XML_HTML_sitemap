<?php

namespace NexaPressXmlHtmlSitemap\Provider;

interface SitemapProviderInterface
{
    /*
     * 設定項目と対応する識別名
     */
    public function key(): string;

    /*
     * サイトマップ掲載データを取得
     */
    public function entries(): array;
}