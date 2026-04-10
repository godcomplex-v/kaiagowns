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

$cnt = $db->prepare(
    "SELECT COUNT(*) FROM transactions
     WHERE customer_id=:uid AND type='sale'"
);
$cnt->execute([':uid' => $uid]);
$total = (int)$cnt->fetchColumn();
$pag   = paginate($total, 15);

$stmt = $db->prepare(
    "SELECT t.id, t.status, t.amount_paid, t.created_at, t.notes,
            i.name AS item_name, i.image AS item_image,
            c.name AS category
     FROM transactions t
     JOIN items i ON i.id = t.item_id
     LEFT JOIN categories c ON c.id = i.category_id
     WHERE t.customer_id = :uid AND t.type = 'sale'
     ORDER BY t.created_at DESC
     LIMIT :limit OFFSET :offset"
);
$stmt->bindValue(':uid',    $uid, PDO::PARAM_INT);
$stmt->bindValue(':limit',  $pag['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pag['offset'],   PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$url_pattern = APP_URL . '/customer/purchases/index.php?page=%d';
$active_nav  = 'purchases';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Purchases — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/customer.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/customer_dash.css">
</head>
<body>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>
<main class="main-content">
    <header class="topbar"><h2>My Purchases</h2></header>
    <section class="page-body">

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                <tr>
                    <th>Item</th><th>Category</th><th>Status</th>
                    <th>Date</th><th>Amount Paid</th><th>Receipt</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="table-empty">
                        No purchases yet.
                        <a href="<?= e(APP_URL) ?>/customer/catalog/index.php">Browse the catalog →</a>
                    </td></tr>
                <?php else: ?>
                    <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:.5rem">
                                <img src="<?= $r['item_image']
                                    ? e(UPLOAD_URL.'items/'.$r['item_image'])
                                    : e(APP_URL.'/assets/images/no-image.svg') ?>"
                                     class="inv-thumb" style="width:36px;height:36px" alt="">
                                <?= e($r['item_name']) ?>
                            </div>
                        </td>
                        <td><?= e($r['category'] ?? '—') ?></td>
                        <td>
                            <span class="badge badge--<?= e($r['status']) ?>">
                                <?= e(ucfirst($r['status'])) ?>
                            </span>
                        </td>
                        <td><?= fmt_date($r['created_at']) ?></td>
                        <td><?= fmt_money($r['amount_paid']) ?></td>
                        <td>
                            <?php if ($r['status']==='completed'): ?>
                            <a href="<?= e(APP_URL) ?>/customer/purchases/receipt.php?id=<?= (int)$r['id'] ?>"
                               target="_blank" class="btn btn--sm btn--ghost">
                                🖨 Receipt
                            </a>
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
</body>
</html>