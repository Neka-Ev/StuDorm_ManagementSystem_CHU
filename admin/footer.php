<button class="backToTopBtn" id="backToTopBtn" title="返回顶部" aria-label="返回顶部">
    <i class="fas fa-arrow-up"></i>
</button>
<footer class="admin-footer">
            <div class="footer-content">
                <div class="footer-logo" alt="大学 Logo">
                    <a href="../index.php">
                        <img src="../assets/images/logo_blue.png" alt="大学 Logo" class="footer-logo-img">
                    </a>   
                     <span class="logo-text">宿舍管理系统</span>         
                </div>
                <div class="footer-info">
                    <p>&copy;<?php echo date('Y'); ?> Chang'an House Dormitory Management System, All Rights Reserved</p>
                    <p>< 版本 1.2.0 ></a></p>
                </div>
                <div class="footer-stats">
                    <p><i class="fas fa-user"></i> 当前用户: <?php echo htmlspecialchars($_SESSION['admin']['username'] ?? '管理员'); ?></p>
                    <p><i class="fas fa-clock"></i> 最后登录: <?php 
                        if(isset($_SESSION['admin']['last_login'])) {
                            // 处理DateTime对象或字符串
                            $lastLogin = $_SESSION['admin']['last_login'];
                            if (is_object($lastLogin) && get_class($lastLogin) === 'DateTime') {
                                echo $lastLogin->format('Y-m-d H:i');
                            } else {
                                echo date('Y-m-d H:i', strtotime((string)$lastLogin));
                            }
                        } else {
                            echo '首次登录';
                        }
                    ?></p>
                </div>
            </div>
        </footer>
    </div>                    
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>