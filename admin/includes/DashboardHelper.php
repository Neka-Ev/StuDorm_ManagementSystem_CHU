<?php

class DashboardHelper {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // 查询学生总数
    public function getStudentCount() {
        $sql = "SELECT COUNT(*) AS cnt FROM Students";
        $stmt = sqlsrv_query($this->conn, $sql);
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row ? intval($row['cnt']) : 0;
    }

    // 查询宿舍总数
    public function getDormCount() {
        $sql = "SELECT COUNT(*) AS cnt FROM DormitoryRooms";
        $stmt = sqlsrv_query($this->conn, $sql);
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row ? intval($row['cnt']) : 0;
    }

    // 查询管理员数量
    public function getAdminCount() {
        $sql = "SELECT COUNT(*) AS cnt FROM Administrators";
        $stmt = sqlsrv_query($this->conn, $sql);
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row ? intval($row['cnt']) : 0;
    }

    // 查询入住率
    public function getOccupancyRate() {
        $sqlTotal = "SELECT COUNT(*) AS total FROM DormitoryRooms";
        $sqlUsed = "SELECT SUM(current_occupancy) AS used FROM DormitoryRooms";
        $stmtTotal = sqlsrv_query($this->conn, $sqlTotal);
        $stmtUsed = sqlsrv_query($this->conn, $sqlUsed);
        $total = sqlsrv_fetch_array($stmtTotal, SQLSRV_FETCH_ASSOC)['total'] ?? 0;
        $used = sqlsrv_fetch_array($stmtUsed, SQLSRV_FETCH_ASSOC)['used'] ?? 0;
        if ($total == 0) return '0%';
        // 假设每间房最多住满（可根据实际情况调整）
        $sqlCap = "SELECT SUM(capacity) AS cap FROM DormitoryRooms";
        $stmtCap = sqlsrv_query($this->conn, $sqlCap);
        $cap = sqlsrv_fetch_array($stmtCap, SQLSRV_FETCH_ASSOC)['cap'] ?? 0;
        if ($cap == 0) return '0%';
        return round($used / $cap * 100, 1) . '%';
    }

    // 查询最近活动（调换/分配记录）
    public function getRecentActivities($limit = 10) {
    $sql = "SELECT t.*, 
                   s.full_name AS student_name, 
                   r_from.room_number AS from_room_number, 
                   r_to.room_number AS to_room_number, 
                   b_from.building_name AS from_building_name,
                   b_to.building_name AS to_building_name, 
                   a.username AS admin_name,
                   a.full_name AS admin_level
            FROM Transfers t
            LEFT JOIN Students s ON t.student_id = s.student_id
            LEFT JOIN DormitoryRooms r_from ON t.from_room_id = r_from.room_id
            LEFT JOIN DormitoryRooms r_to ON t.to_room_id = r_to.room_id
            LEFT JOIN DormitoryBuildings b_from ON r_from.building_id = b_from.building_id
            LEFT JOIN DormitoryBuildings b_to ON r_to.building_id = b_to.building_id
            LEFT JOIN Administrators a ON t.processed_by = a.admin_id
            ORDER BY t.transfer_date DESC";
    $stmt = sqlsrv_query($this->conn, $sql);
    $activities = [];
    $count = 0;
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $activities[] = $row;
        $count++;
        if ($count >= $limit) break;
    }
    return $activities;
    }
    public function getBuildingOverviewData() {
    // 查询每栋楼的入住人数、性别、绑定学院
    $sql = "SELECT b.building_id, b.building_name, b.gender,
                   SUM(r.current_occupancy) AS occupancy
            FROM DormitoryBuildings b
            LEFT JOIN DormitoryRooms r ON b.building_id = r.building_id
            GROUP BY b.building_id, b.building_name, b.gender
            ORDER BY b.building_id";
    $stmt = sqlsrv_query($this->conn, $sql);
    $data = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $data[$row['building_id']] = [
            'building_name' => $row['building_name'],
            'gender' => $row['gender'],
            'occupancy' => intval($row['occupancy'])
        ];
    }
    // 查询绑定学院
    $sql2 = "SELECT b.building_id, c.college_name
             FROM CollegeDormitoryBinding b
             JOIN Colleges c ON b.college_id = c.college_id";
    $stmt2 = sqlsrv_query($this->conn, $sql2);
    while ($row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)) {
        $bid = $row['building_id'];
        if (!isset($data[$bid]['colleges'])) $data[$bid]['colleges'] = [];
        $data[$bid]['colleges'][] = $row['college_name'];
    }
    return array_values($data);
}
}