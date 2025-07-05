<?php
// filepath: d:\xampp\htdocs\StuDorm_ManagementSystem\admin\includes\StuManageHelper.php
class StuManageHelper {
    private $conn;
    public function __construct($conn) {
        $this->conn = $conn;
    }

    // 查询学生（支持筛选和分页）
    public function searchStudents($name = '', $collegeId = '', $grade = '', $page = 1, $pageSize = 10) {
        $where = "1=1";
        $params = [];
        if ($name !== '') {
            $where .= " AND s.full_name LIKE ?";
            $params[] = "%$name%";
        }
        if ($collegeId !== '') {
            $where .= " AND s.college_id = ?";
            $params[] = $collegeId;
        }
        if ($grade !== '') {
            $where .= " AND LEFT(s.student_id, 4) = ?";
            $params[] = $grade;
        }
        $offset = ($page - 1) * $pageSize;
        $sql = "SELECT s.student_id, s.full_name, s.gender, s.college_id, c.college_name,
                       b.building_name, r.room_number
                FROM Students s
                LEFT JOIN Colleges c ON s.college_id = c.college_id
                LEFT JOIN DormitoryBuildings b ON s.dorm_building_id = b.building_id
                LEFT JOIN DormitoryRooms r ON s.dorm_room_id = r.room_id
                WHERE $where
                ORDER BY s.student_id
                OFFSET $offset ROWS FETCH NEXT $pageSize ROWS ONLY";
        $stmt = sqlsrv_query($this->conn, $sql, $params);
        $students = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $students[] = $row;
        }
        // 总数
        $sqlCount = "SELECT COUNT(*) AS cnt FROM Students s WHERE $where";
        $stmtCount = sqlsrv_query($this->conn, $sqlCount, $params);
        $total = 0;
        if ($stmtCount) {
            $row = sqlsrv_fetch_array($stmtCount, SQLSRV_FETCH_ASSOC);
            $total = $row ? intval($row['cnt']) : 0;
        }
        return ['data' => $students, 'total' => $total];
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

    // 添加学生
    public function addStudent($fullName, $gender, $collegeId, $grade) {
        // 生成学号通过触发器完成
        $year = $grade;
        $admissionDate = $year . '-09-01';
        $sql = "INSERT INTO Students (full_name, gender, college_id, admission_date) VALUES (?, ?, ?, ?)";
        $params = [$fullName, $gender, $collegeId, $admissionDate];
        return sqlsrv_query($this->conn, $sql, $params);
    }

    // 批量删除学生（及其相关外键表记录）
    public function deleteStudents($studentIds) {
        if (empty($studentIds)) return false;
        // 先删除外键表
        $in = implode(',', array_fill(0, count($studentIds), '?'));
        // Transfers
        $sql = "DELETE FROM Transfers WHERE student_id IN ($in)";
        sqlsrv_query($this->conn, $sql, $studentIds);
        // Allocations
        $sql = "DELETE FROM Allocations WHERE student_id IN ($in)";
        sqlsrv_query($this->conn, $sql, $studentIds);
        // Students
        $sql = "DELETE FROM Students WHERE student_id IN ($in)";
        return sqlsrv_query($this->conn, $sql, $studentIds);
    }
}
?>