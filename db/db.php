<?php
// db.php - PostgreSQL connection. Configure via .env file (project root) or env vars.

// Load .env file if present (for local development)
(function () {
    $envFile = __DIR__ . '/../.env';
    if (!is_file($envFile)) return;
    $contents = file_get_contents($envFile);
    if ($contents === false) return;
    // Strip UTF-8 BOM if present
    if (substr($contents, 0, 3) === "\xEF\xBB\xBF") {
        $contents = substr($contents, 3);
    }
    foreach (preg_split('/\r\n|\r|\n/', $contents) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        // Strip any BOM/non-printables that snuck onto the key
        $k = preg_replace('/^[^A-Za-z_]+/', '', $k);
        if ($k === '') continue;
        // Strip optional surrounding quotes
        $v = preg_replace('/^([\'"])(.*)\1$/', '$2', $v);
        if (getenv($k) === false) {
            putenv("$k=$v");
            $_ENV[$k] = $v;
        }
    }
})();

class Database
{
    private $host;
    private $port;
    private $dbname;
    private $username;
    private $password;
    private $sql;

    private function env($name, $default = '')
    {
        return getenv($name) ?: ($_ENV[$name] ?? ($_SERVER[$name] ?? $default));
    }

    public function getConnection()
    {
        $this->sql = null;

        // Defaults to LOCAL PostgreSQL (override via .env or Vercel env vars)
        $this->host = $this->env('DB_HOST', 'localhost');
        $this->port = $this->env('DB_PORT', '5432');
        $this->dbname = $this->env('DB_NAME', 'announcement');
        $this->username = $this->env('DB_USER', 'postgres');
        $this->password = $this->env('DB_PASS', '');

        // SSL: localhost and ngrok tunnels skip SSL; others require it.
        // Override with DB_SSLMODE=disable|require|prefer
        $sslMode = $this->env('DB_SSLMODE', '');
        if ($sslMode === '') {
            $hostLower = strtolower($this->host);
            $isPlain = in_array($hostLower, ['localhost', '127.0.0.1', '::1'], true)
                || str_contains($hostLower, 'ngrok.io')
                || str_contains($hostLower, 'ngrok-free.app')
                || str_contains($hostLower, 'ngrok.app');
            $sslMode = $isPlain ? 'disable' : 'require';
        }
        $sslPart = $sslMode === 'disable' ? '' : ';sslmode=' . $sslMode;

        // Connect timeout (in seconds). Keeps Vercel from hanging for ~30s
        // when the local laptop / ngrok tunnel is offline. Override via DB_TIMEOUT.
        $timeout = (int)$this->env('DB_TIMEOUT', '6');
        if ($timeout < 2) $timeout = 2;

        try {
            $dsn = "pgsql:host=" . $this->host .
                ";port=" . $this->port .
                ";dbname=" . $this->dbname .
                ";connect_timeout=" . $timeout .
                $sslPart;

            $this->sql = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_TIMEOUT => $timeout,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(503);
            // Friendly message; keep details out of public response
            die('Database is temporarily unavailable. Please try again in a moment.');
        }

        return $this->sql;
    }
}

function getDB()
{
    static $connection = null;
    if ($connection === null) {
        $database = new Database();
        $connection = $database->getConnection();
    }
    return $connection;
}
?>