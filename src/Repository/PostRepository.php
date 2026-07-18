<?php

namespace NexaPressXmlHtmlSitemap\Repository;

use app\Models\Post;

class PostRepository
{
    /*
     * 公開済み投稿を取得
     */
    public static function published(): array
    {
        $posts = Post::published();

        return is_array($posts)
            ? $posts
            : [];
    }
}