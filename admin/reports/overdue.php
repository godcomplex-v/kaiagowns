<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
session_guard('admin');
$db=get_db();
$rows=$db->query("SELECT t.id AS '#',u.name AS 'Customer',u.phone AS 'Phone',i.name AS 'Item',t.borrow_date AS 'Borrow Date',t.due_date AS 'Due Date',DATEDIFF(CURDATE(),t.due_date) AS 'Days Late',(DATEDIFF(CURDATE(),t.due_date)*".PENALTY_PER_DAY.") AS 'Est. Penalty' FROM transactions t JOIN users u ON u.id=t.customer_id JOIN items i ON i.id=t.item_id WHERE t.status='overdue' ORDER BY `Days Late` DESC")->fetchAll();
$summary=$db->query("SELECT COUNT(*) AS total,SUM(DATEDIFF(CURDATE(),due_date)) AS total_days,(SUM(DATEDIFF(CURDATE(),due_date))*".PENALTY_PER_DAY.") AS est_penalty FROM transactions WHERE status='overdue'")->fetch();
$dist=$db->query("SELECT SUM(DATEDIFF(CURDATE(),due_date) BETWEEN 1 AND 3) AS '1-3 days',SUM(DATEDIFF(CURDATE(),due_date) BETWEEN 4 AND 7) AS '4-7 days',SUM(DATEDIFF(CURDATE(),due_date) BETWEEN 8 AND 14) AS '8-14 days',SUM(DATEDIFF(CURDATE(),due_date)>14) AS '15+ days' FROM transactions WHERE status='overdue'")->fetch();
$active_nav='reports';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Overdue Report — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/transactions.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/reports.css">
</head><body><?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content"><header class="topbar"><h2>Reports</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body">
<?php require __DIR__.'/partials/report_tabs.php'; ?>
<div class="report-header"><h3 class="report-title">Overdue Report <span class="report-subtitle">— current snapshot</span></h3>
<button class="btn btn--ghost btn--sm export-btn" data-type="overdue" data-params="">⬇ Export CSV</button></div>
<div class="summary-grid">
    <div class="summary-card summary-card--danger"><span class="summary-num"><?= (int)$summary['total'] ?></span><span class="summary-label">Overdue Items</span></div>
    <div class="summary-card summary-card--warn"><span class="summary-num"><?= (int)$summary['total_days'] ?></span><span class="summary-label">Total Days Late</span></div>
    <div class="summary-card summary-card--danger"><span class="summary-num"><?= fmt_money($summary['est_penalty']??0) ?></span><span class="summary-label">Est. Total Penalty</span></div>
</div>
<div class="chart-row"><div class="chart-card chart-card--full"><h4 class="chart-title">Days-Late Distribution</h4><div class="chart-wrap chart-wrap--sm"><canvas id="overdueDistChart"></canvas></div></div></div>
<div class="table-wrap"><table class="data-table report-table">
<thead><tr><th>#</th><th>Customer</th><th>Phone</th><th>Item</th><th>Borrow Date</th><th>Due Date</th><th>Days Late</th><th>Est. Penalty</th></tr></thead>
<tbody><?php if(empty($rows)): ?><tr><td colspan="8" class="table-empty">No overdue items. 🎉</td></tr>
<?php else: foreach($rows as $r): ?>
<tr class="overdue-row"><td><?= (int)$r['#'] ?></td><td><?= e($r['Customer']) ?></td><td><?= e($r['Phone']??'—') ?></td><td><?= e($r['Item']) ?></td>
<td><?= fmt_date($r['Borrow Date']) ?></td><td><?= fmt_date($r['Due Date']) ?></td>
<td><span class="days-pill due-critical"><?= (int)$r['Days Late'] ?>d</span></td>
<td><span class="penalty-amt"><?= fmt_money($r['Est. Penalty']) ?></span></td>
</tr><?php endforeach; endif; ?>
</tbody></table></div>
</section></main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>const APP_URL='<?= e(APP_URL) ?>';const chartData={overdueDist:{labels:<?= json_encode(array_keys($dist)) ?>,values:<?= json_encode(array_map('intval',array_values($dist))) ?>}};</script>
<script src="<?= e(APP_URL) ?>/assets/js/reports.js"></script>
</body></html>
