<?php
require_once __DIR__ . '/../includes/DormitoryAllocator.php';
require_once __DIR__ . '/../includes/AutoAssignHelper.php';
require_once __DIR__ . '/../../config/db_config.php';


$allocator = new DormitoryAllocator($conn);
$helper = new AutoAssignHelper($conn);

$success = $error = $warning = null;

// 处理绑定配置表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bind_config'])) {
    $collegeId = intval($_POST['autoassign_college_id']);
    $selectedBuildings = $_POST['buildings'] ?? [];

    // 校验：至少有一个男生宿舍和一个女生宿舍
    if (!$helper->validateBinding($selectedBuildings)) {
        $error = "请至少选择一个男生宿舍楼和一个女生宿舍楼进行绑定。";
    } else {
        $helper->saveBinding($collegeId, $selectedBuildings);
        $success = "绑定关系已更新";
    }
}

// 处理自动分配表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign'])) {
    $collegeId = intval($_POST['autoassign_college_id']);
    // 只允许分配本学院已绑定的宿舍楼
    $maleBuildings = [];
    $femaleBuildings = [];
    foreach ($helper->collegeBindings[$collegeId] ?? [] as $bid) {
        if ($helper->buildings[$bid]['gender'] == 'M') $maleBuildings[] = $bid;
        if ($helper->buildings[$bid]['gender'] == 'F') $femaleBuildings[] = $bid;
    }
    $adminId = $_SESSION['admin']['admin_id'] ?? 1;

    try {
        $result = $allocator->autoAssignByCollegeAndGender($collegeId, $maleBuildings, $femaleBuildings, $adminId);
        $success = "成功分配: " . $result['assigned'] . "名学生";
        if ($result['unassigned'] > 0) {
            $warning = "有 " . $result['unassigned'] . "名学生未能分配(可能没有合适的宿舍)";
        }
    } catch (Exception $e) {
        $error = "分配失败: " . $e->getMessage();
    }
}

// 获取剩余未分配人数和宿舍楼空房间数
$selectedCollegeId = isset($_POST['autoassign_college_id']) ? intval($_POST['autoassign_college_id']) : null;
$unassignedCount = $selectedCollegeId ? $helper->getUnassignedCountByGender($selectedCollegeId) : ['M'=>0, 'F'=>0];
$buildingVacancy = $helper->getBuildingVacancy();
?>
    <!-- 下方绑定配置与自动分配表单 -->
    <div class="binding-config">
        <h2><i class="fas fa-cogs"></i> 配置学院可用宿舍楼 & 自动分配</h2>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if (isset($warning)): ?>
            <div class="alert alert-warning"><?php echo $warning; ?></div>
        <?php endif; ?>

        <!-- 绑定配置表单 -->
        <form method="post" class="auto-assign-form">
            <div class="form-group">
                <label for="college_id"><i class="fas fa-university"></i> 选择学院</label>
                <select id="college_id" name="autoassign_college_id" class="form-control" required>
                    <option value="">-- 请选择学院 --</option>
                    <?php foreach ($helper->colleges as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php if ($selectedCollegeId == $id) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($selectedCollegeId): ?>
                <div style="margin-bottom:12px;color:#3a0ca3;">
                    当前学院未分配学生：男生 <?php echo $unassignedCount['M']; ?> 人，女生 <?php echo $unassignedCount['F']; ?> 人
                </div>
                <div class="building-selection">
                    <?php foreach (['M' => '男生宿舍楼', 'F' => '女生宿舍楼'] as $gender => $label): ?>
                        <div class="gender-group">
                            <strong><?php echo $label; ?></strong>
                            <?php foreach ($helper->buildings as $bid => $building): ?>
                                <?php if ($building['gender'] == $gender): ?>
                                    <div class="building-option">
                                        <?php
                                        // 允许多学院绑定，不做禁用
                                        $checked = (isset($helper->collegeBindings[$selectedCollegeId]) && in_array($bid, $helper->collegeBindings[$selectedCollegeId]));
                                        ?>
                                        <input type="checkbox"
                                               name="buildings[]"
                                               value="<?php echo $bid; ?>"
                                               id="building_<?php echo $bid; ?>"
                                               <?php echo $checked ? 'checked' : ''; ?>
                                        >
                                        <label for="building_<?php echo $bid; ?>">
                                            <?php echo htmlspecialchars($building['building_name']); ?>
                                            <span style="color:#888;font-size:13px;">（剩余床位：<?php echo isset($buildingVacancy[$bid]) ? $buildingVacancy[$bid] : '未知'; ?>）</span>
                                        </label>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" name="bind_config">
                        <i class="fas fa-save"></i> 保存关联配置
                    </button>
                </div>
            <?php endif; ?>
        </form>

        <!-- 自动分配表单（仅在选择学院后显示） -->
        <?php if ($selectedCollegeId): ?>
        <form method="post" class="auto-assign-form" style="margin-top:18px;">
            <input type="hidden" name="autoassign_college_id" value="<?php echo intval($selectedCollegeId); ?>">
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" name="assign">
                    <i class="fas fa-user-plus"></i> 执行自动分配
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <div class="autoassign-content">
    <!-- 绑定关系表格 -->
    <div class="binding-status">
        <h2><i class="fas fa-link"></i> 宿舍楼与学院绑定情况</h2>
        <div style="overflow-x:auto;" class="unassigned-students-table-wrap">
        <table class="table unassigned-students-table">
            <thead>
                <tr>
                    <th>宿舍楼</th>
                    <th>性别</th>
                    <th>已绑定学院</th>
                    <th>剩余床位</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($helper->buildings as $id => $building): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($building['building_name']); ?></td>
                        <td><?php echo $building['gender'] == 'M' ? '男' : '女'; ?></td>
                        <td>
                            <?php
                            if (isset($helper->bindings[$id])) {
                                $names = [];
                                foreach ($helper->bindings[$id] as $cid) {
                                    $names[] = isset($helper->colleges[$cid]) ? htmlspecialchars($helper->colleges[$cid]) : '未知';
                                }
                                echo implode('，', $names);
                            } else {
                                echo '<span style="color:gray">未绑定</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php echo isset($buildingVacancy[$id]) ? $buildingVacancy[$id] : '未知'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- 未分配学生列表 -->
    <div class="unassigned-students">
        <h4><i class="fas fa-user-times"></i> 未分配学生列表</h4>
        <div style="overflow-x:auto;" class="unassigned-students-table-wrap">
        <table class="table unassigned-students-table">
            <thead>
                <tr>
                    <th>学号</th>
                    <th>姓名</th>
                    <th>性别</th>
                    <th>学院</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT s.student_id, s.full_name, s.gender, c.college_name 
                     FROM Students s 
                     JOIN Colleges c ON s.college_id = c.college_id
                     WHERE s.dorm_room_id IS NULL";
                if(!empty($selectedCollegeId)) {
                    $sql .= " AND s.college_id = ?";    //选择学院查学院
                    $unassigned = sqlsrv_query($conn, $sql, [intval($selectedCollegeId)]);
                }else {
                    $unassigned = sqlsrv_query($conn, $sql); // 未选择学院查全部
                }                              
                while ($row = sqlsrv_fetch_array($unassigned, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $row['student_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo $row['gender'] == 'M' ? '男' : '女'; ?></td>
                        <td><?php echo htmlspecialchars($row['college_name']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>

    <div class="unassigned-students">
        <h4><i class="fas fa-user-check"></i> 已分配学生列表</h4>
        <div style="overflow-x:auto;" class="unassigned-students-table-wrap">
        <table class="table unassigned-students-table">
            <thead>
                <tr>
                    <th>学号</th>
                    <th>姓名</th>
                    <th>性别</th>
                    <th>宿舍</th>
                    <th>学院</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT s.student_id, s.full_name, s.gender, s.college_id, c.college_name, 
                        b.building_name, r.room_number, s.dorm_room_id
                        FROM Students s
                        JOIN Colleges c ON s.college_id = c.college_id
                        LEFT JOIN DormitoryBuildings b ON s.dorm_building_id = b.building_id
                        LEFT JOIN DormitoryRooms r ON s.dorm_room_id = r.room_id
                        WHERE s.dorm_room_id IS NOT NULL";
                if(!empty($selectedCollegeId)) {
                    $sql .= " AND s.college_id = ?";    //选择学院查学院
                    $unassigned = sqlsrv_query($conn, $sql, [intval($selectedCollegeId)]);
                }else {
                    $unassigned = sqlsrv_query($conn, $sql); // 未选择学院查全部
                }                              
                while ($row = sqlsrv_fetch_array($unassigned, SQLSRV_FETCH_ASSOC)): ?>
                    <tr>
                        <td><?php echo $row['student_id']; ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo $row['gender'] == 'M' ? '男' : '女'; ?></td>
                        <td><?php echo htmlspecialchars($row['building_name'] . ' - ' . $row['room_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['college_name']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>


