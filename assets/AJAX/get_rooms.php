<?php
require_once __DIR__ . '/../../config/db_config.php';
require_once __DIR__ . '/../../admin/includes/TransferHelper.php';

header('Content-Type: text/html');

if (!isset($_GET['building_id'])) {
    die('<div class="alert alert-danger">缺少参数</div>');
}

$buildingId = intval($_GET['building_id']);
$helper = new TransferHelper($conn);
$rooms = $helper->getRoomsByBuilding($buildingId);

if (empty($rooms)) {
    echo '<div class="alert alert-info">该宿舍楼没有可用房间</div>';
    exit;
}

// 横排按钮式输出
foreach ($rooms as $room) {
    $available = $room['capacity'] - $room['current_occupancy'];
    echo '<span class="room-item" data-room-id="' . $room['room_id'] . '" title="楼层:'.$room['floor'].' 容量:'.$room['capacity'].' 剩余:'.$available.'">';
    echo htmlspecialchars($room['room_number']) . ' (' . $available . ')';
    echo '</span>';
}
?>