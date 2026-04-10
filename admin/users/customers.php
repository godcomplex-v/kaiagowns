<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/pagination.php';
session_guard('admin');
$db=get_db();
$search=get_param('search'); $status=get_param('status');
$where=["role='customer'"]; $params=[];
if($search!==''){$where[]='(name LIKE :s OR email LIKE :s OR phone LIKE :s)';$params[':s']='%'.$search.'%';}
if(in_array($status,['active','inactive','suspended'],true)){$where[]='status=:status';$params[':status']=$status;}
$ws='WHERE '.implode(' AND ',$where);
$cnt=get_db()->prepare("SELECT COUNT(*) FROM users {$ws}"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
$pag=paginate($total,15);
$stmt=get_db()->prepare("SELECT id,name,email,phone,status,created_at FROM users {$ws} ORDER BY created_at DESC LIMIT :l OFFSET :o");
foreach($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->bindValue(':l',$pag['per_page'],PDO::PARAM_INT); $stmt->bindValue(':o',$pag['offset'],PDO::PARAM_INT); $stmt->execute();
$customers=$stmt->fetchAll();
$qs=http_build_query(array_filter(['search'=>$search,'status'=>$status]));
$url_pattern=APP_URL.'/admin/users/customers.php?'.($qs?$qs.'&':'').'page=%d';
$active_nav='users';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Customers — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
</head><body>
<?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content">
<header class="topbar"><h2>Manage Users</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body">
<div class="tab-bar"><a href="customers.php" class="tab-link tab-link--active">Customers</a><a href="staff.php" class="tab-link">Staff</a></div>
<div class="table-toolbar">
    <form method="GET" class="toolbar-filters">
        <input type="search" name="search" placeholder="Search name, email, phone…" value="<?= e($search) ?>" class="toolbar-search">
        <select name="status" class="toolbar-select"><option value="">All statuses</option>
            <option value="active" <?= $status==='active'?'selected':'' ?>>Active</option>
            <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactive</option>
            <option value="suspended" <?= $status==='suspended'?'selected':'' ?>>Suspended</option></select>
        <button type="submit" class="btn btn--primary">Filter</button>
        <?php if($search||$status): ?><a href="customers.php" class="btn btn--ghost">Clear</a><?php endif; ?>
    </form>
    <span class="table-count"><?= $total ?> customer<?= $total!==1?'s':'' ?></span>
</div>
<div class="table-wrap"><table class="data-table" id="customerTable">
<thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Registered</th><th>Actions</th></tr></thead>
<tbody>
<?php if(empty($customers)): ?><tr><td colspan="7" class="table-empty">No customers found.</td></tr>
<?php else: foreach($customers as $i=>$c): ?>
<tr id="customer-row-<?= (int)$c['id'] ?>">
    <td><?= $i+1 ?></td><td><?= e($c['name']) ?></td><td><?= e($c['email']) ?></td>
    <td><?= e($c['phone']??'—') ?></td>
    <td><span class="badge badge--<?= e($c['status']) ?>"><?= e(ucfirst($c['status'])) ?></span></td>
    <td><?= fmt_date($c['created_at']) ?></td>
    <td><?php if($c['status']==='active'): ?>
        <button class="btn btn--sm btn--danger toggle-status" data-id="<?= (int)$c['id'] ?>" data-action="deactivate">Deactivate</button>
        <?php else: ?>
        <button class="btn btn--sm btn--success toggle-status" data-id="<?= (int)$c['id'] ?>" data-action="activate">Activate</button>
        <?php endif; ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?= pagination_html($pag,$url_pattern) ?>
</section></main>
<div id="toast" class="toast" role="alert" aria-live="polite"></div>
<script>const APP_URL='<?= e(APP_URL) ?>';</script>
<script src="<?= e(APP_URL) ?>/assets/js/users_customers.js"></script>
</body></html>
