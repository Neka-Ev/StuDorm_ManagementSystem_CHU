<?php
// 检查是否登录的中间件
function check_admin_login() {
    session_start();
    
    if (!isset($_SESSION['admin'])) {
        // 记录尝试访问的页面以便登录后重定向
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header("Location: ../login.php");
        exit();
    }
}

// 在需要登录的页面顶部包含此文件并调用函数
// require_once 'auth_check.php';
// check_admin_login();
?>