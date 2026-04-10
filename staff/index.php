<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session_guard.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/txn_helpers.php';

session_guard('staff');
flag_overdue_rentals();

$db  = get_db();
$uid = (int)$_SESSION['user_id'];

// ── Stats scoped to this staff member ───────────────────────────
$stats = $db->prepare(
    "SELECT
        SUM(status = 'pending')                        AS pending,
        SUM(status IN ('active','overdue'))             AS active,
        SUM(status = 'overdue')                        AS overdue,
        SUM(status = 'returned')                       AS awaiting,
        SUM(status = 'completed' AND type = 'rent')    AS completed,
        SUM(status = 'completed' AND type = 'sale')    AS sales
     FROM transactions
     WHERE staff_id = :uid
        OR customer_id IN (
            SELECT id FROM users WHERE role='customer'
        )"
)->execute([':uid' => $uid]);

// Global counts (staff see all pending/active — not just their own)
$global = $db->query(
    "SELECT
        SUM(status = 'pending')  AS pending,
        SUM(status = 'active')   AS active,
        SUM(status = 'overdue')  AS overdue,
        SUM(status = 'returned') AS awaiting
     FROM transactions"
)->fetch();

// Today's activity by this staff member
$today_count = $db->prepare(
    "SELECT COUNT(*) FROM logs
     WHERE user_id = :uid AND DATE(created_at) = CURDATE()"
)->execute([':uid' => $uid]) ? $db->prepare(
    "SELECT COUNT(*) FROM logs
     WHERE user_id = :uid AND DATE(created_at) = CURDATE()"
) : null;

$tc = $db->prepare(
    'SELECT COUNT(*) FROM logs WHERE user_id=:uid AND DATE(created_at)=CURDATE()'
);
$tc->execute([':uid' => $uid]);
$today_actions = (int)$tc->fetchColumn();

// Recent transactions (last 10, all staff can see)
$recent = $db->query(
    "SELECT t.id, t.type, t.status, t.created_at,
            u.name AS customer_name,
            i.name AS item_name
     FROM transactions t
     JOIN users u ON u.id = t.customer_id
     JOIN items i ON i.id = t.item_id
     ORDER BY t.created_at DESC
     LIMIT 10"
)->fetchAll();

// Overdue items (top 5)
$overdue_top = $db->query(
    "SELECT t.id, i.name AS item_name, u.name AS customer_name,
            DATEDIFF(CURDATE(), t.due_date) AS days_late
     FROM transactions t
     JOIN items i ON i.id = t.item_id
     JOIN users u ON u.id = t.customer_id
     WHERE t.status = 'overdue'
     ORDER BY days_late DESC LIMIT 5"
)->fetchAll();

// Unread notifications for this staff
$notifs = $db->prepare(
    'SELECT message, type, created_at FROM notifications
     WHERE user_id=:uid AND is_read=0
     ORDER BY created_at DESC LIMIT 5'
);
$notifs->execute([':uid' => $uid]);
$notifs = $notifs->fetchAll();

$active_nav = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/staff.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/transactions.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/staff_dash.css">
</head>
<body>
<?php require __DIR__ . '/partials/sidebar.php'; ?>
<main class="main-content">
    <header class="topbar">
        <h2>Dashboard</h2>
        <span>Welcome, <?= e($_SESSION['name']) ?></span>
    </header>
    <section class="page-body">

        <!-- Global stats bar -->
        <div class="stats-bar staff-stats">
            <div class="stat-card stat-card--pending">
                <span class="stat-num"><?= (int)$global['pending'] ?></span>
                <span class="stat-label">Pending Requests</span>
            </div>
            <div class="stat-card stat-card--active">
                <span class="stat-num"><?= (int)$global['active'] ?></span>
                <span class="stat-label">Active Rentals</span>
            </div>
            <div class="stat-card stat-card--overdue">
                <span class="stat-num"><?= (int)$global['overdue'] ?></span>
                <span class="stat-label">Overdue</span>
            </div>
            <div class="stat-card stat-card--returned">
                <span class="stat-num"><?= (int)$global['awaiting'] ?></span>
                <span class="stat-label">Awaiting Return Confirm</span>
            </div>
            <div class="stat-card">
                <span class="stat-num"><?= $today_actions ?></span>
                <span class="stat-label">Your Actions Today</span>
            </div>
        </div>

        <div class="staff-grid">

            <!-- Recent transactions -->
            <div class="dash-card dash-card--wide">
                <div class="dash-card__header">
                    <h4>Recent Transactions</h4>
                    <a href="<?= e(APP_URL) ?>/staff/transactions/index.php"
                       class="btn btn--sm btn--ghost">View All</a>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                        <tr>
                            <th>#</th><th>Customer</th><th>Item</th>
                            <th>Type</th><th>Status</th><th>Date</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recent)): ?>
                            <tr><td colspan="6" class="table-empty">No transactions yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent as $r): ?>
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
                                <td><?= fmt_date($r['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Overdue alert panel -->
            <div class="dash-card">
                <div class="dash-card__header">
                    <h4>⚠ Top Overdue</h4>
                    <a href="<?= e(APP_URL) ?>/staff/transactions/index.php?status=overdue"
                       class="btn btn--sm btn--ghost">See All</a>
                </div>
                <?php if (empty($overdue_top)): ?>
                    <p class="dash-empty">No overdue items 🎉</p>
                <?php else: ?>
                    <ul class="overdue-list">
                        <?php foreach ($overdue_top as $o): ?>
                        <li class="overdue-item">
                            <div class="overdue-info">
                                <span class="overdue-name"><?= e($o['item_name']) ?></span>
                                <span class="overdue-customer"><?= e($o['customer_name']) ?></span>
                            </div>
                            <span class="days-pill due-critical">
                                <?= (int)$o['days_late'] ?>d
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Unread notifications panel -->
            <div class="dash-card">
                <div class="dash-card__header">
                    <h4>Notifications</h4>
                    <a href="<?= e(APP_URL) ?>/staff/notifications.php"
                       class="btn btn--sm btn--ghost">View All</a>
                </div>
                <?php if (empty($notifs)): ?>
                    <p class="dash-empty">No unread notifications.</p>
                <?php else: ?>
                    <ul class="notif-list">
                        <?php foreach ($notifs as $n): ?>
                        <li class="notif-item notif-item--<?= e($n['type']) ?>">
                            <span class="notif-msg"><?= e($n['message']) ?></span>
                            <span class="notif-time"><?= fmt_date($n['created_at'], 'M d, H:i') ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

        </div>
    </section>
</main>
</body>
</html>