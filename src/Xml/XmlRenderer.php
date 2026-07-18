<?php

namespace NexaPressXmlHtmlSitemap\Xml;

use DOMDocument;
use NexaPressXmlHtmlSitemap\Sitemap\SitemapEntry;

class XmlRenderer
{
    /*
     * XMLサイトマップを生成
     */
    public function render(
        array $entries
    ): string {
        $document =
            new DOMDocument(
                '1.0',
                'UTF-8'
            );

        $document->formatOutput = true;

        $urlSet =
            $document->createElementNS(
                'http://www.sitemaps.org/schemas/sitemap/0.9',
                'urlset'
            );

        $document->appendChild(
            $urlSet
        );

        foreach ($entries as $entry) {
            if (
                !$entry instanceof SitemapEntry
            ) {
                continue;
            }

            $this->appendEntry(
                $document,
                $urlSet,
                $entry
            );
        }

        $xml = $document->saveXML();

        return is_string($xml)
            ? $xml
            : '';
    }

    /*
     * 1件分のURLを追加
     */
    private function appendEntry(
        DOMDocument $document,
        \DOMElement $urlSet,
        SitemapEntry $entry
    ): void {
        $urlElement =
            $document->createElement('url');

        $locationElement =
            $document->createElement('loc');

        $locationElement->appendChild(
            $document->createTextNode(
                $entry->url()
            )
        );

        $urlElement->appendChild(
            $locationElement
        );

        $lastModified =
            $entry->lastModified();

        if ($lastModified !== null) {
            $lastModifiedElement =
                $document->createElement(
                    'lastmod'
                );

            $lastModifiedElement->appendChild(
                $document->createTextNode(
                    $lastModified
                )
            );

            $urlElement->appendChild(
                $lastModifiedElement
            );
        }

        $urlSet->appendChild(
            $urlElement
        );
    }
}