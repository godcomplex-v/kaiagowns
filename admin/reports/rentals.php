<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
session_guard('admin');
$db=get_db(); $range=date_range();
$rows=$db->prepare("SELECT t.id AS '#',u.name AS 'Customer',i.name AS 'Item',t.borrow_date AS 'Borrow Date',t.due_date AS 'Due Date',t.return_date AS 'Return Date',t.status AS 'Status',t.amount_paid AS 'Amount Paid',t.penalty_fee AS 'Penalty Fee' FROM transactions t JOIN users u ON u.id=t.customer_id JOIN items i ON i.id=t.item_id WHERE t.type='rent' AND DATE(t.created_at) BETWEEN :from AND :to ORDER BY t.created_at DESC");
$rows->execute([':from'=>$range['from'],':to'=>$range['to']]); $rows=$rows->fetchAll();
$summary=$db->prepare("SELECT COUNT(*) AS total,SUM(status IN ('active','overdue')) AS active,SUM(status='completed') AS completed,SUM(status='overdue') AS overdue,SUM(amount_paid) AS revenue,SUM(penalty_fee) AS penalties FROM transactions WHERE type='rent' AND DATE(created_at) BETWEEN :from AND :to");
$summary->execute([':from'=>$range['from'],':to'=>$range['to']]); $summary=$summary->fetch();
$by_day=$db->prepare("SELECT DATE(created_at) AS label,COUNT(*) AS value FROM transactions WHERE type='rent' AND DATE(created_at) BETWEEN :from AND :to GROUP BY DATE(created_at) ORDER BY label");
$by_day->execute([':from'=>$range['from'],':to'=>$range['to']]); $by_day=$by_day->fetchAll();
$active_nav='reports';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Rental Report — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/transactions.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/reports.css">
</head><body><?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content"><header class="topbar"><h2>Reports</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body">
<?php require __DIR__.'/partials/report_tabs.php'; ?>
<div class="report-header"><h3 class="report-title">Rental Report</h3>
<button class="btn btn--ghost btn--sm export-btn" data-type="rentals" data-params="<?= e(http_build_query($range)) ?>">⬇ Export CSV</button></div>
<?php require __DIR__.'/partials/date_filter.php'; ?>
<div class="summary-grid">
    <div class="summary-card"><span class="summary-num"><?= (int)$summary['total'] ?></span><span class="summary-label">Total Rentals</span></div>
    <div class="summary-card summary-card--good"><span class="summary-num"><?= (int)$summary['active'] ?></span><span class="summary-label">Active / Overdue</span></div>
    <div class="summary-card"><span class="summary-num"><?= (int)$summary['completed'] ?></span><span class="summary-label">Completed</span></div>
    <div class="summary-card summary-card--danger"><span class="summary-num"><?= (int)$summary['overdue'] ?></span><span class="summary-label">Overdue</span></div>
    <div class="summary-card summary-card--money"><span class="summary-num"><?= fmt_money($summary['revenue']??0) ?></span><span class="summary-label">Revenue</span></div>
    <div class="summary-card summary-card--warn"><span class="summary-num"><?= fmt_money($summary['penalties']??0) ?></span><span class="summary-label">Penalties</span></div>
</div>
<div class="chart-row"><div class="chart-card chart-card--full"><h4 class="chart-title">Rentals per Day</h4><div class="chart-wrap chart-wrap--wide"><canvas id="rentalsPerDayChart"></canvas></div></div></div>
<div class="table-wrap"><table class="data-table report-table">
<thead><tr><th>#</th><th>Customer</th><th>Item</th><th>Borrow Date</th><th>Due Date</th><th>Return Date</th><th>Status</th><th>Amount Paid</th><th>Penalty</th></tr></thead>
<tbody><?php if(empty($rows)): ?><tr><td colspan="9" class="table-empty">No rentals in this period.</td></tr>
<?php else: foreach($rows as $r): ?>
<tr><td><?= (int)$r['#'] ?></td><td><?= e($r['Customer']) ?></td><td><?= e($r['Item']) ?></td>
<td><?= fmt_date($r['Borrow Date']) ?></td><td><?= fmt_date($r['Due Date']) ?></td><td><?= fmt_date($r['Return Date']) ?></td>
<td><span class="badge badge--<?= e(strtolower($r['Status'])) ?>"><?= e(ucfirst($r['Status'])) ?></span></td>
<td><?= fmt_money($r['Amount Paid']) ?></td>
<td><?= (float)$r['Penalty Fee']>0?'<span class="penalty-amt">'.fmt_money($r['Penalty Fee']).'</span>':'—' ?></td>
</tr><?php endforeach; endif; ?>
</tbody>
<?php if(!empty($rows)): ?><tfoot><tr class="table-foot"><td colspan="7"><strong>Totals</strong></td><td><strong><?= fmt_money($summary['revenue']??0) ?></strong></td><td><strong><?= fmt_money($summary['penalties']??0) ?></strong></td></tr></tfoot><?php endif; ?>
</table></div>
</section></main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>const APP_URL='<?= e(APP_URL) ?>';const chartData={byDay:{labels:<?= json_encode(array_column($by_day,'label')) ?>,values:<?= json_encode(array_map('intval',array_column($by_day,'value'))) ?>}};</script>
<script src="<?= e(APP_URL) ?>/assets/js/reports.js"></script>
</body></html>
