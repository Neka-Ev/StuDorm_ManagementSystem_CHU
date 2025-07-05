<?php
require_once __DIR__ . '/config/db_config.php';

$errMsg = '';
$successMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'admin';
    $admin_code = $_POST['admin_code'] ?? '';

    $fullname = ($role === 'admin') ? '普通管理员' : '超级管理员';

    // 管理员身份码
    $valid_code = 'Neka_TSPWD';

    if ($username === '' || $password === '') {
        $errMsg = '用户名和密码不能为空！';
    } elseif ($admin_code !== $valid_code) {
        $errMsg = '管理员身份码错误，无法注册！';
    } else {
        // 检查用户名是否已存在
        $sql = "SELECT * FROM Administrators WHERE username = ?";
        $stmt = sqlsrv_query($conn, $sql, [$username]);
        if ($stmt && sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $errMsg = '用户名已存在，请更换！';
        } else {
            // 密码加密
            $sql = "INSERT INTO Administrators (username, password, full_name) VALUES (?, ?, ?)";
            $stmt = sqlsrv_query($conn, $sql, [$username, $password, $fullname]);
            if ($stmt) {
                $successMsg = '注册成功！请前往登录。';
            } else {
                $errMsg = '注册失败，请重试。';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>管理员注册</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="./assets/icons/headicon.ico">
    <style>
        .register-tip { color:#888; font-size:15px; margin-bottom:18px; }
        .role-select { margin-bottom:18px; text-align:left; }
    </style>
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
                <a href="index.php"></a>
                <img src="assets/images/logo_back.png" alt="Logo">
            </div>
            <h1>返回首页</h1>
        </div> 
    </div>
    <div class="right-section">
        <div class="form-container">
            <h2>管理员注册</h2>
            <?php if ($errMsg): ?>
                <div class="error-message"><?php echo htmlspecialchars($errMsg); ?></div>
            <?php elseif ($successMsg): ?>
                <div class="error-message" style="background:#e8f5e9;color:#388e3c;border-color:#388e3c;">
                    <?php echo htmlspecialchars($successMsg); ?>
                </div>
            <?php endif; ?>
            <form method="post" autocomplete="off">
                <label for="username">用户名</label>
                <input type="text" id="username" name="username" maxlength="32" required placeholder="请输入用户名">

                <label for="password">密码</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" maxlength="32" required placeholder="请输入密码">
                    <i class="fas fa-eye" id="togglePwd" style="cursor:pointer;"></i>
                </div>

                <label for="role">身份选择</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="admin" <?php if(isset($_POST['role']) && $_POST['role']=='admin') echo 'selected'; ?>>普通管理员</option>
                    <option value="superadmin" <?php if(isset($_POST['role']) && $_POST['role']=='superadmin') echo 'selected'; ?>>超级管理员</option>
                </select>


                <label for="admin_code">管理员身份码</label>
                <input type="password" id="admin_code" name="admin_code" maxlength="32" required placeholder="请输入管理员身份码">

                <button type="submit" class="btn"><i class="fas fa-user-plus"></i> 注册</button>
            </form>
            <p>已有账号？<a href="login.php">返回登录</a></p>
        </div>
    </div>
</div>
<script>
document.getElementById('togglePwd').onclick = function() {
    var pwd = document.getElementById('password');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        this.classList.remove('fa-eye');
        this.classList.add('fa-eye-slash');
    } else {
        pwd.type = 'password';
        this.classList.remove('fa-eye-slash');
        this.classList.add('fa-eye');
    }
};
</script>
</body>
</html>