<?php
require_once __DIR__ . '/../includes/DashboardHelper.php';
require_once __DIR__ . '/../../config/db_config.php';

$helper = new DashboardHelper($conn);

$studentCount = $helper->getStudentCount();
$dormCount = $helper->getDormCount();
$adminCount = $helper->getAdminCount();
$occupancyRate = $helper->getOccupancyRate();
$recentActivities = $helper->getRecentActivities(10);
$buildingOverview = $helper->getBuildingOverviewData();
?>
<section id="dashboard-content" class="content-section active">
    <div class="content-header">
        <h2><i class="fas fa-tachometer-alt"></i> 主页</h2>
        <p>系统概览与关键指标</p>
    </div>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3>学生总数</h3>
                <p><?php echo $studentCount; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-bed"></i>
            </div>
            <div class="stat-info">
                <h3>宿舍总数</h3>
                <p><?php echo $dormCount; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-info">
                <h3>管理员数量</h3>
                <p><?php echo $adminCount; ?></p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="stat-info">
                <h3>入住率</h3>
                <p><?php echo $occupancyRate; ?></p>
            </div>
        </div>
    </div>
    
    <div class="dashboard-table">
    <h3>宿舍楼入住总览</h3>
    <div class="dashboard-table-wrap">
        <table class="table table-bordered table-striped dashboard-overview-table">
            <thead>
                <tr>
                    <th>宿舍楼</th>
                    <th>性别</th>
                    <th>入住人数</th>
                    <th>绑定学院</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($buildingOverview as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['building_name']); ?></td>
                    <td><?php echo $item['gender'] === 'M' ? '男' : '女'; ?></td>
                    <td><?php echo $item['occupancy']; ?></td>
                    <td>
                        <?php
                        if (!empty($item['colleges'])) {
                            echo htmlspecialchars(implode('，', $item['colleges']));
                        } else {
                            echo '<span class="text-muted">未绑定</span>';
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($buildingOverview)): ?>
                <tr>
                    <td colspan="4" class="text-center">暂无数据</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
    
    <div class="recent-activity">
    <h3>最近活动</h3>
    <div class="activity-list activity-list-scroll">
        <?php foreach ($recentActivities as $activity): ?>
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="activity-content">
                    <p>
                        <strong><?php echo htmlspecialchars($activity['student_name']); ?></strong>
                        被
                        <strong><?php echo htmlspecialchars($activity['admin_level'].' - '.$activity['admin_name']); ?></strong>
                        <?php if (!empty($activity['from_room_number'])): ?>
                            从
                            <strong><?php echo htmlspecialchars($activity['from_building_name'] . ' ' . $activity['from_room_number']); ?></strong>
                            调换到
                        <?php else: ?>
                            分配到
                        <?php endif; ?>
                        <strong>
                            <?php echo htmlspecialchars($activity['to_building_name'] . ' ' . $activity['to_room_number']); ?>
                        </strong>
                        ， 原因:
                        <?php if (!empty($activity['reason'])):?>
                            <small style = "color:brown"> <?php echo htmlspecialchars($activity['reason']); ?> </small>
                        <?php else:?>
                            <small> 未填写原因 </small>
                        <?php endif;?>
                    </p>
                    <small>
                        <?php echo $activity['transfer_date'] instanceof DateTime
                        ? $activity['transfer_date']->format('Y-m-d H:i')
                        : htmlspecialchars($activity['transfer_date']); ?>
                    </small>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($recentActivities)): ?>
            <div class="activity-item">
                <div class="activity-content">
                    <p>暂无最近活动</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
</section>