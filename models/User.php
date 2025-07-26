<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * User Model
 * Handles user-related database operations
 */
class User extends BaseModel {
    protected $table = 'users';

    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $sql = "SELECT *, ST_Y(location) AS latitude, ST_X(location) AS longitude FROM {$this->table} WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Create new user
     */
    public function createUser($data) {
        // Hash password before storing
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (isset($data['email'])) {
            $data['unique_token'] = hash('sha256', $data['email']);
        }

        return $this->create($data);
    }

    /**
     * Verify user password
     */
    public function verifyPassword($email, $password) {
        $user = $this->getUserByEmail($email);
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }

    /**
     * Update user profile
     */
    public function updateProfile($data) {
        $id = $data['id'];
        
        // Remove id from update data
        unset($data['id']);
        
        // Remove password from update data if empty
        if (isset($data['password']) && empty($data['password'])) {
            unset($data['password']);
        } elseif (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Get user by email (alias for getUserByEmail)
     */
    public function getByEmail($email) {
        return $this->getUserByEmail($email);
    }

    /**
     * Get users by blood type and location
     */
    public function getUsersByBloodTypeAndLocation($bloodTypeAbo, $bloodTypeRhesus, $city = null) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE blood_type_abo = :abo 
                AND blood_type_rhesus = :rhesus 
                AND is_active = 1";
        
        $params = [
            ':abo' => $bloodTypeAbo,
            ':rhesus' => $bloodTypeRhesus
        ];
        
        if ($city) {
            $sql .= " AND city = :city";
            $params[':city'] = $city;
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Update last donation date
     */
    public function updateLastDonationDate($userId, $date) {
        return $this->update($userId, ['last_donation_date' => $date]);
    }

    /**
     * Check if user can donate (at least 3 months since last donation)
     */
    public function canDonate($userId) {
        $user = $this->getById($userId);
        if (!$user || !$user['last_donation_date']) {
            return true;
        }
        
        $lastDonation = new DateTime($user['last_donation_date']);
        $now = new DateTime();
        $interval = $lastDonation->diff($now);
        
        return $interval->m >= 3 || $interval->y > 0;
    }

    /**
     * Search users
     */
    public function searchUsers($keyword, $bloodType = null) {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if ($keyword) {
            $sql .= " AND (full_name LIKE :keyword OR city LIKE :keyword OR province LIKE :keyword)";
            $params[':keyword'] = "%{$keyword}%";
        }
        
        if ($bloodType) {
            $bloodTypeParts = str_split($bloodType);
            if (count($bloodTypeParts) >= 2) {
                $abo = substr($bloodType, 0, -1);
                $rhesus = substr($bloodType, -1);
                $sql .= " AND blood_type_abo = :abo AND blood_type_rhesus = :rhesus";
                $params[':abo'] = $abo;
                $params[':rhesus'] = $rhesus;
            }
        }
        
        $sql .= " AND is_active = 1 ORDER BY full_name ASC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getUserByUniqueToken($token) {
        $sql = "SELECT * FROM {$this->table} WHERE unique_token = :token";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getUserByTelegramChatId($chatId) {
        $sql = "SELECT * FROM {$this->table} WHERE telegram_chat_id = :chatId";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':chatId', $chatId);
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>