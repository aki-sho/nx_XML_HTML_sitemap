<?php

namespace NexaPressXmlHtmlSitemap\Repository;

class PostRepository
{
    /*
     * 公開済み投稿を取得
     */
    public static function published(): iterable
    {
        yield from ContentRepository::published('posts');
    }
}
