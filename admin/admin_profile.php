<?php
require_once __DIR__ . '/includes/AdminHelper.php';
require_once __DIR__ . '/../config/db_config.php';
require_once __DIR__ . '/../includes/auth.php'; // 包含登录验证
check_admin_login();

$adminId = $_SESSION['admin']['admin_id'];
$helper = new AdminHelper($conn);
$admin = $helper->getAdminById($adminId);

$success = $error = null;

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK && isset($_POST['avatar_upload'])) {
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    $filetype = $_FILES['avatar']['type'];
    $ext = $allowed[$filetype] ?? '';
    if (!$ext) {
        $error = "仅支持jpg/png格式头像";
    } else {
        $username = $admin['username'];
        $target = __DIR__ . "/../assets/images/head_{$username}.$ext";
        // 删除旧头像（jpg/png）
        foreach (['jpg', 'png'] as $oldExt) {
            $old = __DIR__ . "/../assets/images/head_{$username}.$oldExt";
            if (file_exists($old)) unlink($old);
        }
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
            $success = "头像上传成功！";
        } else {
            $error = "头像上传失败，请重试";
        }
    }
}else if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_edit'])) {
    $newUsername = trim($_POST['username'] ?? '');
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if(!empty($oldPassword)) {
        // 验证旧密码
        if (!$helper->checkOldPassword($adminId, $oldPassword)) {
            $error = "原密码错误，请重新输入";
        }
    }

    if ($newUsername === '') {
        $error = "用户名不能为空";
    }else if ($newPassword !== $confirmPassword) {
        $error = "两次输入的密码不一致";
    }else if (empty($newPassword)){
        $error = "请输入新密码";
    }else {
        $update = $helper->updateAdmin($adminId, $newUsername, $newPassword);
        if ($update) {
            // 修改成功，强制退出重新登录
            session_destroy();
            header('Location: /../StuDorm_ManagementSystem/login.php?msg=info_updated');
            exit;
        } else {
            $error = "修改失败，请重试";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="UTF-8">
    <title>个人空间 - 管理员</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin_profile.css">
</head>
<body>
    <div class="profile-container">
        <header class="admin-header">
            <div class="logo" alt="大学 Logo">
                <a href="../index.php">
                    <img src="../assets/images/logo_blue.png" alt="大学 Logo" class="logo-img">
                </a>   
                <span class="logo-text">宿舍管理系统-个人空间</span>         
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
                <button class="back-btn" onclick="location.href='admin_dashboard.php' ">
                    <i class="fas fa-arrow-left"></i> 返回
                </button>
            </div>
        </header>
        <div class="profile-avatar">
            <form method="post" enctype="multipart/form-data" style="display:inline;">
                <input type = "hidden" name="avatar_upload" value="1">
                <label for="avatar_upload" style="cursor:pointer;">
                    <?php
                    $imgPathJpg = "../assets/images/head_" . $admin['username'] . ".jpg";
                    $imgPathPng = "../assets/images/head_" . $admin['username'] . ".png";
                    if (file_exists(__DIR__ . "/../assets/images/head_" . $admin['username'] . ".jpg")) {
                        $imgShow = $imgPathJpg;
                    } elseif (file_exists(__DIR__ . "/../assets/images/head_" . $admin['username'] . ".png")) {
                        $imgShow = $imgPathPng;
                    } else {
                        $imgShow = "";
                    }
                    ?>
                    <?php if ($imgShow): ?>
                        <img src="<?php echo $imgShow; ?>" alt="头像" style="width:80px;height:80px;border-radius:50%;object-fit:cover;">
                    <?php else: ?>
                        <i class="fas fa-user-tie" style="font-size:70px;color:#bbb;"></i>
                    <?php endif; ?>
                     <!-- input type="file"调出资源管理器 --> 
                    <input type="file" id="avatar_upload" name="avatar" accept=".jpg,.png" style="display:none;" onchange="this.form.submit()">
                </label>
                <div style="font-size:13px;color:#4361ee;margin-top:6px;">点击头像上传</div>
            </form>
        </div>
        <div class="profile-info">
            <h2><?php echo htmlspecialchars($admin['username']); ?></h2>
            <p>权限：<?php echo htmlspecialchars($admin['admin_level'] ?? '普通管理员'); ?></p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php elseif ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="post" class="profile-form">
            <input type="hidden" name="profile_edit" value="1">
            <label for="username">用户名</label>
            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" readonly style="background:#f5f5f5;cursor:not-allowed;">
            
            <label for="password">旧密码</label>
            <input type="password" id="old_password" name="old_password" placeholder="请输入原密码，不修改留空">
            
            <label for="password">新密码</label>
            <input type="password" id="password" name="password" placeholder="如不修改请留空">

            <label for="confirm_password">确认新密码</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="如不修改请留空">

            <button type="submit">保存修改</button>
        </form>
    </div>
</body>
</html>