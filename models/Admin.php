<?php
require_once __DIR__ . '/BaseModel.php';

class Admin extends BaseModel {
    protected $table = 'admin';

    public function getAdminByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = :username";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function verifyPassword($username, $password) {
        $admin = $this->getAdminByUsername($username);
        if ($admin && password_verify($password, $admin['password'])) {
            return $admin;
        }
        return false;
    }

    public function createAdmin($username, $password) {
        return $this->create([
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ]);
    }
}
