<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session_guard.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/txn_helpers.php';

session_guard('customer');
flag_overdue_rentals();

$db  = get_db();
$uid = (int)$_SESSION['user_id'];

// Quick stats
$stats = $db->prepare(
    "SELECT
        SUM(status IN ('active','overdue'))  AS active_rentals,
        SUM(status = 'pending')              AS pending_requests,
        SUM(status = 'returned')             AS to_return,
        SUM(status = 'completed' AND type='sale') AS total_purchases
     FROM transactions
     WHERE customer_id = :uid"
);
$stats->execute([':uid' => $uid]);
$stats = $stats->fetch();

// Upcoming due dates (next 7 days + overdue)
$upcoming = $db->prepare(
    "SELECT t.id, i.name AS item_name, t.due_date, t.status,
            DATEDIFF(t.due_date, CURDATE()) AS days_left
     FROM transactions t
     JOIN items i ON i.id = t.item_id
     WHERE t.customer_id = :uid
       AND t.status IN ('active','overdue')
     ORDER BY t.due_date ASC
     LIMIT 5"
);
$upcoming->execute([':uid' => $uid]);
$upcoming = $upcoming->fetchAll();

// Recent activity (last 8 transactions)
$activity = $db->prepare(
    "SELECT t.id, t.type, t.status, t.created_at,
            i.name AS item_name
     FROM transactions t
     JOIN items i ON i.id = t.item_id
     WHERE t.customer_id = :uid
     ORDER BY t.created_at DESC
     LIMIT 8"
);
$activity->execute([':uid' => $uid]);
$activity = $activity->fetchAll();

// Unread notifications (top 3)
$notifs = $db->prepare(
    'SELECT message, type, created_at FROM notifications
     WHERE user_id=:uid AND is_read=0
     ORDER BY created_at DESC LIMIT 3'
);
$notifs->execute([':uid' => $uid]);
$notifs = $notifs->fetchAll();

// Customer info
$customer = $db->prepare(
    'SELECT name, profile_photo, status FROM users WHERE id=:uid LIMIT 1'
);
$customer->execute([':uid' => $uid]);
$customer = $customer->fetch();

$active_nav = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/customer.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/transactions.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/customer_dash.css">
</head>
<body>
<?php require __DIR__ . '/partials/sidebar.php'; ?>
<main class="main-content">
    <header class="topbar">
        <div class="cust-welcome">
            <?php if ($customer['profile_photo']): ?>
                <img src="<?= e(UPLOAD_URL . 'avatars/' . $customer['profile_photo']) ?>"
                     alt="" class="topbar-avatar">
            <?php endif; ?>
            <div>
                <h2>Welcome back, <?= e($customer['name']) ?>!</h2>
                <?php if ($customer['status'] === 'suspended'): ?>
                    <span class="badge badge--suspended">Account Suspended</span>
                <?php endif; ?>
            </div>
        </div>
    </header>
    <section class="page-body">

        <!-- Quick stats -->
        <div class="cust-stats">
            <a href="<?= e(APP_URL) ?>/customer/rentals/index.php"
               class="cstat-card cstat--blue">
                <span class="cstat-num"><?= (int)$stats['active_rentals'] ?></span>
                <span class="cstat-label">Active Rentals</span>
            </a>
            <a href="<?= e(APP_URL) ?>/customer/requests/index.php"
               class="cstat-card cstat--amber">
                <span class="cstat-num"><?= (int)$stats['pending_requests'] ?></span>
                <span class="cstat-label">Pending Requests</span>
            </a>
            <a href="<?= e(APP_URL) ?>/customer/returns/index.php"
               class="cstat-card cstat--rose">
                <span class="cstat-num"><?= (int)$stats['to_return'] ?></span>
                <span class="cstat-label">Items to Return</span>
            </a>
            <a href="<?= e(APP_URL) ?>/customer/purchases/index.php"
               class="cstat-card cstat--teal">
                <span class="cstat-num"><?= (int)$stats['total_purchases'] ?></span>
                <span class="cstat-label">Total Purchases</span>
            </a>
        </div>

        <div class="cust-home-grid">

            <!-- Upcoming due dates -->
            <div class="dash-card">
                <div class="dash-card__header">
                    <h4>📅 Upcoming Due Dates</h4>
                    <a href="<?= e(APP_URL) ?>/customer/rentals/index.php"
                       class="btn btn--sm btn--ghost">View Rentals</a>
                </div>
                <?php if (empty($upcoming)): ?>
                    <p class="dash-empty">No active rentals. <a href="<?= e(APP_URL) ?>/customer/catalog/index.php">Browse the catalog →</a></p>
                <?php else: ?>
                    <ul class="due-list">
                        <?php foreach ($upcoming as $u): ?>
                        <?php
                        $days = (int)$u['days_left'];
                        $cls  = $u['status']==='overdue' ? 'due-critical'
                              : ($days<=2 ? 'due-warning' : '');
                        ?>
                        <li class="due-item <?= $cls ?>">
                            <div class="due-info">
                                <span class="due-name"><?= e($u['item_name']) ?></span>
                                <span class="due-date"><?= fmt_date($u['due_date']) ?></span>
                            </div>
                            <?php if ($u['status']==='overdue'): ?>
                                <span class="days-pill due-critical">
                                    <?= abs($days) ?>d overdue
                                </span>
                            <?php else: ?>
                                <span class="days-pill <?= $cls ?>">
                                    <?= $days ?>d left
                                </span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Recent activity feed -->
            <div class="dash-card">
                <div class="dash-card__header">
                    <h4>🕐 Recent Activity</h4>
                </div>
                <?php if (empty($activity)): ?>
                    <p class="dash-empty">No activity yet.</p>
                <?php else: ?>
                    <ul class="activity-feed">
                        <?php foreach ($activity as $a): ?>
                        <li class="feed-item">
                            <span class="feed-dot feed-dot--<?= e($a['status']) ?>"></span>
                            <div class="feed-body">
                                <span class="feed-text">
                                    <strong><?= e($a['item_name']) ?></strong>
                                    —
                                    <span class="badge badge--<?= e($a['status']) ?>"
                                          style="font-size:.72rem">
                                        <?= e(ucfirst($a['status'])) ?>
                                    </span>
                                    <span class="badge badge--type-<?= e($a['type']) ?>"
                                          style="font-size:.72rem">
                                        <?= e(ucfirst($a['type'])) ?>
                                    </span>
                                </span>
                                <span class="feed-time"><?= fmt_date($a['created_at'], 'M d, Y') ?></span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Notifications preview -->
            <?php if (!empty($notifs)): ?>
            <div class="dash-card dash-card--full">
                <div class="dash-card__header">
                    <h4>🔔 New Notifications</h4>
                    <a href="<?= e(APP_URL) ?>/customer/notifications.php"
                       class="btn btn--sm btn--ghost">View All</a>
                </div>
                <ul class="notif-list">
                    <?php foreach ($notifs as $n): ?>
                    <li class="notif-item notif-item--<?= e($n['type']) ?>">
                        <span class="notif-msg"><?= e($n['message']) ?></span>
                        <span class="notif-time"><?= fmt_date($n['created_at'], 'M d, H:i') ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

        </div>
    </section>
</main>
</body>
</html>