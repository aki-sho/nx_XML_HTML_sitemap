<?php

/*
 * NX XML HTML Sitemapのクラスを自動読み込み
 */
spl_autoload_register(
    static function (string $className): void {
        $namespace = 'NexaPressXmlHtmlSitemap\\';

        if (!str_starts_with($className, $namespace)) {
            return;
        }

        $relativeClass = substr(
            $className,
            strlen($namespace)
        );

        $relativePath = str_replace(
            '\\',
            DIRECTORY_SEPARATOR,
            $relativeClass
        );

        $filePath = __DIR__
            . '/src/'
            . $relativePath
            . '.php';

        if (is_file($filePath)) {
            require_once $filePath;
        }
    }
);