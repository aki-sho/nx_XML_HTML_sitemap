<?php

namespace NexaPressXmlHtmlSitemap\Admin;

use app\Core\Csrf;
use app\Core\Permission;
use InvalidArgumentException;
use NexaPressXmlHtmlSitemap\Settings\Settings;
use NexaPressXmlHtmlSitemap\Settings\SettingsValidator;
use Throwable;

class SettingsController
{
    /*
     * XMLサイトマップ設定を保存
     */
    public function saveXml(): void
    {
        Permission::require('extensions.manage');
        Csrf::requireValid($_POST['_csrf_token'] ?? null);

        try {
            $settings =
                SettingsValidator::xml($_POST);

            Settings::saveSection(
                'xml',
                $settings
            );

            Notice::success(
                'XMLサイトマップ設定を保存しました。'
            );
        } catch (InvalidArgumentException $exception) {
            Notice::error($exception->getMessage());
        } catch (Throwable) {
            Notice::error(
                'XMLサイトマップ設定を保存できませんでした。'
            );
        }

        $this->redirect();
    }

    /*
     * HTMLサイトマップ設定を保存
     */
    public function saveHtml(): void
    {
        Permission::require('extensions.manage');
        Csrf::requireValid($_POST['_csrf_token'] ?? null);

        try {
            $settings =
                SettingsValidator::html($_POST);

            Settings::saveSection(
                'html',
                $settings
            );

            Notice::success(
                'HTMLサイトマップ設定を保存しました。'
            );
        } catch (InvalidArgumentException $exception) {
            Notice::error($exception->getMessage());
        } catch (Throwable) {
            Notice::error(
                'HTMLサイトマップ設定を保存できませんでした。'
            );
        }

        $this->redirect();
    }

    /*
     * サイトマップ設定画面へ戻る
     */
    private function redirect(): void
    {
        redirect_to(
            'admin/extensions/'
            . NX_XML_HTML_SITEMAP_ID
            . '/dashboard'
        );
    }
}
