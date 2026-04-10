<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php'; require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php'; require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/pagination.php'; require_once __DIR__.'/../../includes/txn_helpers.php';
session_guard('admin'); $db=get_db(); flag_overdue_rentals();
$search=get_param('search'); $type_filter='';
$where=["t.status='overdue'"]; $params=[];
if($search!==''){$where[]='(u.name LIKE :s OR i.name LIKE :s)';$params[':s']='%'.$search.'%';}
$ws=implode(' AND ',$where);
$cnt=$db->prepare("SELECT COUNT(*) FROM transactions t JOIN users u ON u.id=t.customer_id JOIN items i ON i.id=t.item_id WHERE {$ws}"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
$pag=paginate($total,20);
$stmt=$db->prepare("SELECT t.id,t.borrow_date,t.due_date,t.amount_paid,u.name AS customer_name,u.phone AS customer_phone,i.id AS item_id,i.name AS item_name,DATEDIFF(CURDATE(),t.due_date) AS days_late,(DATEDIFF(CURDATE(),t.due_date)*:ppd) AS est_penalty FROM transactions t JOIN users u ON u.id=t.customer_id JOIN items i ON i.id=t.item_id WHERE {$ws} ORDER BY days_late DESC LIMIT :l OFFSET :o");
$stmt->bindValue(':ppd',PENALTY_PER_DAY);
foreach($params as $k=>$v) $stmt->bindValue($k,$v); $stmt->bindValue(':l',$pag['per_page'],PDO::PARAM_INT); $stmt->bindValue(':o',$pag['offset'],PDO::PARAM_INT); $stmt->execute(); $rows=$stmt->fetchAll();
$qs=http_build_query(array_filter(['search'=>$search]));
$url_pattern=APP_URL.'/admin/transactions/overdue.php?'.($qs?$qs.'&':'').'page=%d';
$active_nav='transactions';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Overdue — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/transactions.css">
</head><body><?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content"><header class="topbar"><h2>Transactions</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body"><?php require __DIR__.'/partials/stats_bar.php'; ?>
<div class="txn-tab-bar"><a href="pending.php" class="tab-link">Pending</a><a href="active.php" class="tab-link">Active Rentals</a><a href="returns.php" class="tab-link">Returns</a><a href="overdue.php" class="tab-link tab-link--active">Overdue</a><a href="sales.php" class="tab-link">Sales</a><a href="completed.php" class="tab-link">Completed</a></div>
<div class="table-toolbar"><?php require __DIR__.'/partials/txn_filters.php'; ?><span class="table-count"><?= $total ?> overdue item<?= $total!==1?'s':'' ?></span></div>
<div class="table-wrap"><table class="data-table" id="overdueTable">
<thead><tr><th>#</th><th>Customer</th><th>Item</th><th>Borrow Date</th><th>Due Date</th><th>Days Late</th><th>Est. Penalty</th><th>Actions</th></tr></thead>
<tbody><?php if(empty($rows)): ?><tr><td colspan="8" class="table-empty">No overdue rentals. 🎉</td></tr>
<?php else: foreach($rows as $r): ?>
<tr id="txn-row-<?= (int)$r['id'] ?>" class="overdue-row">
    <td><?= (int)$r['id'] ?></td>
    <td><div class="customer-cell"><span class="customer-name"><?= e($r['customer_name']) ?></span><span class="customer-sub"><?= e($r['customer_phone']??'') ?></span></div></td>
    <td><?= e($r['item_name']) ?></td><td><?= fmt_date($r['borrow_date']) ?></td><td><?= fmt_date($r['due_date']) ?></td>
    <td><span class="days-pill due-critical"><?= (int)$r['days_late'] ?>d</span></td>
    <td><span class="penalty-amt"><?= fmt_money($r['est_penalty']) ?></span></td>
    <td><button class="btn btn--sm btn--primary process-return-btn" data-id="<?= (int)$r['id'] ?>" data-item="<?= e($r['item_name']) ?>" data-customer="<?= e($r['customer_name']) ?>" data-days-late="<?= (int)$r['days_late'] ?>" data-est-penalty="<?= (float)$r['est_penalty'] ?>" data-item-id="<?= (int)$r['item_id'] ?>">Process Return</button></td>
</tr><?php endforeach; endif; ?>
</tbody></table></div><?= pagination_html($pag,$url_pattern) ?>
</section></main>

<div id="processReturnModal" class="modal" role="dialog" aria-modal="true" hidden><div class="modal__backdrop"></div>
<div class="modal__box modal__box--sm"><div class="modal__header"><h3 class="modal__title">Process Return</h3><button class="modal__close" id="closePRModal">&times;</button></div>
<div class="modal__body"><p>Processing return of <strong id="prItemName"></strong> from <strong id="prCustomer"></strong>.</p>
<div class="penalty-notice"><span>Days late: <strong id="prDaysLate"></strong></span><span>Penalty: <strong id="prPenalty"></strong></span></div>
<div class="form-group" style="margin-top:.75rem"><label for="prNotes">Notes (optional)</label><textarea id="prNotes" rows="2" maxlength="500"></textarea></div></div>
<div class="modal__footer"><button class="btn btn--ghost" id="cancelPR">Cancel</button><button class="btn btn--danger" id="confirmPR">Mark as Returned</button></div>
</div></div>

<div id="toast" class="toast"></div>
<script>const APP_URL='<?= e(APP_URL) ?>';</script><script src="<?= e(APP_URL) ?>/assets/js/transactions.js"></script>
</body></html>
