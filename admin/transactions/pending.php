<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/pagination.php';
require_once __DIR__.'/../../includes/txn_helpers.php';
session_guard('admin');
$db=get_db(); flag_overdue_rentals();
$search=get_param('search'); $type_filter=get_param('type');

$where=["t.status='pending'"]; $params=[];
if($search!==''){$where[]='(u.name LIKE :s OR i.name LIKE :s)';$params[':s']='%'.$search.'%';}
if(in_array($type_filter,['rent','sale'],true)){$where[]='t.type=:type';$params[':type']=$type_filter;}
$ws=implode(' AND ',$where);
$cnt=$db->prepare("SELECT COUNT(*) FROM transactions t JOIN users u ON u.id=t.customer_id JOIN items i ON i.id=t.item_id WHERE {$ws}"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
$pag=paginate($total,20);
$stmt=$db->prepare("SELECT t.id,t.type,t.borrow_date,t.due_date,t.amount_paid,t.created_at,u.id AS customer_id,u.name AS customer_name,u.phone AS customer_phone,i.id AS item_id,i.name AS item_name,i.rental_price,i.sale_price,i.stock FROM transactions t JOIN users u ON u.id=t.customer_id JOIN items i ON i.id=t.item_id WHERE {$ws} ORDER BY t.created_at ASC LIMIT :l OFFSET :o");
foreach($params as $k=>$v) $stmt->bindValue($k,$v); $stmt->bindValue(':l',$pag['per_page'],PDO::PARAM_INT); $stmt->bindValue(':o',$pag['offset'],PDO::PARAM_INT); $stmt->execute(); $rows=$stmt->fetchAll();
$qs=http_build_query(array_filter(['search'=>$search,'type'=>$type_filter]));
$url_pattern=APP_URL.'/admin/transactions/pending.php?'.($qs?$qs.'&':'').'page=%d';
$active_nav='transactions';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Pending Requests — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/transactions.css">
</head><body>
<?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content">
<header class="topbar"><h2>Transactions</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body">
<?php require __DIR__.'/partials/stats_bar.php'; ?>

<div class="txn-tab-bar"><a href="pending.php" class="tab-link tab-link--active">Pending</a><a href="active.php" class="tab-link">Active Rentals</a><a href="returns.php" class="tab-link">Returns</a><a href="overdue.php" class="tab-link">Overdue</a><a href="sales.php" class="tab-link">Sales</a><a href="completed.php" class="tab-link">Completed</a></div>
<div class="table-toolbar"><?php require __DIR__.'/partials/txn_filters.php'; ?><span class="table-count"><?= $total ?> request<?= $total!==1?'s':'' ?></span></div>
<div class="table-wrap"><table class="data-table" id="pendingTable">
<thead><tr><th>#</th><th>Customer</th><th>Item</th><th>Type</th><th>Borrow Date</th><th>Due Date</th><th>Amount</th><th>Requested</th><th>Stock</th><th>Actions</th></tr></thead>
<tbody>
<?php if(empty($rows)): ?><tr><td colspan="10" class="table-empty">No pending requests.</td></tr>
<?php else: foreach($rows as $r): ?>
<tr id="txn-row-<?= (int)$r['id'] ?>">
    <td><?= (int)$r['id'] ?></td>
    <td><div class="customer-cell"><span class="customer-name"><?= e($r['customer_name']) ?></span><span class="customer-sub"><?= e($r['customer_phone']??'') ?></span></div></td>
    <td><?= e($r['item_name']) ?></td>
    <td><span class="badge badge--type-<?= e($r['type']) ?>"><?= e(ucfirst($r['type'])) ?></span></td>
    <td><?= $r['type']==='rent'?fmt_date($r['borrow_date']):'—' ?></td>
    <td><?= $r['type']==='rent'?fmt_date($r['due_date']):'—' ?></td>
    <td><?= fmt_money($r['type']==='rent'?$r['rental_price']:$r['sale_price']) ?></td>
    <td><?= fmt_date($r['created_at']) ?></td>
    <td><span class="stock-badge <?= (int)$r['stock']===0?'stock-badge--zero':'' ?>"><?= (int)$r['stock'] ?></span></td>
    <td class="actions-cell">
        <?php if((int)$r['stock']>0): ?>
        <button class="btn btn--sm btn--success approve-btn" data-id="<?= (int)$r['id'] ?>" data-type="<?= e($r['type']) ?>" data-item="<?= e($r['item_name']) ?>" data-customer="<?= e($r['customer_name']) ?>">Approve</button>
        <?php else: ?><span class="badge badge--out-of-stock">Out of stock</span><?php endif; ?>
        <button class="btn btn--sm btn--danger reject-btn" data-id="<?= (int)$r['id'] ?>" data-item="<?= e($r['item_name']) ?>" data-customer="<?= e($r['customer_name']) ?>">Reject</button>
    </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?= pagination_html($pag,$url_pattern) ?>
</section></main>

<div id="approveModal" class="modal" role="dialog" aria-modal="true" hidden><div class="modal__backdrop"></div>
<div class="modal__box modal__box--sm"><div class="modal__header"><h3 class="modal__title">Approve Request</h3><button class="modal__close" id="closeApproveModal">&times;</button></div>
<div class="modal__body"><p>Approve <strong id="approveItemName"></strong> for <strong id="approveCustomer"></strong>?</p><p class="modal-note" id="approveNote"></p></div>
<div class="modal__footer"><button class="btn btn--ghost" id="cancelApprove">Cancel</button><button class="btn btn--success" id="confirmApprove">Yes, Approve</button></div>
</div></div>

<div id="rejectModal" class="modal" role="dialog" aria-modal="true" hidden><div class="modal__backdrop"></div>
<div class="modal__box modal__box--sm"><div class="modal__header"><h3 class="modal__title">Reject Request</h3><button class="modal__close" id="closeRejectModal">&times;</button></div>
<div class="modal__body"><p>Rejecting <strong id="rejectItemName"></strong> for <strong id="rejectCustomer"></strong>.</p>
<div class="form-group" style="margin-top:.75rem"><label for="rejectReason">Reason <span class="required">*</span></label><textarea id="rejectReason" rows="3" maxlength="500" placeholder="Explain why…"></textarea><span class="field-error" id="rejectReasonError"></span></div></div>
<div class="modal__footer"><button class="btn btn--ghost" id="cancelReject">Cancel</button><button class="btn btn--danger" id="confirmReject">Reject</button></div>
</div></div>

<div id="toast" class="toast" role="alert" aria-live="polite"></div>
<script>const APP_URL='<?= e(APP_URL) ?>';</script>
<script src="<?= e(APP_URL) ?>/assets/js/transactions.js"></script>
</body></html>
