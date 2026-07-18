<?php

namespace NexaPressXmlHtmlSitemap\Sitemap;

use NexaPressXmlHtmlSitemap\Provider\ProviderRegistry;
use Throwable;

class SitemapService
{
    private ProviderRegistry $registry;

    public function __construct(
        ProviderRegistry $registry
    ) {
        $this->registry = $registry;
    }

    /*
     * 有効な掲載対象を収集
     */
    public function collect(
        array $settings
    ): array {
        $entries = [];

        $registeredUrls = [];

        foreach (
            $this->registry->all()
            as $provider
        ) {
            $settingKey =
                'include_' . $provider->key();

            if (
                ($settings[$settingKey] ?? false)
                !== true
            ) {
                continue;
            }

            try {
                $providerEntries =
                    $provider->entries();
            } catch (Throwable) {
                continue;
            }

            foreach ($providerEntries as $entry) {
                if (
                    !$entry instanceof SitemapEntry
                ) {
                    continue;
                }

                $url = trim($entry->url());

                if (
                    $url === '' ||
                    isset($registeredUrls[$url])
                ) {
                    continue;
                }

                $registeredUrls[$url] = true;
                $entries[] = $entry;
            }
        }

        return $entries;
    }
}