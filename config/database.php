<?php
/**
 * Database Configuration
 * BantuDonor Application
 */
require_once __DIR__ . '/envloader.php';

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $charset = 'utf8mb4';
    public $conn;

    /**
     * Constructor
     * Initializes the database connection parameters
     */

    public function __construct() {
        $this->host = $_ENV['MySQL_DB_HOST'];
        $this->db_name = $_ENV['MySQL_DB_NAME'];
        $this->username = $_ENV['MySQL_DB_USER'];
        $this->password = $_ENV['MySQL_DB_PASSWORD'];
    }

    /**
     * Get database connection
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
            die();
        }
        
        return $this->conn;
    }
    
    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->conn = null;
    }
}

?>