<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
session_guard('admin');
$db=get_db(); $range=date_range();
$staff_rows=$db->prepare("SELECT u.name AS 'Staff Member',COUNT(l.id) AS 'Total Actions',SUM(l.action='login') AS 'Logins',SUM(l.action='approve_txn') AS 'Approvals',SUM(l.action='reject_txn') AS 'Rejections',SUM(l.action='process_return') AS 'Returns Processed',SUM(l.action='adjust_stock') AS 'Stock Adjustments',SUM(l.action='add_item') AS 'Items Added',MAX(l.created_at) AS 'Last Active' FROM logs l JOIN users u ON u.id=l.user_id WHERE u.role IN ('admin','staff') AND DATE(l.created_at) BETWEEN :from AND :to GROUP BY u.id ORDER BY `Total Actions` DESC");
$staff_rows->execute([':from'=>$range['from'],':to'=>$range['to']]); $staff_rows=$staff_rows->fetchAll();
$action_counts=$db->prepare("SELECT l.action AS label,COUNT(*) AS value FROM logs l JOIN users u ON u.id=l.user_id WHERE u.role IN ('admin','staff') AND DATE(l.created_at) BETWEEN :from AND :to GROUP BY l.action ORDER BY value DESC LIMIT 10");
$action_counts->execute([':from'=>$range['from'],':to'=>$range['to']]); $action_counts=$action_counts->fetchAll();
$recent=$db->prepare("SELECT l.created_at,u.name AS staff,l.action,l.details,l.ip FROM logs l JOIN users u ON u.id=l.user_id WHERE u.role IN ('admin','staff') AND DATE(l.created_at) BETWEEN :from AND :to ORDER BY l.created_at DESC LIMIT 100");
$recent->execute([':from'=>$range['from'],':to'=>$range['to']]); $recent=$recent->fetchAll();
$active_nav='reports';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Staff Activity Report — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/reports.css">
</head><body><?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content"><header class="topbar"><h2>Reports</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body">
<?php require __DIR__.'/partials/report_tabs.php'; ?>
<div class="report-header"><h3 class="report-title">Staff Activity Report</h3>
<button class="btn btn--ghost btn--sm export-btn" data-type="staff_activity" data-params="<?= e(http_build_query($range)) ?>">⬇ Export CSV</button></div>
<?php require __DIR__.'/partials/date_filter.php'; ?>
<div class="table-wrap" style="margin-bottom:1.5rem"><table class="data-table report-table">
<thead><tr><th>Staff Member</th><th>Total Actions</th><th>Logins</th><th>Approvals</th><th>Rejections</th><th>Returns</th><th>Stock Adj.</th><th>Last Active</th></tr></thead>
<tbody><?php if(empty($staff_rows)): ?><tr><td colspan="8" class="table-empty">No staff activity in this period.</td></tr>
<?php else: foreach($staff_rows as $r): ?>
<tr><td><strong><?= e($r['Staff Member']) ?></strong></td><td><?= (int)$r['Total Actions'] ?></td><td><?= (int)$r['Logins'] ?></td><td><?= (int)$r['Approvals'] ?></td><td><?= (int)$r['Rejections'] ?></td><td><?= (int)$r['Returns Processed'] ?></td><td><?= (int)$r['Stock Adjustments'] ?></td><td><?= fmt_date($r['Last Active'],'M d, Y H:i') ?></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<div class="chart-row">
    <div class="chart-card"><h4 class="chart-title">Top Actions</h4><div class="chart-wrap"><canvas id="actionBreakdownChart"></canvas></div></div>
    <div class="chart-card"><h4 class="chart-title">Recent Activity Log</h4>
    <div class="activity-log">
        <?php if(empty($recent)): ?><p class="table-empty">No log entries.</p>
        <?php else: foreach($recent as $log): ?>
        <div class="activity-entry"><span class="activity-action"><?= e($log['action']) ?></span><span class="activity-staff"><?= e($log['staff']) ?></span><span class="activity-detail"><?= e($log['details']??'') ?></span><span class="activity-time"><?= fmt_date($log['created_at'],'M d H:i') ?></span></div>
        <?php endforeach; endif; ?>
    </div></div>
</div>
</section></main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>const APP_URL='<?= e(APP_URL) ?>';const chartData={actions:{labels:<?= json_encode(array_column($action_counts,'label')) ?>,values:<?= json_encode(array_map('intval',array_column($action_counts,'value'))) ?>}};</script>
<script src="<?= e(APP_URL) ?>/assets/js/reports.js"></script>
</body></html>
