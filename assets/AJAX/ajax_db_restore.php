<?php
// filepath: d:\xampp\htdocs\StuDorm_ManagementSystem\assets\AJAX\ajax_db_restore.php
require_once __DIR__ . '/../../config/db_config.php';
header('Content-Type: application/json');
$file = $_GET['file'] ?? '';
$backupDir = realpath(__DIR__ . '/../../backup');
$bakFile = $backupDir . DIRECTORY_SEPARATOR . basename($file);
if (!is_file($bakFile)) {
    echo json_encode(['success' => false, 'msg' => '备份文件不存在']);
    exit;
}
$sql = "RESTORE DATABASE [DormDB] FROM DISK = N'$bakFile' WITH REPLACE";
$stmt = sqlsrv_query($conn, $sql);

// 获取所有消息
$errors = sqlsrv_errors();
$errMsg = '';
if (is_array($errors)) {
    foreach ($errors as $err) {
        foreach ($err as $key => $val) {
            $errMsg .= "$key: $val\n";
        }
        $errMsg .= "---------------------\n";
    }
}

if ($stmt) {
    echo json_encode(['success' => true, 'info' => $errMsg]);
} else {
    echo json_encode(['success' => false, 'msg' => '数据库还原失败', 'errors' => $errMsg]);
}
?>