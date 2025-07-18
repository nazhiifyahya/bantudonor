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
        
        // Total blood bags donated
        $sql = "SELECT SUM(blood_bags) as total FROM {$this->table} 
                WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $stats['total_blood_bags'] = $stmt->fetch()['total'] ?: 0;
        
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