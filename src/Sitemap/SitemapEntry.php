<?php

namespace NexaPressXmlHtmlSitemap\Sitemap;

class SitemapEntry
{
    private string $url;

    private string $title;

    private string $type;

    private ?string $lastModified;

    public function __construct(
        string $url,
        string $title,
        string $type,
        ?string $lastModified = null
    ) {
        $this->url = $url;
        $this->title = $title;
        $this->type = $type;
        $this->lastModified = $lastModified;
    }

    /*
     * URLを取得
     */
    public function url(): string
    {
        return $this->url;
    }

    /*
     * 表示名を取得
     */
    public function title(): string
    {
        return $this->title;
    }

    /*
     * コンテンツ種別を取得
     */
    public function type(): string
    {
        return $this->type;
    }

    /*
     * 最終更新日時を取得
     */
    public function lastModified(): ?string
    {
        return $this->lastModified;
    }
}