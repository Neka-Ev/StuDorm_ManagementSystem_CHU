<?php
include './config/db_config.php'; // SQL Server 数据库连接文件

$error = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
        $sql = "SELECT * FROM Administrators WHERE username = ?";
        $params = array($username);
        $stmt = sqlsrv_query($conn, $sql, $params);
        
        if ($stmt === false) {
            die("数据库查询失败: " . print_r(sqlsrv_errors(), true));
        }

        if (sqlsrv_has_rows($stmt)) {
            $admin = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            // 验证密码
            if ($password == $admin['password']) {
                // 设置会话变量
                $role = ($admin['full_name'] == '超级管理员')?'super_admin':'admin'; // 判断当前管理员身份，默认为普通管理员
                $_SESSION['admin'] = [
                    'admin_id' => $admin['admin_id'],
                    'username' => $admin['username'],
                    'full_name' => $admin['full_name'],
                    'last_login' => $admin['last_login'],
                    'role' => $role
                ];
                
                // 更新最后登录时间
                $update_sql = "UPDATE Administrators SET last_login = GETDATE() WHERE admin_id = ?";
                $update_params = array($admin['admin_id']);
                sqlsrv_query($conn, $update_sql, $update_params);
                
                // 重定向到主页
                header("Location: admin/admin_dashboard.php");
                exit();
            } else {
                echo "<script>alert('用户名或密码错误！');</script>";
            }
        } else {
            echo "<script>alert('用户名不存在！');</script>";
        }
        
        sqlsrv_free_stmt($stmt);
    } catch (Exception $e) {
        $error = '登录系统错误: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录</title>
    <link rel="stylesheet" href="./assets/css/login.css">
    <link rel="icon" type="image//x-icon" href="./assets/icons/headicon.ico">
</head>
<body>
     <header>
        <nav class="navigasi">
            <div class="logo" alt="大学 Logo">
                <a href="index.php">
                    <img src="./assets/images/logo_white.png" alt="大学 Logo" class="logo-img">
                </a>
            </div>
        </nav>
    </header>
    <div class="container">
        <div class="left-section">
            <div class="logo">
                
                    <div class="icon"> 
                        <a href="index.php">
                            <img src="./assets/images/logo_back.png">  
                        </a>
                    </div>
               
                <h1>返回首页</h1>
            </div>
        </div>
        <div class="right-section">
            <div class="form-container">
                <h2>管理员登录</h2>
                <p>请输入您的管理员凭据</p>
                
                <form method="POST" action="">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" placeholder="输入管理员用户名" required>

                    <label for="password">密码</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" placeholder="输入密码" required>
                    </div>
                    
                    <button type="submit" class="btn" name="login">登录</button>
                </form>
                <p>没有账号？<a href="register.php">前往注册</a></p>
            </div>
        </div>
    </div>
</body>
</html>