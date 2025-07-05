<?php
class DormitoryAllocator {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * 按学院和性别自动分配宿舍
     * 
     * @param int $collegeId 学院ID
     * @param array $maleBuildings 男生宿舍楼ID数组
     * @param array $femaleBuildings 女生宿舍楼ID数组
     * @param int $adminId 操作管理员ID
     * @return array 分配结果 [assigned => 已分配人数, unassigned => 未分配人数]
     */
    public function autoAssignByCollegeAndGender($collegeId, $maleBuildings, $femaleBuildings, $adminId) {
        // 开始事务
        if (sqlsrv_begin_transaction($this->conn) === false) {
            throw new Exception("无法开始事务: " . print_r(sqlsrv_errors(), true));
        }
        
        try {
            $assigned = 0;
            $unassigned = 0;
            
            // 1. 获取未分配宿舍的学生(按性别分组)
            $maleStudents = $this->getUnassignedStudents($collegeId, 'M');
            $femaleStudents = $this->getUnassignedStudents($collegeId, 'F');
            
            // 2. 分配男生
            if (!empty($maleBuildings)) {
                $maleRooms = $this->getAvailableRooms($maleBuildings);
                foreach ($maleStudents as $student) {
                    if ($this->assignStudentToRoom($student['student_id'], $maleRooms, $adminId)) {
                        $assigned++;
                    } else {
                        $unassigned++;
                    }
                }
            } else {
                $unassigned += count($maleStudents);
            }
            
            // 3. 分配女生
            if (!empty($femaleBuildings)) {
                $femaleRooms = $this->getAvailableRooms($femaleBuildings);
                foreach ($femaleStudents as $student) {
                    if ($this->assignStudentToRoom($student['student_id'], $femaleRooms, $adminId)) {
                        $assigned++;
                    } else {
                        $unassigned++;
                    }
                }
            } else {
                $unassigned += count($femaleStudents);
            }
            
            // 提交事务
            sqlsrv_commit($this->conn);
            return ['assigned' => $assigned, 'unassigned' => $unassigned];
            
        } catch (Exception $e) {
            // 回滚事务
            sqlsrv_rollback($this->conn);
            throw $e;
        }
    }
    
    /**
     * 获取未分配宿舍的学生
     */
    private function getUnassignedStudents($collegeId, $gender) {
        $sql = "SELECT student_id FROM Students 
                WHERE college_id = ? AND gender = ? AND dorm_room_id IS NULL";
        $params = array($collegeId, $gender);
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            throw new Exception("查询未分配学生失败: " . print_r(sqlsrv_errors(), true));
        }
        
        $students = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $students[] = $row;
        }
        
        sqlsrv_free_stmt($stmt);
        return $students;
    }
    
    /**
     * 获取指定宿舍楼中有空位的房间
     */
    private function getAvailableRooms($buildingIds) {
        $placeholders = implode(',', array_fill(0, count($buildingIds), '?'));   // 形成字符串 ?,?,?,... ;?个数取决于所拥有宿舍楼数量
        $sql = "SELECT r.room_id, r.building_id, r.capacity, r.current_occupancy
                FROM DormitoryRooms r
                WHERE r.building_id IN ($placeholders) 
                AND r.current_occupancy < r.capacity
                ORDER BY r.current_occupancy DESC"; // 优先分配人数较多的房间
        
        $stmt = sqlsrv_query($this->conn, $sql, $buildingIds);
        
        if ($stmt === false) {
            throw new Exception("查询可用房间失败: " . print_r(sqlsrv_errors(), true));
        }
        
        $rooms = array();
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $rooms[] = $row;
        }
        
        sqlsrv_free_stmt($stmt);
        return $rooms;
    }
    
    /**
     * 将学生分配到房间
     */
    private function assignStudentToRoom($studentId, &$availableRooms, $adminId) {
        foreach ($availableRooms as &$room) {
            if ($room['current_occupancy'] < $room['capacity']) {
                // 1. 标记旧分配记录为无效(如果有)
                $this->deactivateOldAllocations($studentId);
                
                // 2. 更新学生住宿信息
                $this->updateStudentDormInfo($studentId, $room['building_id'], $room['room_id']);
                
                // 3. 更新房间入住人数
                $this->updateRoomOccupancy($room['room_id'], $room['current_occupancy'] + 1);
                $room['current_occupancy']++; // 更新本地引用，避免重复分配情况发生
                
                // 4. 记录分配历史
                $this->recordAllocation($studentId, $room['room_id'], $adminId);
                
                return true;
            }
        }
        
        return false; // 没有合适房间
    }
    
    /**
     * 标记学生旧的分配记录为无效
     */
    private function deactivateOldAllocations($studentId) {
        $sql = "UPDATE Allocations SET is_active = 0 
                WHERE student_id = ? AND is_active = 1";
        $params = array($studentId);
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            throw new Exception("更新旧分配记录失败: " . print_r(sqlsrv_errors(), true));
        }
        
        sqlsrv_free_stmt($stmt);
    }
    
    /**
     * 更新学生住宿信息
     */
    private function updateStudentDormInfo($studentId, $buildingId, $roomId) {
        $sql = "UPDATE Students 
                SET dorm_building_id = ?, dorm_room_id = ? 
                WHERE student_id = ?";
        $params = array($buildingId, $roomId, $studentId);
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            throw new Exception("更新学生住宿信息失败: " . print_r(sqlsrv_errors(), true));
        }
        
        sqlsrv_free_stmt($stmt);
    }
    
    /**
     * 更新房间入住人数
     */
    private function updateRoomOccupancy($roomId, $newOccupancy) {
        $sql = "UPDATE DormitoryRooms 
                SET current_occupancy = ? 
                WHERE room_id = ?";
        $params = array($newOccupancy, $roomId);
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            throw new Exception("更新房间入住人数失败: " . print_r(sqlsrv_errors(), true));
        }
        
        sqlsrv_free_stmt($stmt);
    }
    
    /**
     * 记录分配历史
     */
    private function recordAllocation($studentId, $roomId, $adminId) {
        $sql = "INSERT INTO Allocations 
                (student_id, room_id, created_by, allocation_type, is_active) 
                VALUES (?, ?, ?, 'initial', 1)";
        $params = array($studentId, $roomId, $adminId);
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        
        if ($stmt === false) {
            throw new Exception("记录分配历史失败: " . print_r(sqlsrv_errors(), true));
        }
        
        sqlsrv_free_stmt($stmt);
    }
}
?>