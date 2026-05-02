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

    public function getConnection()
    {
        $this->sql = null;

        // Read from environment variables, fall back to defaults for local dev
        // Use Supabase connection pooler for better IPv4 support
        $this->host = $_ENV['DB_HOST'] ?? 'aws-0-ap-southeast-1.pooler.supabase.com';
        $this->port = $_ENV['DB_PORT'] ?? '6543';
        $this->dbname = $_ENV['DB_NAME'] ?? 'postgres';
        $this->username = $_ENV['DB_USER'] ?? 'postgres';
        $this->password = $_ENV['DB_PASS'] ?? '@#Ellyred@#12345';

        try {
            // Try to resolve IPv4 address, fallback to hostname
            $ipv4 = gethostbyname($this->host);
            $hostToUse = ($ipv4 && $ipv4 !== $this->host) ? $ipv4 : $this->host;

            $dsn = "pgsql:host=" . $hostToUse .
                ";port=" . $this->port .
                ";dbname=" . $this->dbname .
                ";sslmode=require";

            $this->sql = new PDO($dsn, $this->username, $this->password);
            $this->sql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->sql->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
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