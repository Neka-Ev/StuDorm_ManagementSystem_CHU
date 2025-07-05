<?php
include 'header.php';
?>

<div class="admin-main">
    <!-- 左侧导航栏 -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <div class="admin-avatar">
                <img src="../assets/images/head_<?php echo $_SESSION['admin']['username'] ?>.jpg"
                        alt="管理员头像"
                        onerror="this.onerror=null;this.src='../assets/images/head_<?php echo $_SESSION['admin']['username'] ?>.png';">
            </div>
            <div class="admin-info" >
                <h3><?php echo $_SESSION['admin']['username'] ?? '管理员'; ?></h3>
                <p style = "color:<?php echo ($_SESSION['admin']['role']=='super_admin')?'red':'#17a2b8'?>">&lt;<?php echo $_SESSION['admin']['full_name'] ?? '管理员'; ?>&gt;</p>
            </div>
        </div>  <!-- $_SESSION是超全局数组，能够在不同页面保存定义的用户数据 -->

        <nav class="sidebar-nav">
            <ul>
                <li class="active">
                    <a href="#dashboard" class="nav-link" data-content="dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>系统主页</span>
                    </a>
                </li>
                <li>
                    <a href="#auto-assign" class="nav-link" data-content="autoassign">
                        <i class="fas fa-cogs"></i>
                        <span>自动分配宿舍</span>
                    </a>
                </li>
                <li>
                    <a href="#dormtransfer" class="nav-link" data-content="dormtransfer">
                        <i class="fas fa-bed"></i>
                        <span>学生宿舍调配</span>
                    </a>
                </li>
                <li>
                    <a href="#stumanage" class="nav-link" data-content="stumanage">
                        <i class="fas fa-chart-bar"></i>
                        <span>学生住宿管理</span>
                    </a>
                </li>
                <li>
                    <a href="#dormnanage" class="nav-link" data-content="dormmanage">
                        <i class="fas fa-home"></i>
                        <span>宿舍入住管理</span>
                    </a>
                </li>           
                <li>
                    <a href="#about" class="nav-link" data-content="about">
                        <i class="fas fa-cog"></i>
                        <span>关于系统</span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- 主内容区 -->
    <main class="admin-content">   
        <!-- 内容区将在这里动态加载 -->
    </main>
</div>

<?php include 'footer.php'; ?>