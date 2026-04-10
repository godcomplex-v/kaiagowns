<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_guard.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/pagination.php';

session_guard('customer');

$db  = get_db();
$uid = (int)$_SESSION['user_id'];

$status_f = get_param('status');
$where    = ['t.customer_id = :uid'];
$params   = [':uid' => $uid];

if (in_array($status_f,
    ['pending','approved','rejected','cancelled'], true)) {
    $where[]          = 't.status = :status';
    $params[':status'] = $status_f;
} else {
    // Only show request-phase statuses here
    $where[] = "t.status IN ('pending','approved','rejected','cancelled')";
}

$where_sql = implode(' AND ', $where);

$cnt = $db->prepare(
    "SELECT COUNT(*) FROM transactions t WHERE {$where_sql}"
);
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pag   = paginate($total, 15);

$stmt = $db->prepare(
    "SELECT t.id, t.type, t.status, t.borrow_date, t.due_date,
            t.amount_paid, t.notes, t.created_at,
            i.name AS item_name, i.image AS item_image
     FROM transactions t
     JOIN items i ON i.id = t.item_id
     WHERE {$where_sql}
     ORDER BY t.created_at DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $pag['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pag['offset'],   PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$qs = http_build_query(array_filter(['status' => $status_f]));
$url_pattern = APP_URL . '/customer/requests/index.php?' . ($qs?$qs.'&':'') . 'page=%d';
$active_nav  = 'requests';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Requests — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/customer.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/customer_dash.css">
</head>
<body>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>
<main class="main-content">
    <header class="topbar"><h2>My Requests</h2></header>
    <section class="page-body">

        <div class="table-toolbar">
            <form method="GET" class="toolbar-filters">
                <select name="status" class="toolbar-select">
                    <option value="">All</option>
                    <?php foreach (['pending','approved','rejected','cancelled'] as $s): ?>
                        <option value="<?= $s ?>" <?= $status_f===$s?'selected':'' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn--primary">Filter</button>
            </form>
            <span class="table-count"><?= $total ?> request<?= $total!==1?'s':'' ?></span>
        </div>

        <div class="table-wrap">
            <table class="data-table" id="requestsTable">
                <thead>
                <tr>
                    <th>Item</th><th>Type</th><th>Status</th>
                    <th>Dates</th><th>Amount</th><th>Submitted</th><th>Notes</th><th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="table-empty">
                        No requests yet. <a href="<?= e(APP_URL) ?>/customer/catalog/index.php">Browse the catalog →</a>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                    <tr id="req-row-<?= (int)$r['id'] ?>">
                        <td>
                            <div style="display:flex;align-items:center;gap:.5rem">
                                <img src="<?= $r['item_image']
                                    ? e(UPLOAD_URL.'items/'.$r['item_image'])
                                    : e(APP_URL.'/assets/images/no-image.svg') ?>"
                                     class="inv-thumb" style="width:36px;height:36px" alt="">
                                <?= e($r['item_name']) ?>
                            </div>
                        </td>
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
                        <td>
                            <?php if ($r['borrow_date']): ?>
                                <?= fmt_date($r['borrow_date']) ?> →
                                <?= fmt_date($r['due_date']) ?>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td><?= fmt_money($r['amount_paid']) ?></td>
                        <td><?= fmt_date($r['created_at']) ?></td>
                        <td>
                            <?php if ($r['status']==='rejected' && $r['notes']): ?>
                                <span class="reject-reason" title="<?= e($r['notes']) ?>">
                                    <?= e(mb_substr($r['notes'],0,40)) ?>…
                                </span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['status']==='pending'): ?>
                                <button class="btn btn--sm btn--danger cancel-btn"
                                        data-id="<?= (int)$r['id'] ?>">
                                    Cancel
                                </button>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?= pagination_html($pag, $url_pattern) ?>

    </section>
</main>

<!-- Cancel confirm modal -->
<div id="cancelModal" class="modal" role="dialog" aria-modal="true" hidden>
    <div class="modal__backdrop"></div>
    <div class="modal__box modal__box--sm">
        <div class="modal__header">
            <h3 class="modal__title">Cancel Request</h3>
            <button class="modal__close" id="closeCancelModal" aria-label="Close">&times;</button>
        </div>
        <div class="modal__body">
            <p>Are you sure you want to cancel this request?</p>
        </div>
        <div class="modal__footer">
            <button class="btn btn--ghost" id="cancelCancelBtn">Keep It</button>
            <button class="btn btn--danger" id="confirmCancelBtn">Yes, Cancel</button>
        </div>
    </div>
</div>

<div id="toast" class="toast" role="alert" aria-live="polite"></div>
<script>const APP_URL = '<?= e(APP_URL) ?>';</script>
<script src="<?= e(APP_URL) ?>/assets/js/customer_requests.js"></script>
</body>
</html>