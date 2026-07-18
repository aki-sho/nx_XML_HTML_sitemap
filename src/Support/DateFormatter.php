<?php

namespace NexaPressXmlHtmlSitemap\Support;

use DateTimeImmutable;
use Throwable;

class DateFormatter
{
    /*
     * データから最終更新日時を取得
     */
    public static function fromRecord(
        array $record
    ): ?string {
        $candidates = [
            $record['updated_at'] ?? null,
            $record['published_at'] ?? null,
            $record['created_at'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $formatted =
                self::format($candidate);

            if ($formatted !== null) {
                return $formatted;
            }
        }

        return null;
    }

    /*
     * XMLサイトマップ用の日付へ変換
     */
    public static function format(
        mixed $value
    ): ?string {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            $date =
                new DateTimeImmutable($value);
        } catch (Throwable) {
            return null;
        }

        return $date->format(
            DATE_ATOM
        );
    }
}