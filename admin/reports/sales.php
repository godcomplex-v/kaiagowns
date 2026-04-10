<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
session_guard('admin');
$db=get_db(); $range=date_range();
$rows=$db->prepare("SELECT t.id AS '#',u.name AS 'Customer',i.name AS 'Item',c.name AS 'Category',t.amount_paid AS 'Amount Paid',t.created_at AS 'Sale Date' FROM transactions t JOIN users u ON u.id=t.customer_id JOIN items i ON i.id=t.item_id LEFT JOIN categories c ON c.id=i.category_id WHERE t.type='sale' AND t.status='completed' AND DATE(t.created_at) BETWEEN :from AND :to ORDER BY t.created_at DESC");
$rows->execute([':from'=>$range['from'],':to'=>$range['to']]); $rows=$rows->fetchAll();
$summary=$db->prepare("SELECT COUNT(*) AS total,SUM(amount_paid) AS revenue FROM transactions WHERE type='sale' AND status='completed' AND DATE(created_at) BETWEEN :from AND :to");
$summary->execute([':from'=>$range['from'],':to'=>$range['to']]); $summary=$summary->fetch();
$by_cat=$db->prepare("SELECT c.name AS label,COUNT(*) AS value,SUM(t.amount_paid) AS revenue FROM transactions t JOIN items i ON i.id=t.item_id LEFT JOIN categories c ON c.id=i.category_id WHERE t.type='sale' AND t.status='completed' AND DATE(t.created_at) BETWEEN :from AND :to GROUP BY c.id ORDER BY revenue DESC");
$by_cat->execute([':from'=>$range['from'],':to'=>$range['to']]); $by_cat=$by_cat->fetchAll();
$by_day=$db->prepare("SELECT DATE(created_at) AS label,SUM(amount_paid) AS value FROM transactions WHERE type='sale' AND status='completed' AND DATE(created_at) BETWEEN :from AND :to GROUP BY DATE(created_at) ORDER BY label");
$by_day->execute([':from'=>$range['from'],':to'=>$range['to']]); $by_day=$by_day->fetchAll();
$active_nav='reports';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Sales Report — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/reports.css">
</head><body><?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content"><header class="topbar"><h2>Reports</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body">
<?php require __DIR__.'/partials/report_tabs.php'; ?>
<div class="report-header"><h3 class="report-title">Sales Report</h3>
<button class="btn btn--ghost btn--sm export-btn" data-type="sales" data-params="<?= e(http_build_query($range)) ?>">⬇ Export CSV</button></div>
<?php require __DIR__.'/partials/date_filter.php'; ?>
<div class="summary-grid">
    <div class="summary-card"><span class="summary-num"><?= (int)$summary['total'] ?></span><span class="summary-label">Total Sales</span></div>
    <div class="summary-card summary-card--money"><span class="summary-num"><?= fmt_money($summary['revenue']??0) ?></span><span class="summary-label">Total Revenue</span></div>
    <div class="summary-card"><span class="summary-num"><?= $summary['total']>0?fmt_money($summary['revenue']/$summary['total']):'—' ?></span><span class="summary-label">Avg Sale Value</span></div>
</div>
<div class="chart-row">
    <div class="chart-card"><h4 class="chart-title">Revenue by Category</h4><div class="chart-wrap chart-wrap--sm"><canvas id="salesByCatChart"></canvas></div></div>
    <div class="chart-card"><h4 class="chart-title">Daily Revenue</h4><div class="chart-wrap"><canvas id="salesPerDayChart"></canvas></div></div>
</div>
<div class="table-wrap"><table class="data-table report-table">
<thead><tr><th>#</th><th>Customer</th><th>Item</th><th>Category</th><th>Sale Date</th><th>Amount Paid</th></tr></thead>
<tbody><?php if(empty($rows)): ?><tr><td colspan="6" class="table-empty">No sales in this period.</td></tr>
<?php else: foreach($rows as $r): ?>
<tr><td><?= (int)$r['#'] ?></td><td><?= e($r['Customer']) ?></td><td><?= e($r['Item']) ?></td><td><?= e($r['Category']??'—') ?></td><td><?= fmt_date($r['Sale Date']) ?></td><td><?= fmt_money($r['Amount Paid']) ?></td></tr>
<?php endforeach; endif; ?>
</tbody>
<?php if(!empty($rows)): ?><tfoot><tr class="table-foot"><td colspan="5"><strong>Total</strong></td><td><strong><?= fmt_money($summary['revenue']??0) ?></strong></td></tr></tfoot><?php endif; ?>
</table></div>
</section></main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>const APP_URL='<?= e(APP_URL) ?>';const chartData={byCat:{labels:<?= json_encode(array_column($by_cat,'label')) ?>,values:<?= json_encode(array_map('floatval',array_column($by_cat,'revenue'))) ?>},byDay:{labels:<?= json_encode(array_column($by_day,'label')) ?>,values:<?= json_encode(array_map('floatval',array_column($by_day,'value'))) ?>}};</script>
<script src="<?= e(APP_URL) ?>/assets/js/reports.js"></script>
</body></html>
