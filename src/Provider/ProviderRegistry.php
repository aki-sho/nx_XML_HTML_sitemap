<?php

namespace NexaPressXmlHtmlSitemap\Provider;

class ProviderRegistry
{
    private array $providers = [];

    /*
     * 標準Providerを登録
     */
    public static function createDefault(array $settings = []): self
    {
        $registry = new self();

        $registry->register(
            new HomeProvider()
        );

        $registry->register(
            new PostProvider($settings)
        );

        $registry->register(
            new PageProvider($settings)
        );

        $registry->register(
            new CategoryProvider()
        );

        /*
         * 他の拡張機能からProviderを追加可能にする
         */
        if (function_exists('do_action')) {
            do_action(
                'nx_sitemap_register_providers',
                $registry
            );
        }

        return $registry;
    }

    /*
     * Providerを登録
     */
    public function register(
        SitemapProviderInterface $provider
    ): void {
        $key = trim($provider->key());

        if ($key === '') {
            return;
        }

        $this->providers[$key] = $provider;
    }

    /*
     * 登録済みProviderを取得
     */
    public function all(): array
    {
        return array_values(
            $this->providers
        );
    }
}
