<?php
include '../includes/auth.php'; // 包含登录验证
check_admin_login();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>宿舍管理系统 - 控制台</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="../assets/css/content_autoassign.css">
    <link rel="stylesheet" href="../assets/css/content_dormtransfer.css">
    <link rel="icon" href="../assets/images/favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="admin-container">
        <!-- 顶部导航栏 -->
        <header class="admin-header">
            <div class="logo" alt="大学 Logo">
                <a href="../index.php">
                    <img src="../assets/images/logo_blue.png" alt="大学 Logo" class="logo-img">
                </a>   
                <span class="logo-text">宿舍管理系统</span>         
            </div>
            
            <div class="admin-actions">
                <div class="admin-profile">
                    
                    <img src="../assets/images/head_<?php echo $_SESSION['admin']['username'] ?>.jpg"
                        alt="管理员头像"
                        onerror="this.onerror=null;this.src='../assets/images/head_<?php echo $_SESSION['admin']['username'] ?>.png';">
                    
                    <span><?php echo $_SESSION['admin']['username'] ?? '管理员'; ?></span>
                    <div class="profile-dropdown">
                        <a href="../admin/admin_profile.php"><i class="fas fa-user"></i> 个人空间</a>
                        <a href="../login.php" onclick="location.href='../logout.php'"><i class="fas fa-sign-out-alt"></i> 退出登录</a>
                    </div>
                </div>
            </div>
        </header>