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

        // Use Supabase connection pooler (IPv4-compatible for Vercel)
        $this->host = getenv('DB_HOST') ?: 'aws-1-ap-southeast-2.pooler.supabase.com';
        $this->port = getenv('DB_PORT') ?: '6543';
        $this->dbname = getenv('DB_NAME') ?: 'postgres';
        $this->username = getenv('DB_USER') ?: 'postgres.fddnruksiofxalrtypmk';
        $this->password = getenv('DB_PASS') ?: '@#Ellyred@#12345';

        try {
            $dsn = "pgsql:host=" . $this->host .
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