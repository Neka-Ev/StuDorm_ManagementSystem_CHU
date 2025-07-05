<?php
require_once __DIR__ . '/../includes/StuManageHelper.php';
require_once __DIR__ . '/../../config/db_config.php';

$helper = new StuManageHelper($conn);
$colleges = $helper->getAllColleges();

$searchName = $_GET['search_name'] ?? '';
$searchCollege = $_GET['college_id'] ?? '';
$searchGrade = $_GET['grade'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 7;

// 处理添加
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
    $fullName = trim($_POST['full_name']);
    $gender = $_POST['gender'];
    $collegeId = $_POST['college_id'];
    $grade = $_POST['grade'];
    $stmt = $helper->addStudent($fullName, $gender, $collegeId, $grade);

    if ($stmt) {
        $success = "学生 $fullName 已成功添加！";
        // 清空搜索条件
        $searchName = '';
        $searchCollege = '';
        $searchGrade = '';
        $page = 1; // 重置到第一页
    } else {
        $error = "添加失败，权限不足！";
    }
}

// 处理删除
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_students'])) {
    $ids = $_POST['student_ids'] ?? [];
    $stmt = $helper->deleteStudents($ids);
   
    if(!$stmt) $error = "删除失败，权限不足！";

    if (!empty($ids) && $stmt) {
        $ids_str = implode('、', $ids);
        $success = "学号为：$ids_str 的学生已被删除！";
    }
}

$result = $helper->searchStudents($searchName, $searchCollege, $searchGrade, $page, $pageSize);
$students = $result['data'];
$total = $result['total'];
$totalPages = max(1, ceil($total / $pageSize));
?>
<div class="manage-content">
    <h2><i class="fas fa-user-graduate"></i> 学生管理</h2>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    <!-- 筛选表单 -->
    <div class="search-form">
        <h5><i class="fas fa-search"></i> 查询学生 </h5>
        <form method="get" class="form-inline" id="stuManageSearchForm">
            <input type="hidden" name="content" value="stumanage">
            <div class="form-group">
                <label for="search_name">姓名</label>
                <input type="text" id="search_name" name="search_name" class="form-control"
                       value="<?php echo htmlspecialchars($searchName); ?>" placeholder="请输入姓名">
            </div>
            <div class="form-group">
                <label for="college_id">学院</label>
                <select id="stu_college_id" name="college_id" class="form-control">
                    <option value="">全部</option>
                    <?php foreach ($colleges as $cid => $cname): ?>
                        <option value="<?php echo $cid; ?>" <?php if ($searchCollege == $cid) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cname); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="grade">年级</label>
                <input type="text" id="grade" name="grade" class="form-control"
                       value="<?php echo htmlspecialchars($searchGrade); ?>" placeholder="如2023">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> 查询
            </button>
        </form>
    </div>
    <!-- 添加学生表单 -->
    <div class="add-form" style="margin:18px 0;">
        <h5><i class="fas fa-add"></i> 录入学生 </h5>
        <form method="post" class="form-inline" id="addStudentForm">
            <input type="hidden" name="content" value="stumanage">
            <div class="form-group">
                <label for="add_full_name">姓名</label>
                <input type="text" id="add_full_name" name="full_name" class="form-control" required 
                     placeholder="请输入姓名">
            </div>
            <div class="form-group">
                <label for="add_gender">性别</label>
                <select id="add_gender" name="gender" class="form-control" required>
                    <option value="M">男</option>
                    <option value="F">女</option>
                </select>
            </div>
            <div class="form-group">
                <label for="add_college_id">学院</label>
                <select id="add_college_id" name="college_id" class="form-control" required>
                    <?php foreach ($colleges as $cid => $cname): ?>
                        <option value="<?php echo $cid; ?>"><?php echo htmlspecialchars($cname); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="add_grade">年级</label>
                <input type="text" id="add_grade" name="grade" class="form-control" required placeholder="如2023">
            </div>
            <button type="submit" name="add_student" class="btn btn-success">
                <i class="fas fa-plus"></i> 添加学生
            </button>
        </form>
    </div>
    <!-- 学生列表 -->
    <form method="post" id="deleteStudentsForm">
    <div class="student-list">
        <h4>学生列表</h4>
        <div class="table-wrap">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAllStu"></th>
                        <th>学号</th>
                        <th>姓名</th>
                        <th>学院</th>
                        <th>宿舍</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $stu): ?>
                        <tr>
                            <td><input type="checkbox" name="student_ids[]" value="<?php echo $stu['student_id']; ?>"></td>
                            <td><?php echo htmlspecialchars($stu['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($stu['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($stu['college_name']); ?></td>
                            <td>
                                <?php if ($stu['building_name'] && $stu['room_number']): ?>
                                    <?php echo htmlspecialchars($stu['building_name'] . '-' . $stu['room_number']); ?>
                                <?php else: ?>
                                    <span class="text-muted">未分配</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-danger single-del-btn" data-stu-id="<?php echo $stu['student_id']; ?>">删除</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="6" class="text-center">暂无数据</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <!-- 分页 -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="分页" style="margin:16px 0;">
            <ul class="pagination justify-content-center">
                <li class="page-item<?php if ($page <= 1) echo ' disabled'; ?>">
                    <a class="page-link" href="#" data-page="1">首页</a>
                </li>
                <li class="page-item<?php if ($page <= 1) echo ' disabled'; ?>">
                    <a class="page-link" href="#" data-page="<?php echo $page-1; ?>">上一页</a>
                </li>
                <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                    <li class="page-item<?php if ($i == $page) echo ' active'; ?>">
                        <a class="page-link" href="#" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item<?php if ($page >= $totalPages) echo ' disabled'; ?>">
                    <a class="page-link" href="#" data-page="<?php echo $page+1; ?>">下一页</a>
                </li>
                <li class="page-item<?php if ($page >= $totalPages) echo ' disabled'; ?>">
                    <a class="page-link" href="#" data-page="<?php echo $totalPages; ?>">末页</a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
        <div class="d-flex justify-content-end" style="margin:12px 0;">
        <button type="submit" name="delete_students" class="btn btn-danger" id="batchDelBtn">
            <i class="fas fa-trash"></i> 批量删除
        </button>
         </div>
    </div>
    
    </form>
</div>