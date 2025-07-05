<?php
// filepath: d:\xampp\htdocs\StuDorm_ManagementSystem\admin\includes\DormManageHelper.php

class DormManageHelper {
    private $conn;
    public function __construct($conn) {
        $this->conn = $conn;
    }

    // 获取所有宿舍楼
    public function getAllBuildings() {
        $sql = "SELECT building_id, building_name FROM DormitoryBuildings ORDER BY building_id";
        $stmt = sqlsrv_query($this->conn, $sql);
        $buildings = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $buildings[$row['building_id']] = $row['building_name'];
        }
        return $buildings;
    }

    // 获取所有学院
    public function getAllColleges() {
        $sql = "SELECT college_id, college_name FROM Colleges ORDER BY college_id";
        $stmt = sqlsrv_query($this->conn, $sql);
        $colleges = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $colleges[$row['college_id']] = $row['college_name'];
        }
        return $colleges;
    }

    // 获取学院与宿舍楼绑定关系
    public function getBuildingBindings() {
        $sql = "SELECT building_id, college_id FROM CollegeDormitoryBinding";
        $stmt = sqlsrv_query($this->conn, $sql);
        $bindings = [];
        if ($stmt !== false) {
            while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
                $bindings[$row['college_id']][] = $row['building_id'];
            }
        }
        return $bindings;
    }

    // 查询宿舍信息（支持多条件、分页）
    public function searchDorms($buildingId = '', $roomNumber = '', $collegeId = '', $notShowEmpty = '', $page = 1, $pageSize = 10) {
        $params = [];
        $where = "1=1";
        // 按宿舍楼筛选
        if ($buildingId !== '') {
            $where .= " AND building_name = ?";
            $params[] = $buildingId;
        }
        // 按房间号筛选
        if ($roomNumber !== '') {
            $where .= " AND room_number LIKE ?";
            $params[] = "%$roomNumber%";
        }
        // 按学院筛选（通过绑定关系）
        if ($collegeId !== '') {
            $bindings = $this->getBuildingBindings();
            $bindBuildingIds = isset($bindings[$collegeId]) ? $bindings[$collegeId] : [];
            if (!empty($bindBuildingIds)) {
                $in = implode(',', array_map('intval', $bindBuildingIds));
                $where .= " AND building_name IN (
                    SELECT building_name FROM DormitoryBuildings WHERE building_id IN ($in)
                )";
            } else {
                // 没有绑定则查不到
                $where .= " AND 1=0";
            }
        }
        // 是否只展示非空宿舍
        if ($notShowEmpty === '1') {
            $where .= " AND actual_count <> 0";
        }

        // 分页
        $offset = ($page - 1) * $pageSize;
        $sql = "SELECT * FROM DormRoomOccupancyPivotView 
                WHERE $where ORDER BY room_id 
                OFFSET $offset ROWS FETCH NEXT $pageSize ROWS ONLY";
        $stmt = sqlsrv_query($this->conn, $sql, $params);

        $result = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $result[] = $row;
        }

        // 获取总数
        $sqlCount = "SELECT COUNT(*) AS cnt FROM DormRoomOccupancyPivotView WHERE $where";
        $stmtCount = sqlsrv_query($this->conn, $sqlCount, $params);
        $total = 0;
        if ($stmtCount) {
            $row = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC);
            $total = $row ? intval($row['cnt']) : 0;
        }

        return ['data' => $result, 'total' => $total];
    }

    public function getDormMembers($roomId) {
    $sql = "SELECT s.student_id, s.full_name, s.gender, s.college_id, c.college_name, s.student_id AS sno
            FROM Students s
            LEFT JOIN Colleges c ON s.college_id = c.college_id
            WHERE s.dorm_room_id = ?";
    $stmt = sqlsrv_query($this->conn, $sql, [$roomId]);
    $members = [];
    $bed = 1;
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        // 通过学号前四位获取年级
        $row['grade'] = substr($row['sno'], 0, 4);
        $row['bed'] = $bed++;
        $members[] = $row;
    }
    return $members;
    }

    public function updateDormType($roomId, $dormType) {
        $sql = "UPDATE DormitoryRooms SET dorm_type = ? WHERE room_id = ?";
        return sqlsrv_query($this->conn, $sql, [$dormType, $roomId]);
    }
}
?>