<?php
// db.php - PostgreSQL connection (works with Supabase or local PG via .env)

// Load .env file if present (for local development)
(function () {
    $envFile = __DIR__ . '/../.env';
    if (!is_file($envFile)) return;
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (!str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
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

        // Defaults to Supabase if no env override is set
        $this->host = $this->env('DB_HOST', 'aws-1-ap-southeast-2.pooler.supabase.com');
        $this->port = $this->env('DB_PORT', '6543');
        $this->dbname = $this->env('DB_NAME', 'postgres');
        $this->username = $this->env('DB_USER', 'postgres.fddnruksiofxalrtypmk');
        $this->password = $this->env('DB_PASS', '@#Ellyred@#12345');

        // Use SSL only for remote hosts; localhost doesn't need it
        $isLocal = in_array(strtolower($this->host), ['localhost', '127.0.0.1', '::1'], true);
        $sslPart = $isLocal ? '' : ';sslmode=require';

        try {
            $dsn = "pgsql:host=" . $this->host .
                ";port=" . $this->port .
                ";dbname=" . $this->dbname .
                $sslPart;

            $this->sql = new PDO($dsn, $this->username, $this->password);
            $this->sql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->sql->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            die('Database connection error. Please try again later.');
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