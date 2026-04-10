<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/pagination.php';
session_guard('admin');
$db=get_db(); $search=get_param('search');
$where=["role='staff'"]; $params=[];
if($search!==''){$where[]='(name LIKE :s OR email LIKE :s)';$params[':s']='%'.$search.'%';}
$ws='WHERE '.implode(' AND ',$where);
$cnt=$db->prepare("SELECT COUNT(*) FROM users {$ws}"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
$pag=paginate($total,15);
$stmt=$db->prepare("SELECT id,name,email,phone,status,created_at FROM users {$ws} ORDER BY created_at DESC LIMIT :l OFFSET :o");
foreach($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->bindValue(':l',$pag['per_page'],PDO::PARAM_INT); $stmt->bindValue(':o',$pag['offset'],PDO::PARAM_INT); $stmt->execute();
$staff_list=$stmt->fetchAll();
$qs=http_build_query(array_filter(['search'=>$search]));
$url_pattern=APP_URL.'/admin/users/staff.php?'.($qs?$qs.'&':'').'page=%d';
$active_nav='users';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Staff — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
</head><body>
<?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content">
<header class="topbar"><h2>Manage Users</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body">
<div class="tab-bar"><a href="customers.php" class="tab-link">Customers</a><a href="staff.php" class="tab-link tab-link--active">Staff</a></div>
<div class="table-toolbar">
    <form method="GET" class="toolbar-filters">
        <input type="search" name="search" placeholder="Search name or email…" value="<?= e($search) ?>" class="toolbar-search">
        <button type="submit" class="btn btn--primary">Search</button>
        <?php if($search): ?><a href="staff.php" class="btn btn--ghost">Clear</a><?php endif; ?>
    </form>
    <div class="toolbar-right">
        <span class="table-count"><?= $total ?> staff member<?= $total!==1?'s':'' ?></span>
        <button class="btn btn--primary" id="addStaffBtn">+ Add Staff</button>
    </div>
</div>
<div class="table-wrap"><table class="data-table" id="staffTable">
<thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Added</th><th>Actions</th></tr></thead>
<tbody>
<?php if(empty($staff_list)): ?><tr><td colspan="7" class="table-empty">No staff members found.</td></tr>
<?php else: foreach($staff_list as $i=>$s): ?>
    <?php require __DIR__.'/partials/staff_row.php'; ?>
<?php endforeach; endif; ?>
</tbody></table></div>
<?= pagination_html($pag,$url_pattern) ?>
</section></main>

<div id="staffModal" class="modal" role="dialog" aria-modal="true" hidden>
    <div class="modal__backdrop"></div>
    <div class="modal__box"><div class="modal__header"><h3 class="modal__title" id="modalTitle">Add Staff Member</h3><button class="modal__close" id="closeModal">&times;</button></div>
    <form id="staffForm" novalidate><input type="hidden" id="staffId" name="id" value="">
    <div class="modal__body">
        <div class="form-row">
            <div class="form-group"><label for="staffName">Full Name <span class="required">*</span></label><input type="text" id="staffName" name="name" maxlength="150" required><span class="field-error" id="nameError"></span></div>
            <div class="form-group"><label for="staffEmail">Email <span class="required">*</span></label><input type="email" id="staffEmail" name="email" maxlength="150" required><span class="field-error" id="emailError"></span></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label for="staffPhone">Phone</label><input type="tel" id="staffPhone" name="phone" maxlength="30"></div>
            <div class="form-group"><label for="staffStatus">Status</label><select id="staffStatus" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label for="staffPassword">Password <span class="required" id="pwRequired">*</span><span id="pwHint" class="field-hint" hidden>(leave blank to keep current)</span></label><input type="password" id="staffPassword" name="password" maxlength="100" autocomplete="new-password"><span class="field-error" id="passwordError"></span></div>
            <div class="form-group"><label for="staffPasswordConfirm">Confirm Password <span class="required" id="cpwRequired">*</span></label><input type="password" id="staffPasswordConfirm" name="password_confirm" maxlength="100" autocomplete="new-password"><span class="field-error" id="confirmError"></span></div>
        </div>
    </div>
    <div class="modal__footer"><button type="button" class="btn btn--ghost" id="cancelModal">Cancel</button><button type="submit" class="btn btn--primary" id="saveStaffBtn">Save</button></div>
    </form></div>
</div>

<div id="deleteModal" class="modal" role="dialog" aria-modal="true" hidden>
    <div class="modal__backdrop"></div>
    <div class="modal__box modal__box--sm"><div class="modal__header"><h3 class="modal__title">Remove Staff Member</h3><button class="modal__close" id="closeDeleteModal">&times;</button></div>
    <div class="modal__body"><p>Are you sure you want to remove <strong id="deleteStaffName"></strong>?</p></div>
    <div class="modal__footer"><button type="button" class="btn btn--ghost" id="cancelDelete">Cancel</button><button type="button" class="btn btn--danger" id="confirmDelete">Yes, Remove</button></div>
    </div>
</div>

<div id="toast" class="toast" role="alert" aria-live="polite"></div>
<script>const APP_URL='<?= e(APP_URL) ?>';</script>
<script src="<?= e(APP_URL) ?>/assets/js/users_staff.js"></script>
</body></html>
