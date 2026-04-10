<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_guard.php';
require_once __DIR__ . '/../../includes/functions.php';

session_guard('staff');

$db    = get_db();
$range = date_range();
$scope = get_param('scope') === 'mine' ? 'mine' : 'all';
$uid   = (int)$_SESSION['user_id'];

$scope_where  = $scope === 'mine' ? 'AND t.staff_id = :uid' : '';
$scope_params = $scope === 'mine' ? [':uid' => $uid] : [];

// Summary for period
$summary = $db->prepare(
    "SELECT
        COUNT(*)                              AS total,
        SUM(t.type='rent')                    AS rentals,
        SUM(t.type='sale')                    AS sales,
        SUM(t.status='overdue')               AS overdue,
        SUM(t.status='completed')             AS completed,
        SUM(t.amount_paid)                    AS revenue,
        SUM(t.penalty_fee)                    AS penalties
     FROM transactions t
     WHERE DATE(t.created_at) BETWEEN :from AND :to
     {$scope_where}"
);
$summary->execute(array_merge(
    [':from' => $range['from'], ':to' => $range['to']],
    $scope_params
));
$summary = $summary->fetch();

// Daily transaction counts for chart
$daily = $db->prepare(
    "SELECT DATE(t.created_at) AS label, COUNT(*) AS value
     FROM transactions t
     WHERE DATE(t.created_at) BETWEEN :from AND :to
     {$scope_where}
     GROUP BY DATE(t.created_at) ORDER BY label"
);
$daily->execute(array_merge(
    [':from' => $range['from'], ':to' => $range['to']],
    $scope_params
));
$daily = $daily->fetchAll();

// Detailed rows
$rows = $db->prepare(
    "SELECT t.id, t.type, t.status, t.borrow_date, t.due_date,
            t.return_date, t.amount_paid, t.penalty_fee, t.created_at,
            u.name AS customer_name, i.name AS item_name,
            s.name AS staff_name
     FROM transactions t
     JOIN users u ON u.id=t.customer_id
     JOIN items i ON i.id=t.item_id
     LEFT JOIN users s ON s.id=t.staff_id
     WHERE DATE(t.created_at) BETWEEN :from AND :to
     {$scope_where}
     ORDER BY t.created_at DESC
     LIMIT 100"
);
$rows->execute(array_merge(
    [':from' => $range['from'], ':to' => $range['to']],
    $scope_params
));
$rows = $rows->fetchAll();

$active_nav = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/staff.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/reports.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/transactions.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/staff_dash.css">
</head>
<body>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>
<main class="main-content">
    <header class="topbar">
        <h2>Reports</h2>
        <span>Welcome, <?= e($_SESSION['name']) ?></span>
    </header>
    <section class="page-body">

        <div class="report-header">
            <h3 class="report-title">Transaction Report</h3>
            <!-- Scope toggle -->
            <div style="display:flex;gap:.5rem;align-items:center">
                <span style="font-size:.85rem;color:var(--text-muted)">Showing:</span>
                <a href="?scope=all&from=<?= e($range['from']) ?>&to=<?= e($range['to']) ?>"
                   class="btn btn--sm <?= $scope==='all' ?'btn--primary':'btn--ghost' ?>">
                    All Staff
                </a>
                <a href="?scope=mine&from=<?= e($range['from']) ?>&to=<?= e($range['to']) ?>"
                   class="btn btn--sm <?= $scope==='mine'?'btn--primary':'btn--ghost' ?>">
                    My Activity
                </a>
            </div>
        </div>

        <?php require __DIR__ . '/../../../admin/reports/partials/date_filter.php'; ?>

        <div class="summary-grid">
            <div class="summary-card">
                <span class="summary-num"><?= (int)$summary['total'] ?></span>
                <span class="summary-label">Total Transactions</span>
            </div>
            <div class="summary-card">
                <span class="summary-num"><?= (int)$summary['rentals'] ?></span>
                <span class="summary-label">Rentals</span>
            </div>
            <div class="summary-card">
                <span class="summary-num"><?= (int)$summary['sales'] ?></span>
                <span class="summary-label">Sales</span>
            </div>
            <div class="summary-card summary-card--danger">
                <span class="summary-num"><?= (int)$summary['overdue'] ?></span>
                <span class="summary-label">Overdue</span>
            </div>
            <div class="summary-card summary-card--money">
                <span class="summary-num"><?= fmt_money($summary['revenue'] ?? 0) ?></span>
                <span class="summary-label">Total Revenue</span>
            </div>
            <div class="summary-card summary-card--warn">
                <span class="summary-num"><?= fmt_money($summary['penalties'] ?? 0) ?></span>
                <span class="summary-label">Penalties</span>
            </div>
        </div>

        <div class="chart-row">
            <div class="chart-card chart-card--full">
                <h4 class="chart-title">Daily Transactions</h4>
                <div class="chart-wrap chart-wrap--wide">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table report-table">
                <thead>
                <tr>
                    <th>#</th><th>Customer</th><th>Item</th><th>Type</th>
                    <th>Status</th><th>Staff</th><th>Date</th>
                    <th>Amount</th><th>Penalty</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="9" class="table-empty">No transactions in this period.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= (int)$r['id'] ?></td>
                        <td><?= e($r['customer_name']) ?></td>
                        <td><?= e($r['item_name']) ?></td>
                        <td>
                            <span class="badge badge--type-<?= e($r['type']) ?>">
                                <?= e(ucfirst($r['type'])) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge--<?= e($r['status']) ?>">
                                <?= e(ucfirst($r['status'])) ?>
                            </span>
                        </td>
                        <td><?= e($r['staff_name'] ?? '—') ?></td>
                        <td><?= fmt_date($r['created_at']) ?></td>
                        <td><?= fmt_money($r['amount_paid']) ?></td>
                        <td><?= (float)$r['penalty_fee']>0
                            ? '<span class="penalty-amt">'.fmt_money($r['penalty_fee']).'</span>'
                            : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

    </section>
</main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const APP_URL   = '<?= e(APP_URL) ?>';
const chartData = {
    byDay: {
        labels: <?= json_encode(array_column($daily, 'label')) ?>,
        values: <?= json_encode(array_map('intval', array_column($daily, 'value'))) ?>
    }
};
</script>
<script src="<?= e(APP_URL) ?>/assets/js/reports.js"></script>
</body>
</html>