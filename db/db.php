<?php
// db.php - Supabase PostgreSQL connection with environment variables
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

        // Use Supabase connection pooler (IPv4-compatible for Vercel)
        $this->host = $this->env('DB_HOST', 'aws-1-ap-southeast-2.pooler.supabase.com');
        $this->port = $this->env('DB_PORT', '6543');
        $this->dbname = $this->env('DB_NAME', 'postgres');
        $this->username = $this->env('DB_USER', 'postgres.fddnruksiofxalrtypmk');
        $this->password = $this->env('DB_PASS', '@#Ellyred@#12345');

        try {
            $dsn = "pgsql:host=" . $this->host .
                ";port=" . $this->port .
                ";dbname=" . $this->dbname .
                ";sslmode=require";

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