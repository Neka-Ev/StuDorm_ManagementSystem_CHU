<?php
require_once __DIR__ . '/../includes/TransferHelper.php';
require_once __DIR__ . '/../../config/db_config.php';

$helper = new TransferHelper($conn);
$success = $error = null;

// 处理调换/分配表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transfer'])) {
    $studentId = intval($_POST['student_id']);
    $targetRoomId = intval($_POST['target_room']);
    $reason = $_POST['reason'] ?? '';
    $adminId = $_SESSION['admin']['admin_id'] ?? 1;
    // 允许未分配学生分配宿舍，原宿舍为空
    try {
        $result = $helper->processTransfer($studentId, $targetRoomId, $adminId, $reason);
        $success = "宿舍分配/调换成功完成";
    } catch (Exception $e) {
        $error = "操作失败: " . $e->getMessage();
    }
}
// 获取搜索参数
$searchName = $_GET['search_name'] ?? '';
$searchCollegeId = $_GET['college_id'] ?? '';   // 表单获得的college_id
// 分页参数
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 7; // 自定义每页条数
$total = 0;
// 获取所有学生 
$students = $helper->
    searchAllStudents($searchName, $searchCollegeId, $page, $pageSize, $total);

$pageCount = ceil($total / $pageSize);  // 计算总页数

// 获取所有宿舍楼
$buildings = $helper->getAllBuildings();
$colleges = $helper->getAllColleges();

// 获取所有宿舍楼与学院的绑定关系
$buildingBindings = $helper->getBuildingBindings();
?>
<div class="transfer-content">
    <h2><i class="fas fa-exchange-alt"></i> 学生宿舍调换/分配</h2>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <!-- 搜索表单 -->
    <div class="search-form">
        <form method="get" id = "dormTransferSearchForm" class="form-inline">
            <input type="hidden" name="content" value="search">
            <div class="form-group">
                <label for="search_name">学生姓名</label>
                <input type="text" id="search_name" name="search_name" class="form-control" 
                       value="<?php echo htmlspecialchars($searchName); ?>" placeholder="输入学生姓名">
            </div>
            <div class="form-group">
                <label for="college_id">所在学院</label>
                <select id="transfer_college_id" name="college_id" class="form-control">
                    <option value="">全部学院</option>
                    <?php foreach ($colleges as $id => $name): ?>
                        <option value="<?php echo $id; ?>" <?php if ($searchCollegeId == $id) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($name); ?>
                </option>
                    <?php endforeach; ?>
                </select>
            <div style="gap: 16px;"></div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> 搜索
            </button>
        </form>
    </div>
    <!-- 所有学生列表 -->
    <div class="student-list">
        <h4><i class="fas fa-users"></i> 学生列表</h4>
        <div class="table-wrap">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>学号</th>
                    <th>姓名</th>
                    <th>性别</th>
                    <th>学院</th>
                    <th>当前宿舍</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo $student['student_id']; ?></td>
                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td><?php echo $student['gender'] == 'M' ? '男' : '女'; ?></td>
                        <td><?php echo htmlspecialchars($student['college_name']); ?></td>
                        <td>
                            <?php if ($student['building_name'] && $student['room_number']): ?>
                                <?php echo htmlspecialchars($student['building_name'] . ' - ' . $student['room_number']); ?>
                            <?php else: ?>
                                <span class="text-muted">未分配</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($student['building_name'] && $student['room_number']): ?>
                                <button class="btn btn-sm btn-success transfer-btn" 
                                    data-student-id="<?php echo $student['student_id']; ?>"
                                    data-student-name="<?php echo htmlspecialchars($student['full_name']); ?>"
                                    data-student-gender="<?php echo $student['gender']; ?>"
                                    data-student-college-id="<?php echo $student['college_id']; ?>"
                                    data-student-college-name="<?php echo htmlspecialchars($student['college_name']); ?>">
                                    <i class="fas fa-exchange-alt"></i> 调换宿舍
                                </button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-primary transfer-btn" 
                                    data-student-id="<?php echo $student['student_id']; ?>"
                                    data-student-name="<?php echo htmlspecialchars($student['full_name']); ?>"
                                    data-student-gender="<?php echo $student['gender']; ?>"
                                    data-student-college-id="<?php echo $student['college_id']; ?>"
                                    data-student-college-name="<?php echo htmlspecialchars($student['college_name']); ?>">
                                    <i class="fas fa-plus"></i> 分配宿舍
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="6" class="text-center">没有找到匹配的学生</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>
    <!-- 分页 -->
    <?php if ($pageCount > 1): ?>
        <nav aria-label="分页" style="margin:16px 0;">
            <ul class="pagination justify-content-center">   <!-- 使用了网络css样式来显示分页区域 -->
                <li class="page-item<?php if ($page <= 1) echo ' disabled'; ?>">
                    <a class="page-link" href="#" data-page="1">首页</a>
                </li>
                <li class="page-item<?php if ($page <= 1) echo ' disabled'; ?>">
                    <a class="page-link" href="#" data-page="<?php echo $page-1; ?>">上一页</a>
                </li>
                <?php for ($i = max(1, $page-2); $i <= min($pageCount, $page+2); $i++): ?>
                    <li class="page-item<?php if ($i == $page) echo ' active'; ?>">
                        <a class="page-link" href="#" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item<?php if ($page >= $pageCount) echo ' disabled'; ?>">
                    <a class="page-link" href="#" data-page="<?php echo $page+1; ?>">下一页</a>
                </li>
                <li class="page-item<?php if ($page >= $pageCount) echo ' disabled'; ?>">
                    <a class="page-link" href="#" data-page="<?php echo $pageCount; ?>">末页</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    <!-- 调换区域（页面内隐藏/显示） -->
    <div id="transferPanel" style="display:none; margin:24px auto 0 auto; max-width:2048px; background:#fff; border-radius:12px; box-shadow:0 4px 24px rgba(67,97,238,0.10); padding:0 0 10px 0;">
        <div class="card" style="border:none; box-shadow:none; border-radius:12px; margin:0;">
            <form method="post" id="transferForm">
                <!-- JS中赋予值供给表单使用 -->
                <input type="hidden" name="student_id" id="modalStudentId">
                <input type="hidden" name="target_room" id="targetRoomId">           
                <input type="hidden" id="modalStudentGender" name="student_gender">
                <input type="hidden" id="modalStudentCollegeId" name="student_college_id">
                <div class="card-header" style="background: #f8f9fa; border-radius:12px 12px 0 0; border-bottom:1px solid #e3e8ee; position:relative; padding:18px 24px;">
                    <h5 class="mb-0" style="display:inline-block;"><i class="fas fa-exchange-alt"></i> 宿舍调换</h5>
                    <button type="button" class="close" id="closeTransferPanel" style="float:right; font-size:1.5rem; color:#888; background:none; border:none; outline:none;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="card-body" style="background: #fafdff; padding:24px 28px 18px 28px;">
                    <div class="form-group">
                        <label>学生信息</label>
                        <div class="form-control-plaintext" id="studentInfo"></div>
                    </div>
                    <div class="form-group">
                        <label for="reason">调换原因</label>
                        <textarea id="reason" name="reason" class="form-control" rows="3" placeholder="请输入调换原因"></textarea>
                    </div>
                    <div class="form-group">
                        <label>选择目标宿舍</label>
                        <div class="room-selection">
                            <!-- 男生宿舍 -->
                            <div class="gender-section" id="maleDormSection">
                                <div class="gender-title">男生宿舍</div>
                                <div class="building-col">
                                    <?php
                                    foreach ($buildings as $building):
                                        if ($building['gender'] == 'M'):
                                            $bindColleges = [];
                                            foreach ($buildingBindings as $collegeId => $bids) {
                                                if (in_array($building['building_id'], $bids)) $bindColleges[] = $collegeId;
                                            }
                                            // 保证为字符串
                                            $collegeIdsAttr = implode(',', $bindColleges);   // 为下方所有的楼容器获取其对应学院数据并写入，供后端使用
                                    ?>
                                        <!-- 宿舍楼容器 -->
                                        <div class="building-item-flex-vertical" data-college-ids="<?php echo htmlspecialchars($collegeIdsAttr); ?>">
                                            <div class="building-header">
                                                <a href="#" class="building-link" data-building-id="<?php echo $building['building_id']; ?>">
                                                    <?php echo htmlspecialchars($building['building_name']); ?>
                                                </a>
                                            </div>
                                            <div class="room-list-horizontal" id="rooms-<?php echo $building['building_id']; ?>" style="display:none;">
                                                <!-- 房间列表通过AJAX加载 -->
                                            </div>
                                        </div>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </div>
                            </div>
                            <!-- 女生宿舍 -->
                            <div class="gender-section" id="femaleDormSection" style="margin-top:24px;">
                                <div class="gender-title">女生宿舍</div>
                                <div class="building-col">
                                    <?php
                                    foreach ($buildings as $building):
                                        if ($building['gender'] == 'F'):
                                            $bindColleges = [];
                                            foreach ($buildingBindings as $collegeId => $bids) {
                                                if (in_array($building['building_id'], $bids)) $bindColleges[] = $collegeId;
                                            }
                                            $collegeIdsAttr = implode(',', $bindColleges);
                                    ?>
                                        <div class="building-item-flex-vertical" data-college-ids="<?php echo htmlspecialchars($collegeIdsAttr); ?>">
                                            <div class="building-header">
                                                <a href="#" class="building-link" data-building-id="<?php echo $building['building_id']; ?>">
                                                    <?php echo htmlspecialchars($building['building_name']); ?>
                                                </a>
                                            </div>
                                            <div class="room-list-horizontal" id="rooms-<?php echo $building['building_id']; ?>" style="display:none;">
                                                <!-- 房间列表通过AJAX加载 -->
                                            </div>
                                        </div>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2" style="background: #f8f9fa; border-radius:0 0 12px 12px; border-top:1px solid #e3e8ee; padding:14px 24px;">
                    <button type="button" class="btn btn-secondary me-2" id="cancelTransferPanel" style="min-width: 90px;">取消</button>
                    <button type="submit" name="transfer" class="btn btn-primary" style="min-width: 110px;">确认调配</button>
                </div>
        </div>            
    </form>    
</div>

</div>
</div>