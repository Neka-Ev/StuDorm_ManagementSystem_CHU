<?php
// filepath: d:\xampp\htdocs\StuDorm_ManagementSystem\admin\ajax_dorm_type.php
require_once __DIR__ . '/../../admin/includes/DormManageHelper.php';
require_once __DIR__ . '/../../config/db_config.php';

$helper = new DormManageHelper($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = intval($_POST['room_id']);
    $dormType = $_POST['dorm_type'];
    $ok = $helper->updateDormType($roomId, $dormType);
    echo $ok ? 'success' : 'fail';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['room_id'])) {
    $roomId = intval($_GET['room_id']);
    $sql = "SELECT r.room_id, r.room_number, r.dorm_type, b.building_name
            FROM DormitoryRooms r
            JOIN DormitoryBuildings b ON r.building_id = b.building_id
            WHERE r.room_id = ?";
    $stmt = sqlsrv_query($conn, $sql, [$roomId]);
    $info = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    $members = $helper->getDormMembers($roomId);
    header('Content-Type: application/json');
    echo json_encode([
        'info' => $info,
        'members' => $members
    ]);
    exit;
}
?>