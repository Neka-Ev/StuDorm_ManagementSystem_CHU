<?php
class BackupHelper {
    private $backupDir;
    public function __construct() {
        $this->backupDir = realpath(__DIR__ . '/../../backup');
        if (!is_dir($this->backupDir)) mkdir($this->backupDir, 0777, true);
    }
    // 列出所有备份文件
    public function listBackups() {
        $files = [];
        foreach (glob($this->backupDir . '/*.bak') as $file) {
            $files[] = [
                'name' => basename($file),
                'time' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        usort($files, function($a, $b) { return strcmp($b['name'], $a['name']); });
        return $files;
    }
}
?>