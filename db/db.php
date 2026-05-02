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

        // Use Supabase connection pooler for IPv4 support (Vercel doesn't support IPv6)
        // Environment variables are ignored to ensure pooler is used
        $this->host = 'aws-0-ap-southeast-1.pooler.supabase.com';
        $this->port = '6543';
        $this->dbname = $_ENV['DB_NAME'] ?? 'postgres';
        $this->username = $_ENV['DB_USER'] ?? 'postgres';
        $this->password = $_ENV['DB_PASS'] ?? '@#Ellyred@#12345';

        try {
            // Try to resolve IPv4 via Google DNS-over-HTTPS
            $dnsUrl = "https://dns.google/resolve?name=" . $this->host . "&type=A";
            $dnsResponse = @file_get_contents($dnsUrl);
            $ipv4 = null;
            if ($dnsResponse) {
                $dnsData = json_decode($dnsResponse, true);
                if (isset($dnsData['Answer'])) {
                    foreach ($dnsData['Answer'] as $answer) {
                        if ($answer['type'] == 1) { // A record (IPv4)
                            $ipv4 = $answer['data'];
                            break;
                        }
                    }
                }
            }

            $hostToUse = $ipv4 ?: $this->host;

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