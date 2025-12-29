<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * Donation Model
 * Handles donation-related database operations
 */
class Donation extends BaseModel {
    protected $table = 'donations';

    /**
     * Create new donation
     * Override parent create to handle location as text field
     */
    public function create($data) {
        $columns = array_keys($data);
        $placeholders = ':' . implode(', :', $columns);
        $columnList = implode(', ', $columns);
        
        $sql = "INSERT INTO {$this->table} ({$columnList}) VALUES ({$placeholders})";
        
        $stmt = $this->conn->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    /**
     * Update donation
     * Override parent update to handle location as text field
     */
    public function update($id, $data) {
        $setParts = [];
        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setParts);
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = :id";
        
        $stmt = $this->conn->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    /**
     * Create new donation (legacy method)
     */
    public function createDonation($data) {
        return $this->create($data);
    }

    /**
     * Get donations by user
     */
    public function getDonationsByUser($userId) {
        $sql = "SELECT * 
                FROM {$this->table} d  
                WHERE d.user_id = :user_id 
                ORDER BY d.donation_date DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get donation statistics for user
     */
    public function getUserDonationStats($userId) {
        $stats = [];
        
        // Total donations (both personal and request-based)
        $sql = "SELECT COUNT(*) as total FROM {$this->table} 
                WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $stats['total_donations'] = $stmt->fetch()['total'];
        
        // Last donation date
        $sql = "SELECT MAX(donation_date) as last_date FROM {$this->table} 
                WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $stats['last_donation_date'] = $stmt->fetch()['last_date'];
        
        return $stats;
    }
}
?>