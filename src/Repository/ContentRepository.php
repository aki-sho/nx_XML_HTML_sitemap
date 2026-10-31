<?php

namespace NexaPressXmlHtmlSitemap\Repository;

use app\Core\Database;
use InvalidArgumentException;
use PDO;

class ContentRepository
{
    private const BATCH_SIZE = 500;

    /*
     * 本文を取得せず、公開日時とIDをカーソルにして分割取得する。
     * PDOが結果をバッファリングする環境でも1回の取得量を制限する。
     */
    public static function published(string $type): iterable
    {
        if (!in_array($type, ['posts', 'pages'], true)) {
            throw new InvalidArgumentException('不正な掲載対象です。');
        }

        $pdo = Database::connect();
        $table = Database::table($type);
        $columns = 'p.id, p.title, p.slug, p.status, '
            . 'p.published_at, p.created_at, p.updated_at';
        $join = '';

        $metadata = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite'
            ? $pdo->query("PRAGMA table_info({$table})")->fetchAll(PDO::FETCH_ASSOC)
            : $pdo->query("SHOW COLUMNS FROM {$table} LIKE 'noindex'")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($metadata as $column) {
            if (($column['name'] ?? $column['Field'] ?? '') === 'noindex') {
                $columns .= ', p.noindex';
                break;
            }
        }

        if ($type === 'posts') {
            $columns .= ', c.slug AS category_slug';
            $categories = Database::table('categories');
            $join = " LEFT JOIN {$categories} c ON c.id = p.category_id";
        }

        $last = null;

        do {
            $where = " WHERE p.status = 'published' AND p.deleted_at IS NULL";
            $params = [];

            if ($last !== null) {
                $params[':last_id'] = (int)$last['id'];

                if ($last['published_at'] === null) {
                    $where .= ' AND p.published_at IS NULL AND p.id < :last_id';
                } else {
                    $where .= ' AND (p.published_at < :last_date '
                        . 'OR (p.published_at = :same_date AND p.id < :last_id) '
                        . 'OR p.published_at IS NULL)';
                    $params[':last_date'] = $last['published_at'];
                    $params[':same_date'] = $last['published_at'];
                }
            }

            $statement = $pdo->prepare(
                "SELECT {$columns} FROM {$table} p{$join}{$where}"
                . ' ORDER BY p.published_at DESC, p.id DESC LIMIT :batch_size'
            );

            foreach ($params as $name => $value) {
                $statement->bindValue(
                    $name,
                    $value,
                    is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
                );
            }

            $statement->bindValue(':batch_size', self::BATCH_SIZE, PDO::PARAM_INT);
            $statement->execute();
            $count = 0;

            try {
                while (($record = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
                    $last = $record;
                    $count++;
                    yield $record;
                }
            } finally {
                $statement->closeCursor();
            }
        } while ($count === self::BATCH_SIZE);
    }
}
