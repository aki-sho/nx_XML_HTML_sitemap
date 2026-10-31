<?php

namespace NexaPressXmlHtmlSitemap\Repository;

class PageRepository
{
    /*
     * 公開済み固定ページを取得
     */
    public static function published(): iterable
    {
        yield from ContentRepository::published('pages');
    }
}
