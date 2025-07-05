<?php
require_once __DIR__ . '/../../config/db_config.php';
header('Content-Type: application/json');

//$backupDir = 'D:\\xampp\\htdocs\\StuDorm_ManagementSystem\\backup\\';
$backupDir = 'D:\\testOS\\';
$filename = 'StuDorm_ManagementSystem_' . date('Ymd_His');
$backupFile = $backupDir . $filename . '.bak';

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$query = "BACKUP DATABASE [DormDB] TO DISK = N'$backupFile' WITH INIT, COMPRESSION";
$stmt = sqlsrv_query($conn, $query);

// 消费所有结果集
if ($stmt) {
    do {} while (sqlsrv_next_result($stmt));
    sqlsrv_free_stmt($stmt);
}

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

if (file_exists($backupFile) && filesize($backupFile) > 0) {
    echo json_encode([
        'success' => true,
        'file' => $filename . '.bak',
        'info' => $errMsg // 返回信息消息
    ]);
} else {
    echo json_encode([
        'success' => false,
        'msg' => '备份文件未生成',
        'errors' => $errMsg
    ]);
}
?>