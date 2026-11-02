# NX XML HTML Sitemap

> 最新バージョン: 1.0.3（2026-11-03）。公開状態の確認・URL出力・設定ファイルの保護を改善しました。

NexaPressへXMLサイトマップとHTMLサイトマップを追加する拡張機能です。

検索エンジン向けのXMLサイトマップと、閲覧者向けのHTMLサイトマップを個別に設定できます。

![Version](https://img.shields.io/badge/version-v1.0.3-blue)
![NexaPress](https://img.shields.io/badge/NexaPress-2.1.0%2B-ff6b57)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?logo=php&logoColor=white)
![XML Sitemap](https://img.shields.io/badge/XML-Sitemap-2563eb)
![HTML Sitemap](https://img.shields.io/badge/HTML-Sitemap-e34f26?logo=html5&logoColor=white)

## バージョン

```text
1.0.3
```

## 対応環境

- NexaPress 2.1.0以上
- PHP 8.0以上
- PHP mbstring拡張
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

### 個別除外とキャッシュ

XML・HTMLそれぞれに、除外する投稿ID・固定ページID・URLを設定できます。
両方から除外する場合は、各タブで指定してください。

- IDは半角カンマ・改行区切りで各1000件まで指定できます。
- URLは1行1件、1000件まで指定できます。公開URL全体、または公開URLのパスを完全一致で判定します。
- サブディレクトリ設置時のパスには、そのサブディレクトリも含めてください（例：`/subdir/post/example`）。
- URLのフラグメント（`#section`）は除外判定に使用しません。
- 投稿・固定ページのテーブルに`noindex`列がある場合は、その値が有効なコンテンツも除外します。列がない本体でも動作し、DB構造は変更しません。

キャッシュ有効期間はXML・HTML別に60〜3600秒で設定でき、初期値は300秒です。
設定保存や管理画面でのコンテンツ更新時には無効化し、次のアクセスで再生成します。

### 大規模サイトのXML分割

XMLは設定したURL数（1〜50000件、初期値50000件）、または非圧縮50MiBに達する前に自動分割します。
1ファイルに収まる場合は、従来通り`/sitemap.xml`でURL一覧を返します。
複数ファイルになる場合は、同じURLでサイトマップインデックスを返します。

```text
/sitemap.xml       サイトマップインデックス
/sitemap-1.xml     1つ目のXML
/sitemap-2.xml     2つ目のXML
```

分割ファイルをすべて生成してから公開するため、生成途中の一覧は公開しません。
存在しない分割番号は404を返します。

### HTMLの検索・並び替え・ページ送り

`/sitemap`でタイトルの部分一致検索、更新が新しい順・名前順の切り替えを利用できます。
検索は大文字・小文字を区別せず、最大100文字です。
名前順は数字を自然順で並べるため、「項目2」は「項目10」より前に表示されます。
ホームが掲載されている場合は、各並び順で先頭に配置します。

1ページの表示件数は10〜200件で設定でき、初期値は50件です。
ページ送りでは検索条件と並び順を維持し、範囲を超えたページ番号は最終ページとして表示します。
並び替えはキャッシュ生成時に行い、公開アクセスでは必要なページ分だけをメモリに保持します。

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
├─ 除外する投稿ID・固定ページID・URL
├─ キャッシュ有効期間
├─ XML 1ファイルあたりのURL数
└─ XML設定を保存

HTMLサイトマップ
├─ HTMLサイトマップを有効にする
├─ ページタイトル
├─ トップページを表示する
├─ 投稿一覧を表示する
├─ 固定ページ一覧を表示する
├─ カテゴリー一覧を表示する
├─ 除外する投稿ID・固定ページID・URL
├─ キャッシュ有効期間
├─ 1ページあたりの表示件数
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
│  │  ├─ SitemapCache.php
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
│  │  ├─ ContentRepository.php
│  │  ├─ PageRepository.php
│  │  └─ CategoryRepository.php
│  │
│  ├─ Xml/
│  │  ├─ XmlRenderer.php
│  │  ├─ XmlSitemapBuilder.php
│  │  └─ XmlResponse.php
│  │
│  ├─ Html/
│  │  ├─ HtmlViewData.php
│  │  └─ HtmlEntrySorter.php
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
│     ├─ advanced.php
│     └─ content-switches.php
│
├─ views/
│  └─ html/
│     ├─ sitemap.php
│     ├─ partials/
│     │  ├─ tools.php
│     │  └─ pagination.php
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
dist\nx_XML_HTML_sitemap-1.0.3.zip
```

作成されたZIPを確認します。

```bat
dir dist
```

ZIP内部を確認します。

```bat
tar -tf "dist\nx_XML_HTML_sitemap-1.0.3.zip"
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

ビルドはCLI専用です。`build.php`内の許可リストにあるファイルだけを収録し、
未登録のPHPファイル、`.env`、ログ、バックアップ、テストは収録しません。
必要な配布ファイルが欠けている場合や、リンク先がプロジェクト外の場合は失敗します。
実行時に使うファイルを追加した場合は、許可リストも更新してください。

## 設定保存の保護と生成時の負荷対策

- 設定画面と保存処理は、本体の`extensions.manage`権限を要求します。
- XML・HTML両方の保存フォームで、本体のCSRFトークンを発行・検証します。
- 投稿・固定ページは本文を取得せず、公開日時・IDをカーソルに500件ずつ取得します。
- 公開済みでもゴミ箱にあるコンテンツは掲載しません。テーブル接頭辞にも対応します。
- XMLはDOMツリーを全件保持せず、一時ファイルへ順番に書き込みます。
- XMLの生成結果とHTML用のリンク情報は、設定した期間キャッシュします（初期値5分）。
- XMLは複数ファイル、HTMLは更新順・名前順のキャッシュを生成します。並び替えも小分けに行い、全件をメモリへ読み込みません。
- HTMLのログイン利用者向けヘッダーなど、利用者によって変わる表示は共有キャッシュに保存しません。

キャッシュの保存先は次の場所です。このディレクトリへの書き込み権限が必要です。

```text
storage/extensions/nx_XML_HTML_sitemap/cache/
```

管理画面での投稿・固定ページ・カテゴリー・サイト設定の変更と、
本拡張の設定保存時にキャッシュを無効化します。
管理画面を経由せず、変更フックも発行しない更新は、設定したキャッシュ有効期間の経過後に反映されます。
生成中に更新があった場合は、古い生成結果を公開しません。

同時生成はXML・HTMLを合わせて1処理に制限します。
有効なキャッシュがなく別のリクエストが生成中の場合、待機せず`503`と`Retry-After: 5`を返します。
DB・保存先の障害時は、部分的な一覧を公開せず`503`を返します。
生成時の負荷を制限するため、掲載対象全体は25万URL、分割数は5万ファイル、
HTML用のリンクデータは約64MiBを上限とし、超過時も`503`を返します。
XMLの1ファイルあたりの上限は5万URL・50MiBで、上限を超える一覧は自動分割します。

## 回帰テスト

NexaPress本体の認証・権限・CSRFクラスを使って検証します。
本体ソースと、PHPのPDO SQLite・DOM・mbstring・ZipArchive拡張が必要です。

```bat
php tests/run.php ..\nexapress
```

テスト用の一時ディレクトリとSQLite DBを作成し、本体の設定ファイルやDBには接続しません。
不正リクエストの拒否、分割取得、キャッシュの競合・無効化、
個別除外とnoindex、XMLの件数・容量による分割、HTMLの検索・並び替え・ページ送り、
5万1件のXML・HTML生成、配布ZIPへの機密ファイル混入防止を確認します。

## 動作確認

次の順番で確認してください。

1. NexaPress管理画面の「拡張機能」を開く
2. `nx_XML_HTML_sitemap-1.0.3.zip`をアップロードする
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
16. XML・HTML別の個別除外が反映されることを確認する
17. XMLの1ファイルあたりのURL数を小さく設定し、インデックスと分割XMLを確認する
18. HTMLの検索・更新順・名前順と、検索条件を維持したページ送りを確認する

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

## 1.0.3の改修

- 管理画面にXML／HTMLの有効状態と公開URLを表示し、実際のXMLを未ログイン状態で確認するボタンを追加しました。404（停止・未検出）、503（生成・設定の問題）、HTTP 200のHTML応答を区別します。
- XMLが有効な場合だけ公開ページのheadにサイトマップを宣言し、URL収集側が実在する公開先を発見できるようにしました。
- CMSが相対パスを返す場合もXMLと分割インデックスには完全なURLを出力します。キャッシュはサイトのホスト・方式を含む起点ごとに区別します。
- 設定は共有ロックで読み込み、保存は排他ロックと一時ファイルの置換で行います。破損・型不正の設定を初期値へ黙って戻さず、管理画面に復旧案内を表示し、公開側は503で停止します。停止中の404と一時エラーは保存しない応答にします。
- 無効化したXMLを強制的に公開する変更はありません。404の場合は管理画面の有効状態と公開結果を確認してください。

追加確認：`php -n tests/url-context.php`。既存の回帰テストと合わせて実行してください。
