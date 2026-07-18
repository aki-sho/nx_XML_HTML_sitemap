<?php

namespace NexaPressXmlHtmlSitemap\Repository;

use app\Core\Database;

class CategoryRepository
{
    /*
     * 公開済み投稿が存在するカテゴリーを取得
     */
    public static function withPublishedPosts(): array
    {
        $pdo = Database::connect();

        $statement = $pdo->query(
            "
            SELECT
                categories.id,
                categories.name,
                categories.slug,
                categories.created_at,
                categories.updated_at,
                COUNT(posts.id) AS post_count
            FROM categories
            INNER JOIN posts
                ON posts.category_id = categories.id
                AND posts.status = 'published'
            GROUP BY
                categories.id,
                categories.name,
                categories.slug,
                categories.created_at,
                categories.updated_at
            ORDER BY categories.name ASC
            "
        );

        $categories = $statement->fetchAll();

        return is_array($categories)
            ? $categories
            : [];
    }
}