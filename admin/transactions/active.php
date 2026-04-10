<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php'; require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php'; require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/pagination.php'; require_once __DIR__.'/../../includes/txn_helpers.php';
session_guard('admin'); $db=get_db(); flag_overdue_rentals();
$search=get_param('search'); $type_filter='';
$where=["t.status='active'"]; $params=[];
if($search!==''){$where[]='(u.name LIKE :s OR i.name LIKE :s)';$params[':s']='%'.$search.'%';}
$ws=implode(' AND ',$where);
$cnt=$db->prepare("SELECT COUNT(*) FROM transactions t JOIN users u ON u.id=t.customer_id JOIN items i ON i.id=t.item_id WHERE {$ws}"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
$pag=paginate($total,20);
$stmt=$db->prepare("SELECT t.id,t.type,t.borrow_date,t.due_date,t.amount_paid,u.name AS customer_name,u.phone AS customer_phone,i.name AS item_name,DATEDIFF(t.due_date,CURDATE()) AS days_remaining FROM transactions t JOIN users u ON u.id=t.customer_id JOIN items i ON i.id=t.item_id WHERE {$ws} ORDER BY t.due_date ASC LIMIT :l OFFSET :o");
foreach($params as $k=>$v) $stmt->bindValue($k,$v); $stmt->bindValue(':l',$pag['per_page'],PDO::PARAM_INT); $stmt->bindValue(':o',$pag['offset'],PDO::PARAM_INT); $stmt->execute(); $rows=$stmt->fetchAll();
$qs=http_build_query(array_filter(['search'=>$search]));
$url_pattern=APP_URL.'/admin/transactions/active.php?'.($qs?$qs.'&':'').'page=%d';
$active_nav='transactions';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Active Rentals — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/transactions.css">
</head><body><?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content"><header class="topbar"><h2>Transactions</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body"><?php require __DIR__.'/partials/stats_bar.php'; ?>
<div class="txn-tab-bar"><a href="pending.php" class="tab-link">Pending</a><a href="active.php" class="tab-link tab-link--active">Active Rentals</a><a href="returns.php" class="tab-link">Returns</a><a href="overdue.php" class="tab-link">Overdue</a><a href="sales.php" class="tab-link">Sales</a><a href="completed.php" class="tab-link">Completed</a></div>
<div class="table-toolbar"><?php require __DIR__.'/partials/txn_filters.php'; ?><span class="table-count"><?= $total ?> active rental<?= $total!==1?'s':'' ?></span></div>
<div class="table-wrap"><table class="data-table"><thead><tr><th>#</th><th>Customer</th><th>Item</th><th>Borrow Date</th><th>Due Date</th><th>Amount Paid</th><th>Days Left</th></tr></thead>
<tbody><?php if(empty($rows)): ?><tr><td colspan="7" class="table-empty">No active rentals.</td></tr>
<?php else: foreach($rows as $r): ?>
<?php $days=(int)$r['days_remaining']; $urg=$days<=0?'due-critical':($days<=2?'due-warning':''); ?>
<tr class="<?= $urg ?>"><td><?= (int)$r['id'] ?></td>
<td><div class="customer-cell"><span class="customer-name"><?= e($r['customer_name']) ?></span><span class="customer-sub"><?= e($r['customer_phone']??'') ?></span></div></td>
<td><?= e($r['item_name']) ?></td><td><?= fmt_date($r['borrow_date']) ?></td><td><?= fmt_date($r['due_date']) ?></td><td><?= fmt_money($r['amount_paid']) ?></td>
<td><span class="days-pill <?= $urg ?>"><?= $days>=0?$days.'d left':abs($days).'d overdue' ?></span></td>
</tr><?php endforeach; endif; ?>
</tbody></table></div><?= pagination_html($pag,$url_pattern) ?>
</section></main><div id="toast" class="toast"></div>
<script>const APP_URL='<?= e(APP_URL) ?>';</script><script src="<?= e(APP_URL) ?>/assets/js/transactions.js"></script>
</body></html>
