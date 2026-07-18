<?php

namespace NexaPressXmlHtmlSitemap\Provider;

class CategoryProvider implements SitemapProviderInterface
{
    /*
     * 設定項目の識別名
     */
    public function key(): string
    {
        return 'categories';
    }

    /*
     * カテゴリーページを取得
     *
     * 現在のNexaPress本体には、
     * 公開カテゴリー一覧ページがないため出力しない
     */
    public function entries(): array
    {
        return [];
    }
}