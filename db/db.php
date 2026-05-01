<?php
// db.php
class Database
{
    // 👇 PASTE THE SESSION POOLER VALUES HERE 👇
    private $host = 'db.fddnruksiofxalrtypmk.supabase.co';  // From pooler string
    private $port = '5432';
    private $dbname = 'postgres';
    private $username = 'postgres';  // Notice: postgres.YOUR_PROJECT_ID
    private $password = '@#Ellyred@#12345';  // Your real password
    private $sql;

    public function getConnection()
    {
        $this->sql = null;

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
    $database = new Database();
    return $database->getConnection();
}
?>