<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_guard.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/txn_helpers.php';

session_guard('customer');
flag_overdue_rentals();

$db  = get_db();
$uid = (int)$_SESSION['user_id'];

// Active rentals
$active = $db->prepare(
    "SELECT t.id, t.status, t.borrow_date, t.due_date,
            t.amount_paid, t.penalty_fee,
            i.name AS item_name, i.image AS item_image,
            DATEDIFF(t.due_date, CURDATE()) AS days_left,
            DATEDIFF(CURDATE(), t.due_date) AS days_late
     FROM transactions t
     JOIN items i ON i.id = t.item_id
     WHERE t.customer_id = :uid
       AND t.type = 'rent'
       AND t.status IN ('active','overdue')
     ORDER BY t.due_date ASC"
);
$active->execute([':uid' => $uid]);
$active_rows = $active->fetchAll();

// Rental history
$history = $db->prepare(
    "SELECT t.id, t.status, t.borrow_date, t.due_date, t.return_date,
            t.amount_paid, t.penalty_fee, t.created_at,
            i.name AS item_name, i.image AS item_image
     FROM transactions t
     JOIN items i ON i.id = t.item_id
     WHERE t.customer_id = :uid
       AND t.type = 'rent'
       AND t.status IN ('returned','completed')
     ORDER BY t.return_date DESC"
);
$history->execute([':uid' => $uid]);
$history_rows = $history->fetchAll();

$active_nav = 'rentals';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Rentals — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/customer.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/transactions.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/customer_dash.css">
</head>
<body>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>
<main class="main-content">
    <header class="topbar"><h2>My Rentals</h2></header>
    <section class="page-body">

        <!-- Active rentals -->
        <h3 class="section-heading">Currently Renting</h3>
        <?php if (empty($active_rows)): ?>
            <div class="empty-state">
                <p>No active rentals. <a href="<?= e(APP_URL) ?>/customer/catalog/index.php">Browse the catalog →</a></p>
            </div>
        <?php else: ?>
        <div class="rental-cards">
            <?php foreach ($active_rows as $r): ?>
            <?php
            $days   = (int)$r['days_left'];
            $late   = (int)$r['days_late'];
            $overdue = $r['status'] === 'overdue';
            $penalty = $overdue ? calc_penalty($r['due_date']) : 0;
            ?>
            <div class="rental-card <?= $overdue?'rental-card--overdue':($days<=2?'rental-card--due':'') ?>">
                <img src="<?= $r['item_image']
                    ? e(UPLOAD_URL.'items/'.$r['item_image'])
                    : e(APP_URL.'/assets/images/no-image.svg') ?>"
                     class="rental-card__img" alt="">
                <div class="rental-card__body">
                    <h4><?= e($r['item_name']) ?></h4>
                    <div class="rental-meta">
                        <span>Borrowed: <?= fmt_date($r['borrow_date']) ?></span>
                        <span>Due: <?= fmt_date($r['due_date']) ?></span>
                    </div>
                    <?php if ($overdue): ?>
                        <div class="overdue-alert">
                            ⚠ <?= $late ?> days overdue —
                            Est. penalty: <strong><?= fmt_money($penalty) ?></strong>
                        </div>
                    <?php else: ?>
                        <div class="days-remaining">
                            <span class="days-pill <?= $days<=2?'due-warning':'' ?>">
                                <?= $days ?> day<?= $days!==1?'s':'' ?> remaining
                            </span>
                        </div>
                    <?php endif; ?>
                    <a href="<?= e(APP_URL) ?>/customer/returns/index.php"
                       class="btn btn--sm btn--primary" style="margin-top:.5rem">
                        Submit Return Notice
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Rental history -->
        <h3 class="section-heading" style="margin-top:2rem">Rental History</h3>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                <tr>
                    <th>Item</th><th>Borrow Date</th><th>Due Date</th>
                    <th>Return Date</th><th>Status</th>
                    <th>Amount Paid</th><th>Penalty</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($history_rows)): ?>
                    <tr><td colspan="7" class="table-empty">No rental history.</td></tr>
                <?php else: ?>
                    <?php foreach ($history_rows as $r): ?>
                    <tr>
                        <td><?= e($r['item_name']) ?></td>
                        <td><?= fmt_date($r['borrow_date']) ?></td>
                        <td><?= fmt_date($r['due_date']) ?></td>
                        <td><?= fmt_date($r['return_date']) ?></td>
                        <td>
                            <span class="badge badge--<?= e($r['status']) ?>">
                                <?= e(ucfirst($r['status'])) ?>
                            </span>
                        </td>
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
</body>
</html>