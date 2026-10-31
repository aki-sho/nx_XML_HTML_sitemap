<?php

namespace NexaPressXmlHtmlSitemap\Xml;

class XmlResponse
{
    /*
     * XMLレスポンスを出力
     */
    public static function send(
        string $xml
    ): void {
        header(
            'Content-Type: application/xml; charset=UTF-8'
        );

        header(
            'X-Content-Type-Options: nosniff'
        );

        echo $xml;
    }

    /** @param resource $stream */
    public static function sendStream($stream): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        fpassthru($stream);
    }
}
