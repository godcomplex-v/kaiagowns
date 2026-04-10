<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php'; require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php'; require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/pagination.php'; require_once __DIR__.'/../../includes/txn_helpers.php';
session_guard('admin'); $db=get_db(); flag_overdue_rentals();
$search=get_param('search'); $type_filter='sale';
$where=["t.status='completed'","t.type='sale'"]; $params=[];
if($search!==''){$where[]='(u.name LIKE :s OR i.name LIKE :s)';$params[':s']='%'.$search.'%';}
$ws=implode(' AND ',$where);
$cnt=$db->prepare("SELECT COUNT(*) FROM transactions t JOIN users u ON u.id=t.customer_id JOIN items i ON i.id=t.item_id WHERE {$ws}"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
$pag=paginate($total,20);
$stmt=$db->prepare("SELECT t.id,t.created_at,t.amount_paid,t.notes,u.name AS customer_name,i.name AS item_name FROM transactions t JOIN users u ON u.id=t.customer_id JOIN items i ON i.id=t.item_id WHERE {$ws} ORDER BY t.created_at DESC LIMIT :l OFFSET :o");
foreach($params as $k=>$v) $stmt->bindValue($k,$v); $stmt->bindValue(':l',$pag['per_page'],PDO::PARAM_INT); $stmt->bindValue(':o',$pag['offset'],PDO::PARAM_INT); $stmt->execute(); $rows=$stmt->fetchAll();
$url_pattern=APP_URL.'/admin/transactions/sales.php?page=%d';
$active_nav='transactions';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Sales — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/transactions.css">
</head><body><?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content"><header class="topbar"><h2>Transactions</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body"><?php require __DIR__.'/partials/stats_bar.php'; ?>
<div class="txn-tab-bar"><a href="pending.php" class="tab-link">Pending</a><a href="active.php" class="tab-link">Active Rentals</a><a href="returns.php" class="tab-link">Returns</a><a href="overdue.php" class="tab-link">Overdue</a><a href="sales.php" class="tab-link tab-link--active">Sales</a><a href="completed.php" class="tab-link">Completed</a></div>
<div class="table-toolbar"><?php require __DIR__.'/partials/txn_filters.php'; ?><span class="table-count"><?= $total ?> sale<?= $total!==1?'s':'' ?></span></div>
<div class="table-wrap"><table class="data-table"><thead><tr><th>#</th><th>Customer</th><th>Item</th><th>Sale Date</th><th>Amount Paid</th><th>Notes</th></tr></thead>
<tbody><?php if(empty($rows)): ?><tr><td colspan="6" class="table-empty">No sales recorded yet.</td></tr>
<?php else: foreach($rows as $r): ?>
<tr><td><?= (int)$r['id'] ?></td><td><?= e($r['customer_name']) ?></td><td><?= e($r['item_name']) ?></td><td><?= fmt_date($r['created_at']) ?></td><td><?= fmt_money($r['amount_paid']) ?></td><td><?= e($r['notes']??'—') ?></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div><?= pagination_html($pag,$url_pattern) ?>
</section></main>
<div id="toast" class="toast"></div><script>const APP_URL='<?= e(APP_URL) ?>';</script><script src="<?= e(APP_URL) ?>/assets/js/transactions.js"></script>
</body></html>
