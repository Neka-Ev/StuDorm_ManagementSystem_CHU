<?php
class AutoAssignHelper {
    private $conn;
    public $colleges = [];
    public $buildings = [];
    public $bindings = []; // [building_id => [college_id, ...]]
    public $collegeBindings = []; // [college_id => [building_id, ...]]
    public $unassignedCount = ['M'=>0, 'F'=>0];
    public $buildingVacancy = []; // [building_id => 剩余床位数]

    public function __construct($conn) {
        $this->conn = $conn;
        $this->loadColleges();
        $this->loadBuildings();
        $this->loadBindings();
    }

    private function loadColleges() {
        $result = sqlsrv_query($this->conn, "SELECT college_id, college_name FROM Colleges");
        while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
            $this->colleges[$row['college_id']] = $row['college_name'];
        }
    }

    private function loadBuildings() {
        $result = sqlsrv_query($this->conn, "SELECT building_id, building_name, gender FROM DormitoryBuildings");
        while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
            $this->buildings[$row['building_id']] = $row;
        }
    }

    private function loadBindings() {
        $result = sqlsrv_query($this->conn, "SELECT college_id, building_id FROM CollegeDormitoryBinding");
        while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
            $bid = $row['building_id'];
            $cid = $row['college_id'];
            $this->bindings[$bid][] = $cid;
            $this->collegeBindings[$cid][] = $bid;
        }
    }

    public function getUnassignedCountByGender($collegeId) {
        $counts = ['M'=>0, 'F'=>0];
        $sql = "SELECT gender, COUNT(*) AS cnt FROM Students WHERE college_id=? AND dorm_room_id IS NULL GROUP BY gender";
        $result = sqlsrv_query($this->conn, $sql, [$collegeId]);
        while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
            $counts[$row['gender']] = intval($row['cnt']);
        }
        return $counts;
    }

    public function getBuildingVacancy() {
        $vacancy = [];
        $sql = "SELECT b.building_id, SUM(r.capacity - ISNULL(r.current_occupancy,0)) AS vacancy
                FROM DormitoryBuildings b
                JOIN DormitoryRooms r ON b.building_id = r.building_id
                GROUP BY b.building_id";
        $result = sqlsrv_query($this->conn, $sql);
        if ($result === false) {
            return $vacancy; // 返回空数组，避免后续报错
        }
        while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
            $vacancy[$row['building_id']] = intval($row['vacancy']);
        }
        return $vacancy;
    }

    public function validateBinding($selectedBuildings) {
        $male = $female = 0;
        foreach ($selectedBuildings as $bid) {
            if (!isset($this->buildings[$bid])) continue;
            if ($this->buildings[$bid]['gender'] == 'M') $male++;
            if ($this->buildings[$bid]['gender'] == 'F') $female++;
        }
        if ($male < 1 || $female < 1) {
            return false;
        }
        return true;
    }

    /**
     * 将所选宿舍楼与学院绑定，先清空原有绑定，再插入新绑定
     * @param int $collegeId 学院ID
     * @param array $selectedBuildings 选中的宿舍楼ID数组
     */
    public function saveBinding($collegeId, $selectedBuildings) {
        // 1. 删除本学院原有绑定（无论多选/少选/未选）
        sqlsrv_query($this->conn, "DELETE FROM CollegeDormitoryBinding WHERE college_id = ?", [$collegeId]);
        // 2. 插入所有当前选中的宿舍楼绑定
        foreach ($selectedBuildings as $buildingId) {
            sqlsrv_query($this->conn, "INSERT INTO CollegeDormitoryBinding (building_id, college_id) VALUES (?, ?)", [$buildingId, $collegeId]);
        }
        // 3. 重新加载绑定关系
        $this->bindings = [];
        $this->collegeBindings = [];
        $this->loadBindings();
    }
}
?>
