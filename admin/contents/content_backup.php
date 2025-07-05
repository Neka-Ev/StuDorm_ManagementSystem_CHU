<?php
require_once __DIR__ . '/../includes/BackupHelper.php';
require_once __DIR__ . '/../../config/db_config.php';
$backupHelper = new BackupHelper();
$backupFiles = $backupHelper->listBackups();
?>
<div class="manage-content">
    <h2><i class="fas fa-database"></i> 数据库备份与还原</h2>
    <div class="mb-3">
        <button class="btn btn-primary" id="manualBackupBtn"><i class="fas fa-save"></i> 手动备份</button>
        <button class="btn btn-secondary" id="autoBackupBtn"><i class="fas fa-clock"></i> 启用/关闭自动备份</button>
        <span id="autoBackupStatus" class="ml-2 text-info"></span>
    </div>
    <div class="alert alert-warning" style="font-size:0.95em;">
        <b>提示：</b>还原数据库前，建议先手动备份当前数据，以防数据丢失或不可逆操作。
    </div>
    <h5>备份文件列表</h5>
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>文件名</th>
                <th>创建时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($backupFiles as $file): ?>
            <tr>
                <td><?php echo htmlspecialchars($file['name']); ?></td>
                <td><?php echo htmlspecialchars($file['time']); ?></td>
                <td>
                    <button class="btn btn-sm btn-success restore-btn" data-file="<?php echo htmlspecialchars($file['name']); ?>">
                        <i class="fas fa-undo"></i> 还原
                    </button>
                    <a class="btn btn-sm btn-info" href="../backup/<?php echo urlencode($file['name']); ?>" download>
                        <i class="fas fa-download"></i> 下载
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($backupFiles)): ?>
            <tr><td colspan="3" class="text-center text-muted">暂无备份文件</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>