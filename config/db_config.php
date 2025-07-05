<?php
session_start();
$serverName = "localhost"; // 数据库服务器名称或IP地址
if (isset($_SESSION['admin']['role']) && $_SESSION['admin']['role'] === 'super_admin') {  // 检查是否为普通管理员
     $connectionOptions = array(
        "Database" => "DormDB",
        "Uid" => "dormdb_user",  //其实是超管
        "PWD" => "P88888888",
        "CharacterSet" => "UTF-8"
    );
} else {
   $connectionOptions = array(
        "Database" => "DormDB",
        "Uid" => "dormdb_admin",  //这个才是普管
        "PWD" => "AAA666666",
        "CharacterSet" => "UTF-8"
    );
}

// 建立连接
$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>