<?php

namespace NexaPressXmlHtmlSitemap\Sitemap;

use NexaPressXmlHtmlSitemap\Provider\ProviderRegistry;
use LengthException;
use NexaPressXmlHtmlSitemap\Support\ExclusionPolicy;

class SitemapService
{
    public const MAX_ENTRIES = 250000;
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
        return iterator_to_array($this->iterate($settings), false);
    }

    public function iterate(array $settings): iterable
    {
        $registeredUrls = [];
        $excludedUrls = ExclusionPolicy::urlRules($settings);

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

            $providerEntries = $provider->entries();

            foreach ($providerEntries as $entry) {
                if (
                    !$entry instanceof SitemapEntry
                ) {
                    continue;
                }

                $url = trim($entry->url());

                $urlKey = hash('sha256', $url, true);

                if (
                    $url === '' ||
                    isset($registeredUrls[$urlKey]) ||
                    !ExclusionPolicy::allowsUrl($url, $excludedUrls)
                ) {
                    continue;
                }

                $registeredUrls[$urlKey] = true;

                if (count($registeredUrls) > self::MAX_ENTRIES) {
                    throw new LengthException('サイトマップのURL数が上限を超えました。');
                }

                yield $entry;
            }
        }
    }
}
