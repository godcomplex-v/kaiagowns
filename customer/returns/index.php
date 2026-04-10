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

// Items due for return (active or overdue)
$due = $db->prepare(
    "SELECT t.id, t.status, t.borrow_date, t.due_date,
            i.name AS item_name, i.image AS item_image,
            DATEDIFF(t.due_date, CURDATE()) AS days_left,
            rn.id AS notice_id, rn.pickup_status
     FROM transactions t
     JOIN items i ON i.id = t.item_id
     LEFT JOIN return_notices rn ON rn.transaction_id = t.id
     WHERE t.customer_id = :uid
       AND t.type = 'rent'
       AND t.status IN ('active','overdue')
     ORDER BY t.due_date ASC"
);
$due->execute([':uid' => $uid]);
$due_rows = $due->fetchAll();

// Past return notices
$past = $db->prepare(
    "SELECT rn.id, rn.notice_date, rn.pickup_status, rn.confirmed_at,
            i.name AS item_name,
            t.return_date, t.penalty_fee
     FROM return_notices rn
     JOIN transactions t ON t.id = rn.transaction_id
     JOIN items i ON i.id = t.item_id
     WHERE rn.customer_id = :uid
     ORDER BY rn.notice_date DESC
     LIMIT 20"
);
$past->execute([':uid' => $uid]);
$past_rows = $past->fetchAll();

$active_nav = 'returns';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Returns — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/customer.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/transactions.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/customer_dash.css">
</head>
<body>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>
<main class="main-content">
    <header class="topbar"><h2>Returns</h2></header>
    <section class="page-body">

        <h3 class="section-heading">Items Due for Return</h3>

        <?php if (empty($due_rows)): ?>
            <div class="empty-state">No items currently rented.</div>
        <?php else: ?>
        <div class="return-list">
            <?php foreach ($due_rows as $r): ?>
            <?php
            $days    = (int)$r['days_left'];
            $overdue = $r['status'] === 'overdue';
            $has_notice = !empty($r['notice_id']);
            $penalty = $overdue ? calc_penalty($r['due_date']) : 0;
            ?>
            <div class="return-item <?= $overdue?'return-item--overdue':'' ?>">
                <img src="<?= $r['item_image']
                    ? e(UPLOAD_URL.'items/'.$r['item_image'])
                    : e(APP_URL.'/assets/images/no-image.svg') ?>"
                     class="inv-thumb" style="width:60px;height:60px;flex-shrink:0" alt="">
                <div class="return-info">
                    <h4><?= e($r['item_name']) ?></h4>
                    <p>Due: <?= fmt_date($r['due_date']) ?>
                        <?php if ($overdue): ?>
                            <span class="days-pill due-critical"><?= abs($days) ?>d overdue</span>
                            — Est. penalty: <strong class="penalty-amt"><?= fmt_money($penalty) ?></strong>
                        <?php else: ?>
                            <span class="days-pill <?= $days<=2?'due-warning':'' ?>">
                                <?= $days ?>d left
                            </span>
                        <?php endif; ?>
                    </p>
                    <?php if ($has_notice): ?>
                        <span class="badge badge--<?= e($r['pickup_status']) === 'confirmed' ? 'completed' : 'pending' ?>">
                            Notice: <?= e(ucfirst(str_replace('_',' ',$r['pickup_status']))) ?>
                        </span>
                    <?php else: ?>
                        <button class="btn btn--sm btn--primary submit-notice-btn"
                                style="margin-top:.4rem"
                                data-id="<?= (int)$r['id'] ?>"
                                data-item="<?= e($r['item_name']) ?>">
                            Notify Staff — Ready to Return
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <h3 class="section-heading" style="margin-top:2rem">Return History</h3>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                <tr>
                    <th>Item</th><th>Notice Date</th><th>Status</th>
                    <th>Confirmed</th><th>Return Date</th><th>Penalty</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($past_rows)): ?>
                    <tr><td colspan="6" class="table-empty">No return history.</td></tr>
                <?php else: ?>
                    <?php foreach ($past_rows as $r): ?>
                    <tr>
                        <td><?= e($r['item_name']) ?></td>
                        <td><?= fmt_date($r['notice_date']) ?></td>
                        <td>
                            <span class="badge badge--<?= e($r['pickup_status'])==='confirmed'?'completed':'pending' ?>">
                                <?= e(ucfirst(str_replace('_',' ',$r['pickup_status']))) ?>
                            </span>
                        </td>
                        <td><?= $r['confirmed_at'] ? fmt_date($r['confirmed_at']) : '—' ?></td>
                        <td><?= $r['return_date'] ? fmt_date($r['return_date']) : '—' ?></td>
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

<!-- Submit return notice modal -->
<div id="noticeModal" class="modal" role="dialog" aria-modal="true" hidden>
    <div class="modal__backdrop"></div>
    <div class="modal__box modal__box--sm">
        <div class="modal__header">
            <h3 class="modal__title">Return Notice</h3>
            <button class="modal__close" id="closeNoticeModal" aria-label="Close">&times;</button>
        </div>
        <div class="modal__body">
            <p>Notify staff that <strong id="noticeItemName"></strong>
               is ready for pickup/drop-off?</p>
        </div>
        <div class="modal__footer">
            <button class="btn btn--ghost" id="cancelNotice">Cancel</button>
            <button class="btn btn--primary" id="confirmNotice">Send Notice</button>
        </div>
    </div>
</div>

<div id="toast" class="toast" role="alert" aria-live="polite"></div>
<script>const APP_URL = '<?= e(APP_URL) ?>';</script>
<script src="<?= e(APP_URL) ?>/assets/js/customer_returns.js"></script>
</body>
</html>