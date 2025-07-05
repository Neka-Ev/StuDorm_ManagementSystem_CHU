<?php
class TransferHelper {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    // 获取学生信息
    public function getStudent($studentId) {
        $sql = "SELECT s.*, c.college_name, b.building_name, r.room_number 
                FROM Students s
                JOIN Colleges c ON s.college_id = c.college_id
                LEFT JOIN DormitoryBuildings b ON s.dorm_building_id = b.building_id
                LEFT JOIN DormitoryRooms r ON s.dorm_room_id = r.room_id
                WHERE s.student_id = ?";
        $params = array($studentId);
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            return null;
        }
        
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }
    
    
    // 查询所有学生
    public function searchAllStudents($name = '', $collegeId = '', $page = 1, $pageSize = 10, &$total = 0) {
    $offset = ($page - 1) * $pageSize;
    $params = [];
    $where = "WHERE 1=1";
    if ($name !== '') {
        $where .= " AND s.full_name LIKE ?";
        $params[] = "%$name%";
    }
    if ($collegeId !== '') {
        $where .= " AND s.college_id = ?";
        $params[] = $collegeId;
    }
    // 获取总数
    $sqlCount = "SELECT COUNT(*) AS cnt FROM Students s $where";
    $stmtCount = sqlsrv_query($this->conn, $sqlCount, $params);
    $total = 0;
    if ($stmtCount && ($row = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC))) {
        $total = $row['cnt'];
    }
    // 获取分页数据
    $sql = "SELECT s.student_id, s.full_name, s.gender, s.college_id, c.college_name, 
                   b.building_name, r.room_number, s.dorm_room_id
            FROM Students s
            JOIN Colleges c ON s.college_id = c.college_id
            LEFT JOIN DormitoryBuildings b ON s.dorm_building_id = b.building_id
            LEFT JOIN DormitoryRooms r ON s.dorm_room_id = r.room_id
            $where
            ORDER BY s.full_name
            OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
    $params2 = array_merge($params, [$offset, $pageSize]);
    $stmt = sqlsrv_query($this->conn, $sql, $params2);
    $students = [];
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $students[] = $row;
        }
    }
    return $students;
}
    
    // 获取所有宿舍楼
    public function getAllBuildings() {
        $sql = "SELECT * FROM DormitoryBuildings ORDER BY building_name";
        $stmt = sqlsrv_query($this->conn, $sql);
        
        if ($stmt === false) {
            return array();
        }
        
        $buildings = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $buildings[] = $row;
        }
        
        return $buildings;
    }
    
    // 获取宿舍楼的房间
    public function getRoomsByBuilding($buildingId) {
        $sql = "SELECT r.*, 
                       (r.capacity - r.current_occupancy) as available_beds
                FROM DormitoryRooms r
                WHERE r.building_id = ?
                AND r.current_occupancy < r.capacity
                ORDER BY r.floor, r.room_number";
        $params = array($buildingId);
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            return array();
        }
        
        $rooms = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rooms[] = $row;
        }
        
        return $rooms;
    }
    
    // 执行调换/分配
    public function processTransfer($studentId, $targetRoomId, $adminId, $reason = '') {
        // 获取学生当前房间
        $student = $this->getStudent($studentId);
        if (!$student) {
            throw new Exception("学生不存在");
        }

        // 获取目标房间信息
        $targetRoom = $this->getRoomInfo($targetRoomId);
        if (!$targetRoom) {
            throw new Exception("目标房间不存在");
        }

        // 检查性别是否匹配
        $buildingGender = $this->getBuildingGender($targetRoom['building_id']);
        $studentGender = $student['gender'];

        if ($buildingGender != $studentGender) {
            throw new Exception("宿舍楼性别与学生性别不匹配");
        }

        // 检查宿舍楼与学院的绑定关系
        if (!$this->checkBuildingCollegeBinding($targetRoom['building_id'], $student['college_id'])) {
            throw new Exception("目标宿舍楼未绑定该学生所在学院，无法分配/调换");
        }

        // 开始事务
        sqlsrv_begin_transaction($this->conn);

        try {
            // 1. 更新原房间的占用数（仅已分配学生才需要）
            if (!empty($student['dorm_room_id'])) {
                $sql = "UPDATE DormitoryRooms 
                        SET current_occupancy = current_occupancy - 1 
                        WHERE room_id = ?";
                $params = array($student['dorm_room_id']);
                $stmt = sqlsrv_query($this->conn, $sql, $params);
                if ($stmt === false) {
                    throw new Exception("更新原房间占用数失败");
                }
            }

            // 2. 更新目标房间的占用数
            $sql = "UPDATE DormitoryRooms 
                    SET current_occupancy = current_occupancy + 1 
                    WHERE room_id = ?";
            $params = array($targetRoomId);
            $stmt = sqlsrv_query($this->conn, $sql, $params);
            if ($stmt === false) {
                throw new Exception("更新目标房间占用数失败");
            }

            // 3. 更新学生的宿舍信息
            $sql = "UPDATE Students 
                    SET dorm_building_id = ?, dorm_room_id = ? 
                    WHERE student_id = ?";
            $params = array($targetRoom['building_id'], $targetRoomId, $studentId);
            $stmt = sqlsrv_query($this->conn, $sql, $params);
            if ($stmt === false) {
                throw new Exception("更新学生宿舍信息失败");
            }

            // 4. 记录调换/分配历史
            $sql = "INSERT INTO Transfers 
                    (student_id, from_room_id, to_room_id, processed_by, reason, approval_status) 
                    VALUES (?, ?, ?, ?, ?, 'approved')";
            $params = array(
                $studentId,
                !empty($student['dorm_room_id']) ? $student['dorm_room_id'] : null,
                $targetRoomId,
                $adminId,
                $reason
            );
            $stmt = sqlsrv_query($this->conn, $sql, $params);

            // 5. 更新原分配记录为不活跃（仅已分配学生才需要）
            if (!empty($student['dorm_room_id'])) {
                $sql = "UPDATE Allocations 
                        SET is_active = 0 
                        WHERE student_id = ? AND is_active = 1";
                $params = array($studentId);
                $stmt = sqlsrv_query($this->conn, $sql, $params);
                if ($stmt === false) {
                    throw new Exception("更新原分配记录失败");
                }
            }

            // 6. 创建新分配记录
            $sql = "INSERT INTO Allocations 
                    (student_id, room_id, created_by, allocation_type, notes) 
                    VALUES (?, ?, ?, ?, ?)";
            $allocationType = !empty($student['dorm_room_id']) ? 'transfer' : 'initial';  //为空则为初始分配
            $reason = !empty($reason) ? $reason : (($allocationType == 'initial')?'初始分配宿舍':'未填写');
            $params = array($studentId, $targetRoomId, $adminId, $allocationType, $reason);
            $stmt = sqlsrv_query($this->conn, $sql, $params);
            if ($stmt === false) {
                throw new Exception("创建新分配记录失败");
            }

            // 提交事务
            sqlsrv_commit($this->conn);
            return true;
        } catch (Exception $e) {
            sqlsrv_rollback($this->conn);
            throw $e;
        }
    }
    
    // 获取房间信息
    private function getRoomInfo($roomId) {
        $sql = "SELECT * FROM DormitoryRooms WHERE room_id = ?";
        $params = array($roomId);
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            return null;
        }
        
        return sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    }
    
    // 获取宿舍楼性别
    private function getBuildingGender($buildingId) {
        $sql = "SELECT gender FROM DormitoryBuildings WHERE building_id = ?";
        $params = array($buildingId);
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            return null;
        }
        
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row ? $row['gender'] : null;
    }
    
    // 获取所有学院
    public function getAllColleges() {
        $sql = "SELECT * FROM Colleges ORDER BY college_name";
        $stmt = sqlsrv_query($this->conn, $sql);
        
        if ($stmt === false) {
            return array();
        }
        
        $colleges = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $colleges[$row['college_id']] = $row['college_name'];
        }
        
        return $colleges;
    }

    // 检查宿舍楼与学院的绑定关系
    private function checkBuildingCollegeBinding($buildingId, $collegeId) {
        $sql = "SELECT COUNT(*) AS cnt FROM CollegeDormitoryBinding WHERE building_id = ? AND college_id = ?";
        $params = array($buildingId, $collegeId);
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        if ($stmt === false) {
            return false;
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return ($row && $row['cnt'] > 0);
    }

    // 获取楼绑定关系
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
}
?>