<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Base Model Class
 * Provides common database operations for all models
 */
abstract class BaseModel {
    protected $db;
    protected $table;
    protected $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    /**
     * Get all records from table
     */
    public function getAll($limit = null, $offset = null, $orderBy = 'id DESC') {
        $sql = "SELECT * FROM {$this->table}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            if ($offset) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get record by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Create new record
     */
    public function create($data) {
        $columns = array_keys($data);
        $placeholders = ':' . implode(', :', $columns);
        $columnList = implode(', ', $columns);
        
        $sql = "INSERT INTO {$this->table} ({$columnList}) VALUES ({$placeholders})";

        if (isset($data['location'])) {
            // Ganti placeholder dan nilai bind dengan format ST_GeomFromText untuk MySQL
            $sql = str_replace(':location', "ST_GeomFromText(:location)", $sql);
        }

        $stmt = $this->conn->prepare($sql);
        
        foreach ($data as $key => $value) {
            if ($key == 'location' && !is_null($value)) {
                $stmt->bindValue(':location', $value);
            } else {
                $stmt->bindValue(':' . $key, $value);
            }
        }
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Update record by ID
     */
    public function update($id, $data) {
        $setParts = [];
        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = :id";

        if (isset($data['location'])) {
            // Ganti placeholder dan nilai bind dengan format ST_GeomFromText untuk MySQL
            $sql = str_replace(':location', "ST_GeomFromText(:location)", $sql);
        }

        $stmt = $this->conn->prepare($sql);
        
        foreach ($data as $key => $value) {
            if ($key == 'location' && !is_null($value)) {
                $stmt->bindValue(':location', $value);
            } else {
                $stmt->bindValue(':' . $key, $value);
            }
        }
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Delete record by ID
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    /**
     * Count total records
     */
    public function count($conditions = null) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        
        if ($conditions) {
            $sql .= " WHERE {$conditions}";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'];
    }

    /**
     * Execute custom query
     */
    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->conn->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->conn->rollback();
    }

    /**
     * Close database connection
     */
    public function __destruct() {
        $this->db->closeConnection();
    }

    /** 
     * Convert coordinates to point
     */
    public function coordinatesToPoint($latitude, $longitude) {
        if ($latitude === null || $longitude === null) {
            return null;
        }
        // Note MySQL POINT uses (x, y) = (longitude, latitude)
        return sprintf('POINT(%F %F)', $longitude, $latitude);
    }
}
?>