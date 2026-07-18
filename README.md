# NX XML HTML Sitemap

NexaPressへXMLサイトマップとHTMLサイトマップを追加する拡張機能です。

検索エンジン向けのXMLサイトマップと、閲覧者向けのHTMLサイトマップを個別に設定できます。

![Version](https://img.shields.io/badge/version-v1.0.0-blue)
![NexaPress](https://img.shields.io/badge/NexaPress-1.9.1%2B-ff6b57)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?logo=php&logoColor=white)
![XML Sitemap](https://img.shields.io/badge/XML-Sitemap-2563eb)
![HTML Sitemap](https://img.shields.io/badge/HTML-Sitemap-e34f26?logo=html5&logoColor=white)

## バージョン

```text
1.0.0
```

## 対応環境

- NexaPress 1.9.1以上
- PHP 8.0以上
- PHP DOM拡張
- PHP ZipArchive

## 主な機能

### XMLサイトマップ

検索エンジン向けのXMLサイトマップを公開します。

公開URL：

```text
/sitemap.xml
```

掲載対象：

- トップページ
- 公開済み投稿
- 公開済み固定ページ
- カテゴリー

掲載対象は管理画面から個別に有効・無効を切り替えられます。

### HTMLサイトマップ

閲覧者向けのHTMLサイトマップを公開します。

公開URL：

```text
/sitemap
```

表示対象：

- トップページ
- 公開済み投稿
- 公開済み固定ページ
- カテゴリー

HTMLサイトマップのページタイトルも変更できます。

## カテゴリーについて

現在のNexaPress本体には、公開カテゴリー一覧ページがありません。

そのため、現在のバージョンではカテゴリー設定を有効にしても、カテゴリーURLはサイトマップへ出力されません。

NexaPress本体へ公開カテゴリーページが追加された後に対応できる構成にしています。

## 設定画面

拡張機能の管理画面では、次の設定を個別に保存できます。

```text
XMLサイトマップ
├─ XMLサイトマップを有効にする
├─ トップページを掲載する
├─ 投稿を掲載する
├─ 固定ページを掲載する
├─ カテゴリーを掲載する
└─ XML設定を保存

HTMLサイトマップ
├─ HTMLサイトマップを有効にする
├─ ページタイトル
├─ トップページを表示する
├─ 投稿一覧を表示する
├─ 固定ページ一覧を表示する
├─ カテゴリー一覧を表示する
└─ HTML設定を保存
```

## インストール方法

1. 配布用ZIPを用意します。
2. NexaPressの管理画面へログインします。
3. 「拡張機能」を開きます。
4. ZIPファイルをアップロードします。
5. 「NX XML HTML Sitemap」を有効化します。
6. 拡張機能メニューから「サイトマップ」を開きます。
7. XMLサイトマップとHTMLサイトマップを設定します。

## 設定データの保存場所

設定データは、拡張機能フォルダ外へ保存されます。

```text
storage/
└─ extensions/
   └─ nx_XML_HTML_sitemap/
      └─ settings.json
```

そのため、拡張機能本体を更新しても設定データは維持されます。

## ファイル構成

```text
nx_XML_HTML_sitemap/
├─ manifest.json
├─ bootstrap.php
├─ autoload.php
├─ hooks.php
├─ build.php
├─ README.md
│
├─ config/
│  ├─ defaults.php
│  └─ admin-sections.php
│
├─ routes/
│  ├─ xml.php
│  ├─ html.php
│  └─ settings.php
│
├─ src/
│  ├─ Controller/
│  │  ├─ XmlSitemapController.php
│  │  └─ HtmlSitemapController.php
│  │
│  ├─ Admin/
│  │  ├─ SettingsController.php
│  │  └─ Notice.php
│  │
│  ├─ Settings/
│  │  ├─ Settings.php
│  │  └─ SettingsValidator.php
│  │
│  ├─ Sitemap/
│  │  ├─ SitemapEntry.php
│  │  └─ SitemapService.php
│  │
│  ├─ Provider/
│  │  ├─ SitemapProviderInterface.php
│  │  ├─ ProviderRegistry.php
│  │  ├─ HomeProvider.php
│  │  ├─ PostProvider.php
│  │  ├─ PageProvider.php
│  │  └─ CategoryProvider.php
│  │
│  ├─ Repository/
│  │  ├─ PostRepository.php
│  │  ├─ PageRepository.php
│  │  └─ CategoryRepository.php
│  │
│  ├─ Xml/
│  │  ├─ XmlRenderer.php
│  │  └─ XmlResponse.php
│  │
│  ├─ Html/
│  │  └─ HtmlViewData.php
│  │
│  └─ Support/
│     ├─ UrlBuilder.php
│     ├─ DateFormatter.php
│     └─ ExclusionPolicy.php
│
├─ admin/
│  ├─ dashboard.php
│  ├─ sections/
│  │  ├─ xml.php
│  │  └─ html.php
│  └─ partials/
│     ├─ notice.php
│     └─ content-switches.php
│
├─ views/
│  └─ html/
│     ├─ sitemap.php
│     └─ sections/
│        ├─ home.php
│        ├─ posts.php
│        ├─ pages.php
│        └─ categories.php
│
└─ assets/
   ├─ css/
   │  ├─ admin-base.css
   │  ├─ admin-tabs.css
   │  ├─ admin-forms.css
   │  └─ public-sitemap.css
   └─ js/
      └─ admin-tabs.js
```

## 配布用ZIPの作成

プロジェクトフォルダへ移動します。

```bat
cd /d "D:\github作業フォルダ\nx_XML_HTML_sitemap"
```

PHPファイルを一括で構文チェックします。

```bat
for /R %f in (*.php) do @php -l "%f"
```

すべてのPHPファイルで次のように表示されることを確認します。

```text
No syntax errors detected
```

`manifest.json`を確認します。

```bat
php -r "$j=file_get_contents('manifest.json'); json_decode($j,true,512,JSON_THROW_ON_ERROR); echo 'manifest.json OK'.PHP_EOL;"
```

正常な場合は次のように表示されます。

```text
manifest.json OK
```

配布用ZIPを作成します。

```bat
php build.php
```

正常に完了すると、次のZIPが作成されます。

```text
dist\nx_XML_HTML_sitemap-1.0.0.zip
```

作成されたZIPを確認します。

```bat
dir dist
```

ZIP内部を確認します。

```bat
tar -tf "dist\nx_XML_HTML_sitemap-1.0.0.zip"
```

## ZIP内部の構成

```text
nx_XML_HTML_sitemap/
├─ manifest.json
├─ bootstrap.php
├─ autoload.php
├─ hooks.php
├─ README.md
├─ config/
├─ routes/
├─ src/
├─ admin/
├─ views/
└─ assets/
```

`build.php`と`dist`フォルダは、配布用ZIPには含まれません。

## 動作確認

次の順番で確認してください。

1. NexaPress管理画面の「拡張機能」を開く
2. `nx_XML_HTML_sitemap-1.0.0.zip`をアップロードする
3. NX XML HTML Sitemapを有効化する
4. 拡張機能メニューの「サイトマップ」を開く
5. XMLサイトマップ設定を保存する
6. HTMLサイトマップ設定を保存する
7. `/sitemap.xml`を開く
8. XMLが正常に表示されることを確認する
9. `/sitemap`を開く
10. HTMLサイトマップが表示されることを確認する
11. 公開済み投稿が表示されることを確認する
12. 公開済み固定ページが表示されることを確認する
13. 下書きが表示されないことを確認する
14. 各掲載設定の有効・無効を確認する
15. HTMLサイトマップのタイトル変更を確認する

## 拡張機能の更新について

`manifest.json`には、将来の拡張機能更新機能で使用する更新元情報を含めています。

```json
"update": {
  "provider": "github",
  "repository": "aki-sho/nx_XML_HTML_sitemap",
  "asset": "nx_XML_HTML_sitemap-{version}.zip"
}
```

現在のNexaPress本体には、拡張機能を個別に自動更新する処理はありません。

今後、本体側へ拡張機能更新機能を追加した際に、GitHub Releasesから更新情報と配布用ZIPを取得できる構成にしています。

## 注意事項

### XMLサイトマップについて

XMLサイトマップへ掲載されるのは、公開状態の投稿と固定ページです。

下書き状態のコンテンツは掲載されません。

### カテゴリーについて

現在は公開カテゴリーページが存在しないため、カテゴリーURLは掲載されません。

### 設定ファイルについて

設定ファイルを直接編集すると、設定画面と実際の動作が一致しなくなる場合があります。

通常はNexaPressの管理画面から設定してください。

### 動作確認について

本番環境へ導入する前に、ローカル環境または検証環境で動作を確認してください。