<?php
require_once __DIR__ . '/../includes/DormManageHelper.php';
require_once __DIR__ . '/../../config/db_config.php';

$helper = new DormManageHelper($conn);

$buildings = $helper->getAllBuildings();
$colleges = $helper->getAllColleges();

$searchBuilding = $_GET['building_name'] ?? '';
$searchRoom = $_GET['room_number'] ?? '';
$searchCollege = $_GET['college_id'] ?? '';
$notShowEmpty = $_GET['not_show_empty'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$pageSize = 7;   //控制一页的显示个数

$result = $helper->searchDorms($searchBuilding, $searchRoom, $searchCollege, $notShowEmpty, $page, $pageSize);
$dorms = $result['data'];
$total = $result['total'];
$totalPages = max(1, ceil($total / $pageSize));
?>
<div class="manage-content">
    <h2><i class="fas fa-bed"></i> 宿舍入住管理</h2>
    <!-- 筛选表单 -->
    <div class="search-form">
        <form method="get" class="form-inline" id="dormManageSearchForm">
            <input type="hidden" name="content" value="dormmanage">
            <div class="form-group">
                <label for="building_name">宿舍楼</label>
                <select id="building_name" name="building_name" class="form-control">
                    <option value="">全部</option>
                    <?php foreach ($buildings as $bname): ?>
                        <option value="<?php echo htmlspecialchars($bname); ?>" <?php if ($searchBuilding == $bname) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($bname); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="room_number">房间号</label>
                <input type="text" id="room_number" name="room_number" class="form-control"
                       value="<?php echo htmlspecialchars($searchRoom); ?>" placeholder="输入房间号">
            </div>
            <div class="form-group">
                <label for="college_id">学院</label>
                <select id="manage_college_id" name="college_id" class="form-control">
                    <option value="">全部</option>
                    <?php foreach ($colleges as $cid => $cname): ?>
                        <option value="<?php echo $cid; ?>" <?php if ($searchCollege == $cid) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cname); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="min-width:120px;">
                <label>
                    <input type="checkbox" name="not_show_empty" value="1" <?php if ($notShowEmpty === '1') echo 'checked'; ?>>
                    不显示空宿舍
                </label>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> 搜索
            </button>
        </form>
    </div>
    <!-- 宿舍信息表格 -->
    <div class="student-list">
        <h4>宿舍信息列表</h4>
        <div class="table-wrap">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>宿舍楼</th>
                        <th>房间号</th>
                        <th>宿舍类型</th>
                        <th>1号床</th>
                        <th>2号床</th>
                        <th>3号床</th>
                        <th>4号床</th>
                        <th>入住人数</th>
                        <th>执行操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dorms as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['building_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['room_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['dorm_type']); ?></td>
                            <td><?php echo htmlspecialchars($row['occupant1'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['occupant2'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['occupant3'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($row['occupant4'] ?? ''); ?></td>
                            <td><?php echo intval($row['actual_count']); ?></td>
                            <td>
                                <button class="btn btn-sm btn-info dorm-attr-btn"
                                data-room-id="<?php echo $row['room_id']; ?>">
                                <i class="fas fa-cog"></i> 属性
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($dorms)): ?>
                        <tr>
                            <td colspan="8" class="text-center">暂无数据</td>
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
    </div>
    <!-- 宿舍属性设置面板(隐藏) -->
    <div id="dormAttrPanel" style="display:none; position:fixed; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.15); z-index:9999;">
    <div style="background:#fff; max-width:520px; margin:60px auto; border-radius:12px; box-shadow:0 4px 24px rgba(67,97,238,0.10); padding:0 0 10px 0; position:relative;">
        <div class="card-header" style="background: #f8f9fa; border-radius:12px 12px 0 0; border-bottom:1px solid #e3e8ee; position:relative; padding:18px 24px;">
            <h5 class="mb-0" style="display:inline-block;"><i class="fas fa-cog"></i> 宿舍属性设置</h5>
            <button type="button" class="close" id="closeDormAttrPanel" style="float:right; font-size:1.5rem; color:#888; background:none; border:none; outline:none;">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <form id="dormAttrForm" style="padding:24px 28px 18px 28px;">
            <input type="hidden" name="room_id" id="attrRoomId">
            <div class="form-group">
                <label>宿舍楼</label>
                <input type="text" class="form-control" id="attrBuildingName" readonly>
            </div>
            <div class="form-group">
                <label>宿舍号</label>
                <input type="text" class="form-control" id="attrRoomNumber" readonly>
            </div>
            <div class="form-group">
                <label for="attrDormType">宿舍类型</label>
                <select class="form-control" id="attrDormType" name="dorm_type" required>
                    <option value="文明宿舍">文明宿舍</option>
                    <option value="普通宿舍">普通宿舍</option>
                    <option value="示范宿舍">示范宿舍</option>
                </select>
            </div>
            <div class="form-group">
                <label>宿舍成员</label>
                <table class="table table-bordered table-sm" id="attrMemberTable">
                    <thead>
                        <tr>
                            <th>床号</th>
                            <th>姓名</th>
                            <th>性别</th>
                            <th>年级</th>
                            <th>学院</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- JS填充 -->
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary me-2" id="cancelDormAttrPanel" style="min-width: 90px;">取消</button>
                <button type="submit" class="btn btn-primary" style="min-width: 110px;">保存</button>
            </div>
        </form>
    </div>
</div>
</div>