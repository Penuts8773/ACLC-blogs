<?php
class UserController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllUsers() {
        try {
            $stmt = $this->pdo->query("SELECT usn, name, privilege FROM user ORDER BY privilege ASC");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Error getting users: " . $e->getMessage());
            return [];
        }
    }

    public function updateUserPrivilege($usn, $newLevel) {
        try {
            $stmt = $this->pdo->prepare("UPDATE user SET privilege = ? WHERE usn = ?");
            return $stmt->execute([intval($newLevel), $usn]);
        } catch (Exception $e) {
            error_log("Error updating user privilege: " . $e->getMessage());
            return false;
        }
    }

    public function isAdmin($user) {
        if (!isset($user['privilege'])) {
            return false;
        }
        // Explicitly cast to integer and compare
        return intval($user['privilege']) === 1;
    }

    public function getPrivilegeName($level) {
        switch (intval($level)) {
            case 1:
                return 'Admin';
            case 2:
                return 'Teacher';
            case 3:
                return 'Student';
            default:
                return 'Unknown';
        }
    }
}