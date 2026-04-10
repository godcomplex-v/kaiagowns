<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_guard.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/pagination.php';
require_once __DIR__ . '/../../includes/txn_helpers.php';

session_guard('staff');
flag_overdue_rentals();

$db          = get_db();
$search      = get_param('search');
$status_f    = get_param('status');
$type_f      = get_param('type');

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]      = '(u.name LIKE :s OR i.name LIKE :s)';
    $params[':s'] = '%' . $search . '%';
}
if (in_array($status_f, ['pending','active','overdue','returned','completed','cancelled','rejected'], true)) {
    $where[]          = 't.status = :status';
    $params[':status'] = $status_f;
}
if (in_array($type_f, ['rent','sale'], true)) {
    $where[]        = 't.type = :type';
    $params[':type'] = $type_f;
}

$where_sql = implode(' AND ', $where);

$cnt = $db->prepare(
    "SELECT COUNT(*) FROM transactions t
     JOIN users u ON u.id=t.customer_id
     JOIN items i ON i.id=t.item_id
     WHERE {$where_sql}"
);
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pag   = paginate($total, 20);

$stmt = $db->prepare(
    "SELECT t.id, t.type, t.status, t.borrow_date, t.due_date,
            t.return_date, t.penalty_fee, t.amount_paid, t.created_at,
            u.name AS customer_name, u.phone AS customer_phone,
            i.id AS item_id, i.name AS item_name,
            DATEDIFF(CURDATE(), t.due_date) AS days_late,
            s.name AS staff_name
     FROM transactions t
     JOIN users u ON u.id = t.customer_id
     JOIN items i ON i.id = t.item_id
     LEFT JOIN users s ON s.id = t.staff_id
     WHERE {$where_sql}
     ORDER BY
         FIELD(t.status,'overdue','pending','active','returned',
               'completed','rejected','cancelled'),
         t.created_at DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $pag['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pag['offset'],   PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$qs = http_build_query(array_filter([
    'search' => $search, 'status' => $status_f, 'type' => $type_f
]));
$url_pattern = APP_URL . '/staff/transactions/index.php?' . ($qs?$qs.'&':'') . 'page=%d';
$active_nav  = 'transactions';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transactions — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/staff.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/transactions.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/staff_dash.css">
</head>
<body>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>
<main class="main-content">
    <header class="topbar">
        <h2>Transactions</h2>
        <span>Welcome, <?= e($_SESSION['name']) ?></span>
    </header>
    <section class="page-body">

        <div class="table-toolbar" style="flex-wrap:wrap">
            <form method="GET" class="toolbar-filters" style="flex-wrap:wrap">
                <input type="search" name="search"
                       placeholder="Search customer or item…"
                       value="<?= e($search) ?>" class="toolbar-search">
                <select name="status" class="toolbar-select">
                    <option value="">All statuses</option>
                    <?php
                    $statuses = ['pending','active','overdue','returned','completed','rejected','cancelled'];
                    foreach ($statuses as $s):
                    ?>
                        <option value="<?= $s ?>" <?= $status_f===$s?'selected':'' ?>>
                            <?= ucfirst($s) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="type" class="toolbar-select">
                    <option value="">Rent &amp; Sale</option>
                    <option value="rent" <?= $type_f==='rent'?'selected':'' ?>>Rent</option>
                    <option value="sale" <?= $type_f==='sale'?'selected':'' ?>>Sale</option>
                </select>
                <button type="submit" class="btn btn--primary">Filter</button>
                <?php if ($search || $status_f || $type_f): ?>
                    <a href="index.php" class="btn btn--ghost">Clear</a>
                <?php endif; ?>
            </form>
            <div class="toolbar-right">
                <span class="table-count"><?= $total ?> transaction<?= $total!==1?'s':'' ?></span>
                <a href="<?= e(APP_URL) ?>/staff/transactions/create.php"
                   class="btn btn--primary">+ New Request</a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table" id="txnTable">
                <thead>
                <tr>
                    <th>#</th><th>Customer</th><th>Item</th><th>Type</th>
                    <th>Status</th><th>Borrow Date</th><th>Due / Return</th>
                    <th>Amount</th><th>Penalty</th><th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="10" class="table-empty">No transactions found.</td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                    <?php require __DIR__ . '/partials/txn_row.php'; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?= pagination_html($pag, $url_pattern) ?>

    </section>
</main>

<!-- Process Return modal -->
<div id="processReturnModal" class="modal" role="dialog" aria-modal="true" hidden>
    <div class="modal__backdrop"></div>
    <div class="modal__box modal__box--sm">
        <div class="modal__header">
            <h3 class="modal__title">Process Return</h3>
            <button class="modal__close" id="closePRModal" aria-label="Close">&times;</button>
        </div>
        <div class="modal__body">
            <p>Mark <strong id="prItemName"></strong> as returned
               from <strong id="prCustomerName"></strong>.</p>
            <div class="penalty-notice" id="prPenaltyNotice" hidden>
                <span>Overdue penalty:</span>
                <strong id="prPenaltyAmt"></strong>
            </div>
            <div class="form-group" style="margin-top:.75rem">
                <label for="prNotes">Notes (condition, remarks)</label>
                <textarea id="prNotes" rows="2" maxlength="500"></textarea>
            </div>
        </div>
        <div class="modal__footer">
            <button class="btn btn--ghost" id="cancelPR">Cancel</button>
            <button class="btn btn--primary" id="confirmPR">Mark Returned</button>
        </div>
    </div>
</div>

<div id="toast" class="toast" role="alert" aria-live="polite"></div>
<script>const APP_URL = '<?= e(APP_URL) ?>';</script>
<script src="<?= e(APP_URL) ?>/assets/js/staff_transactions.js"></script>
</body>
</html>