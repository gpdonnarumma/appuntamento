<?php
/**
 * Database Configuration and Connection
 * SQLite Database Handler
 */

class Database {
    private static $instance = null;
    private $connection;
    private $dbPath;

    private function __construct() {
        $this->dbPath = __DIR__ . '/../database/music_school.db';
        $this->connect();
        $this->initializeSchema();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get database connection
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Connect to SQLite database
     */
    private function connect() {
        try {
            $this->connection = new PDO('sqlite:' . $this->dbPath);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Enable foreign keys
            $this->connection->exec('PRAGMA foreign_keys = ON;');
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Initialize database schema if not exists
     */
    private function initializeSchema() {
        try {
            // Check if users table exists
            $query = "SELECT name FROM sqlite_master WHERE type='table' AND name='users'";
            $stmt = $this->connection->query($query);
            $result = $stmt->fetch();

            // If users table doesn't exist, run schema
            if (!$result) {
                $schemaPath = __DIR__ . '/../database/schema.sql';
                if (file_exists($schemaPath)) {
                    $schema = file_get_contents($schemaPath);
                    $this->connection->exec($schema);
                }
            }
        } catch (PDOException $e) {
            error_log("Schema initialization error: " . $e->getMessage());
        }
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->connection->rollBack();
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Get database instance helper function
 */
function getDB() {
    return Database::getInstance()->getConnection();
}
