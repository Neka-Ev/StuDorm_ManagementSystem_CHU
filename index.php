<?php
// 设置页面标题
$pageTitle = "宿舍管理系统首页";
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="./assets/css/index.css">
    <link rel="icon" type="image//x-icon" href="./assets/icons/headicon.ico">
</head>
<body>
    <!-- 头部导航 -->
    <header>
        <nav class="navigasi">
            <div class="logo" alt="大学 Logo">
                <a href="index.php">
                    <img src="./assets/images/logo_blue.png" alt="大学 Logo" class="logo-img">
                </a>
            </div>
            <div class="nav">
                <a href="login.php">登录</a>
                <a href="register.php">注册</a>
            </div>
        </nav>
    </header>

    <!-- 主图区域 -->
    <section class="header">
        <div class="header-content">
            <h1>-长安屋舍-</h1>
            <h1>宿舍管理系统</h1>
            <p>为学生提供安全、舒适的住宿环境，实现便利化的宿舍管理</p>
            <div class="cta-buttons">
                <a href="login.php" class="cta-button">登录系统</a>
            </div>
        </div>
    </section>

    <!-- 系统功能 -->
    <section class="fitur">
        <h2 class="section-title">系统功能</h2>
        <p class="section-subtitle">全面覆盖宿舍管理需求</p>
        <div class="container">
            <div class="box">
                <div class="feature-icon">🛏️</div>
                <h3>宿舍分配</h3>
                <p>自动分配宿舍，支持按院系、性别自动分配</p>
            </div>
            <div class="box">
                <div class="feature-icon">📝</div>
                <h3>住宿管理</h3>
                <p>学生住宿信息录入、查询与管理，宿舍信息管理</p>
            </div>
            <div class="box">
                <div class="feature-icon">🔧</div>
                <h3>数据安全</h3>
                <p>SSMS备份还原数据库，保障数据安全</p>
            </div>
        </div>
    </section>

    <!-- 关于我们 -->
    <section class="tentang-kami">
        <h2 class="section-title">关于我们</h2>
        <p class="section-subtitle">宿舍管理系统</p>
        <div class="container">
            <div class="box">
                <h3>开发人员</h3>
                <p>NULL</p>
                <p>NULL</p>
                <p>NULL</p>
                <p>NULL</p>
            </div>
            <div class="box">
                <h3>学号</h3>
                <p>NULL</p>
                <p>NULL</p>
                <p>NULL</p>
                <p>NULL</p>
            </div>
        </div>
    </section>
<footer>
    <nav class="navigasi" style="display: flex; flex-direction: column; align-items: center;">
        <div class="logo">
            <a href="index.php">
                <img src="./assets/images/logo_white.png" alt="大学 Logo" class="logo-img" style="display: block; margin: 0 auto;">
            </a>
        </div>
    </nav>
    <p style="text-align: center; margin-top: 10px;">
        &copy;<?php echo date('Y'); ?> Chang'an House Dormitory Management System, All Rights Reserved
    </p>
</footer>
</body>
</html>