<?php

namespace NexaPressXmlHtmlSitemap\Repository;

use app\Core\Database;

class PageRepository
{
    /*
     * 公開済み固定ページを取得
     */
    public static function published(): array
    {
        $pdo = Database::connect();

        $statement = $pdo->query(
            "
            SELECT *
            FROM pages
            WHERE status = 'published'
            ORDER BY published_at DESC
            "
        );

        $pages = $statement->fetchAll();

        return is_array($pages)
            ? $pages
            : [];
    }
}