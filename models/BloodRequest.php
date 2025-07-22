<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * BloodRequest Model
 * Handles blood request-related database operations
 */
class BloodRequest extends BaseModel {
    protected $table = 'blood_requests';

    /**
     * Generate unique request code
     */
    private function generateRequestCode() {
        $prefix = 'BD';
        $date = date('dmY');
        
        // Get the last request of the day
        $sql = "SELECT request_code FROM {$this->table} 
                WHERE request_code LIKE :pattern 
                ORDER BY request_code DESC LIMIT 1";
        
        $pattern = $prefix . '%' . $date;
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':pattern', $pattern);
        $stmt->execute();
        $lastRequest = $stmt->fetch();
        
        if ($lastRequest) {
            // Extract number and increment
            $lastCode = $lastRequest['request_code'];
            $parts = explode('-', $lastCode);
            $number = intval($parts[0] . substr($parts[0], 2)) + 1;
            $code = $prefix . sprintf('%03d', $number) . '-' . $date;
        } else {
            $code = $prefix . '001-' . $date;
        }
        
        return $code;
    }

    /**
     * Create new blood request
     */
    public function createRequest($data) {
        $data['request_code'] = $this->generateRequestCode();
        return $this->create($data);
    }

    /**
     * Get active blood requests
     */
    public function getActiveRequests($limit = null, $offset = null) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE status = 'Active' 
                ORDER BY created_at ASC";
        
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
     * Search blood requests
     */
    public function searchRequests($filters = []) {
        $sql = "SELECT * FROM {$this->table} WHERE status = 'Active'";
        $params = [];
        
        if (!empty($filters['blood_type'])) {
            $bloodTypeParts = str_split($filters['blood_type']);
            if (count($bloodTypeParts) >= 2) {
                $abo = substr($filters['blood_type'], 0, -1);
                $rhesus = substr($filters['blood_type'], -1);
                $sql .= " AND blood_type_abo = :abo AND blood_type_rhesus = :rhesus";
                $params[':abo'] = $abo;
                $params[':rhesus'] = $rhesus;
            }
        }
        
        if (!empty($filters['city'])) {
            $sql .= " AND city LIKE :city";
            $params[':city'] = "%{$filters['city']}%";
        }
        
        $sql .= " ORDER BY created_at ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get requests by city
     */
    public function getRequestsByCity($city) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE city = :city 
                AND status = 'Active' 
                ORDER BY created_at ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':city', $city);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get requests by blood type
     */
    public function getRequestsByBloodType($bloodTypeAbo, $bloodTypeRhesus) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE blood_type_abo = :abo 
                AND blood_type_rhesus = :rhesus 
                AND status = 'Active' 
                ORDER BY created_at ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':abo', $bloodTypeAbo);
        $stmt->bindParam(':rhesus', $bloodTypeRhesus);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Update request status
     */
    public function updateStatus($id, $status) {
        return $this->update($id, ['status' => $status]);
    }

    /**
     * Get requests statistics
     */
    public function getStatistics() {
        $stats = [];
        
        // Total active requests
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'Active'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $stats['total_active'] = $stmt->fetch()['total'];
        
        // Requests by blood type
        $sql = "SELECT CONCAT(blood_type_abo, blood_type_rhesus) as blood_type, COUNT(*) as total 
                FROM {$this->table} 
                WHERE status = 'Active' 
                GROUP BY blood_type_abo, blood_type_rhesus 
                ORDER BY total DESC";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $stats['by_blood_type'] = $stmt->fetchAll();
        
        return $stats;
    }

    /**
     * Get expired requests
     */
    public function getExpiringRequests() {
        $sql = "SELECT * FROM {$this->table} 
                WHERE status = 'Active' 
                AND DATE_ADD(DATE(created_at), INTERVAL 5 DAY) <= CURDATE()
                ORDER BY created_at ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get active blood requests with smart sorting for logged in users
     */
    public function getActiveRequestsWithSmartSorting($userBloodType = '', $userCity = '', $limit = null, $offset = null) {
        if (!empty($userBloodType) || !empty($userCity)) {
            // Smart sorting for logged in users: blood type match, city match, then needed_date
            $sql = "SELECT *, 
                    CASE 
                        WHEN CONCAT(blood_type_abo, blood_type_rhesus) = :userBloodType THEN 1 
                        ELSE 0 
                    END as blood_type_match,
                    CASE 
                        WHEN city = :userCity THEN 1 
                        ELSE 0 
                    END as city_match
                    FROM {$this->table} 
                    WHERE status = 'Active' 
                    ORDER BY blood_type_match DESC, city_match DESC, created_at ASC";
            
            $params = [
                ':userBloodType' => $userBloodType,
                ':userCity' => $userCity
            ];
        } else {
            // Default sorting for non-logged users: urgency then needed_date
            $sql = "SELECT * FROM {$this->table} 
                    WHERE status = 'Active'
                    ORDER BY created_at ASC";
            $params = [];
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            if ($offset) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Search blood requests with smart sorting for logged in users
     */
    public function searchRequestsWithSmartSorting($filters = [], $userBloodType = '', $userCity = '') {
        if (!empty($userBloodType) || !empty($userCity)) {
            // Smart sorting query with user preferences
            $sql = "SELECT *, 
                    CASE 
                        WHEN CONCAT(blood_type_abo, blood_type_rhesus) = :userBloodType THEN 1 
                        ELSE 0 
                    END as blood_type_match,
                    CASE 
                        WHEN city = :userCity THEN 1 
                        ELSE 0 
                    END as city_match
                    FROM {$this->table} 
                    WHERE status = 'Active'";
            
            $params = [
                ':userBloodType' => $userBloodType,
                ':userCity' => $userCity
            ];
        } else {
            // Default query for non-logged users
            $sql = "SELECT * FROM {$this->table} WHERE status = 'Active'";
            $params = [];
        }
        
        // Apply filters
        if (!empty($filters['blood_type'])) {
            $bloodTypeParts = str_split($filters['blood_type']);
            if (count($bloodTypeParts) >= 2) {
                $abo = substr($filters['blood_type'], 0, -1);
                $rhesus = substr($filters['blood_type'], -1);
                $sql .= " AND blood_type_abo = :abo AND blood_type_rhesus = :rhesus";
                $params[':abo'] = $abo;
                $params[':rhesus'] = $rhesus;
            }
        }
        
        if (!empty($filters['city'])) {
            $sql .= " AND city LIKE :city";
            $params[':city'] = "%{$filters['city']}%";
        }
        
        
        // Apply smart sorting
        if (!empty($userBloodType) || !empty($userCity)) {
            $sql .= " ORDER BY blood_type_match DESC, city_match DESC, created_at ASC";
        } else {
            $sql .= " ORDER BY created_at ASC";
        }
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
?>