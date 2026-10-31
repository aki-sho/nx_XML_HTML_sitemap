<?php

/*
 * 実際のNexaPressクラスを使い、接続先だけを一時SQLite DBへ差し替える。
 * 本体の設定ファイル・DBには接続しない。
 */
class SitemapTestPdo extends PDO
{
    public array $queries = [];

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->queries[] = $query;
        return parent::prepare($query, $options);
    }
}

function sitemapTestApplication(string $core, string $runtime): SitemapTestPdo
{
    define('BASE_PATH', $runtime);
    define('BASE_URL', 'https://example.test/subdir');
    define('NX_XML_HTML_SITEMAP_PATH', dirname(__DIR__));
    define('NX_XML_HTML_SITEMAP_ID', 'nx_XML_HTML_sitemap');

    foreach (['app/Config', 'app/Views/admin', 'config'] as $directory) {
        if (!is_dir($runtime . '/' . $directory)) {
            mkdir($runtime . '/' . $directory, 0755, true);
        }
    }

    copy($core . '/app/Config/roles.php', $runtime . '/app/Config/roles.php');
    copy($core . '/app/Views/admin/forbidden.php', $runtime . '/app/Views/admin/forbidden.php');
    file_put_contents($runtime . '/app/Views/admin/layout.php', '<?php echo $content;');

    foreach (['Database', 'Auth', 'Permission', 'Csrf', 'View', 'Hook'] as $class) {
        require_once $core . '/app/Core/' . $class . '.php';
    }

    require_once $core . '/app/Models/User.php';
    require_once $core . '/app/Core/helpers.php';
    require_once NX_XML_HTML_SITEMAP_PATH . '/autoload.php';

    $pdo = new SitemapTestPdo('sqlite::memory:', null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    foreach (['pdo' => $pdo, 'config' => ['table_prefix' => 'nx_test_']] as $name => $value) {
        $property = new ReflectionProperty(app\Core\Database::class, $name);
        $property->setAccessible(true);
        $property->setValue(null, $value);
    }

    $pdo->exec('CREATE TABLE nx_test_users (id INTEGER PRIMARY KEY, username TEXT,
        name TEXT, email TEXT, role TEXT, created_at TEXT)');
    $pdo->exec("INSERT INTO nx_test_users VALUES
        (1, 'test', 'Test User', 'test@example.test', 'administrator', '2026-01-01')");
    $pdo->exec('CREATE TABLE nx_test_categories (id INTEGER PRIMARY KEY, slug TEXT)');
    $pdo->exec("INSERT INTO nx_test_categories VALUES (1, 'news')");

    foreach (['posts', 'pages'] as $type) {
        $pdo->exec('CREATE TABLE nx_test_' . $type . ' (id INTEGER PRIMARY KEY,
            title TEXT, slug TEXT, status TEXT, content TEXT, published_at TEXT,
            created_at TEXT, updated_at TEXT, deleted_at TEXT, category_id INTEGER)');
    }

    $_SESSION = ['_csrf_token' => str_repeat('a', 64)];
    ini_set('error_log', $runtime . '/php-errors.log');

    return $pdo;
}

function sitemapTestPhpCommand(string $script, array $arguments = []): array
{
    $command = [PHP_BINARY, '-n', '-d', 'extension_dir=' . ini_get('extension_dir')];

    foreach (['mbstring', 'pdo_sqlite', 'zip'] as $extension) {
        $command[] = '-d';
        $command[] = 'extension=' . $extension;
    }

    return array_merge($command, [$script], $arguments);
}

function sitemapTestProcess(array $command): array
{
    $process = proc_open($command, [
        0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
    ], $pipes);

    if (!is_resource($process)) {
        throw new RuntimeException('Cannot start test process.');
    }

    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return ['status' => proc_close($process), 'output' => $output, 'errors' => $errors];
}
