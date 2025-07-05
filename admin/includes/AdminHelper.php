<?php
class AdminHelper {
    private $conn;
    public function __construct($conn) {
        $this->conn = $conn;
    }
    public function getAdminById($adminId) {
        $sql = "SELECT * FROM Administrators WHERE admin_id = ?";
        $stmt = sqlsrv_query($this->conn, $sql, [$adminId]);
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }
    public function checkOldPassword($adminId, $oldPassword) {
        $sql = "EXISTS (SELECT 1 FROM Administrators WHERE admin_id = ? AND password = ?)";
        $stmt = sqlsrv_query($this->conn, $sql, [$adminId, $oldPassword]);
        if ($stmt === false) {
            return false; // 与原密码不匹配，驳回
        }
    }
    public function updateAdmin($adminId, $username, $password) {
        if ($password !== '') {
            $sql = "UPDATE Administrators SET username = ?, password = ? WHERE admin_id = ?";
            return sqlsrv_query($this->conn, $sql, [$username, $password, $adminId]);
        } else {
            $sql = "UPDATE Administrators SET username = ? WHERE admin_id = ?";
            return sqlsrv_query($this->conn, $sql, [$username, $adminId]);
        }
    }
}
?>