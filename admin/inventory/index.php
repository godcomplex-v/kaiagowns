<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/pagination.php';
session_guard('admin');
$db=get_db(); $search=get_param('search'); $cat_id=(int)get_param('category'); $status=get_param('status');
$where=['1=1']; $params=[];
if($search!==''){$where[]='(i.name LIKE :s OR i.description LIKE :s)';$params[':s']='%'.$search.'%';}
if($cat_id>0){$where[]='i.category_id=:cat';$params[':cat']=$cat_id;}
if(in_array($status,['available','reserved','damaged','retired'],true)){$where[]='i.status=:status';$params[':status']=$status;}
$ws=implode(' AND ',$where);
$cnt=$db->prepare("SELECT COUNT(*) FROM items i WHERE {$ws}"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
$pag=paginate($total,15);
$stmt=$db->prepare("SELECT i.id,i.name,c.name AS category,i.size,i.stock,i.rental_price,i.sale_price,i.status,i.image,i.created_at FROM items i LEFT JOIN categories c ON c.id=i.category_id WHERE {$ws} ORDER BY i.created_at DESC LIMIT :l OFFSET :o");
foreach($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->bindValue(':l',$pag['per_page'],PDO::PARAM_INT); $stmt->bindValue(':o',$pag['offset'],PDO::PARAM_INT); $stmt->execute(); $items=$stmt->fetchAll();
$cats=$db->query("SELECT id,name FROM categories ORDER BY name")->fetchAll();
$qs=http_build_query(array_filter(['search'=>$search,'category'=>$cat_id?:'','status'=>$status]));
$url_pattern=APP_URL.'/admin/inventory/index.php?'.($qs?$qs.'&':'').'page=%d';
$active_nav='inventory';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Inventory — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/inventory.css">
</head><body>
<?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content">
<header class="topbar"><h2>Inventory</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body">
<div class="table-toolbar">
    <form method="GET" class="toolbar-filters">
        <input type="search" name="search" placeholder="Search items…" value="<?= e($search) ?>" class="toolbar-search">
        <select name="category" class="toolbar-select"><option value="">All categories</option>
            <?php foreach($cats as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= $cat_id===(int)$cat['id']?'selected':'' ?>><?= e($cat['name']) ?></option><?php endforeach; ?>
        </select>
        <select name="status" class="toolbar-select"><option value="">All statuses</option>
            <option value="available" <?= $status==='available'?'selected':'' ?>>Available</option>
            <option value="reserved" <?= $status==='reserved'?'selected':'' ?>>Reserved</option>
            <option value="damaged" <?= $status==='damaged'?'selected':'' ?>>Damaged</option>
            <option value="retired" <?= $status==='retired'?'selected':'' ?>>Retired</option>
        </select>
        <button type="submit" class="btn btn--primary">Filter</button>
        <?php if($search||$cat_id||$status): ?><a href="<?= e(APP_URL) ?>/admin/inventory/index.php" class="btn btn--ghost">Clear</a><?php endif; ?>
    </form>
    <div class="toolbar-right">
        <span class="table-count"><?= $total ?> item<?= $total!==1?'s':'' ?></span>
        <button class="btn btn--ghost" id="manageCatsBtn">⚙ Categories</button>
        <a href="<?= e(APP_URL) ?>/admin/inventory/add.php" class="btn btn--primary">+ Add Item</a>
    </div>
</div>
<div class="table-wrap"><table class="data-table inv-table" id="invTable">
<thead><tr><th>Image</th><th>Name</th><th>Category</th><th>Size</th><th>Stock</th><th>Rental</th><th>Sale</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php if(empty($items)): ?><tr><td colspan="9" class="table-empty">No items found.</td></tr>
<?php else: foreach($items as $item): ?>
    <?php require __DIR__.'/partials/item_row.php'; ?>
<?php endforeach; endif; ?>
</tbody></table></div>
<?= pagination_html($pag,$url_pattern) ?>
</section></main>

<!-- Category Modal -->
<div id="catModal" class="modal" role="dialog" aria-modal="true" hidden><div class="modal__backdrop"></div>
<div class="modal__box modal__box--sm"><div class="modal__header"><h3 class="modal__title">Manage Categories</h3><button class="modal__close" id="closeCatModal">&times;</button></div>
<div class="modal__body">
    <form id="catForm" class="cat-form" novalidate><input type="hidden" id="catId" name="id" value="">
        <div class="cat-form-row"><input type="text" id="catName" name="name" placeholder="Category name" maxlength="100" required class="toolbar-search" style="flex:1">
        <button type="submit" class="btn btn--primary" id="saveCatBtn">Add</button>
        <button type="button" class="btn btn--ghost" id="cancelCatEdit" hidden>Cancel</button></div>
        <span class="field-error" id="catError"></span>
    </form>
    <ul class="cat-list" id="catList">
        <?php foreach($cats as $cat): ?>
        <li class="cat-item" id="cat-item-<?= (int)$cat['id'] ?>"><span><?= e($cat['name']) ?></span>
            <div class="cat-actions"><button class="btn btn--sm btn--ghost edit-cat" data-id="<?= (int)$cat['id'] ?>" data-name="<?= e($cat['name']) ?>">Edit</button>
            <button class="btn btn--sm btn--danger delete-cat" data-id="<?= (int)$cat['id'] ?>" data-name="<?= e($cat['name']) ?>">Delete</button></div>
        </li>
        <?php endforeach; ?>
        <?php if(empty($cats)): ?><li class="cat-empty" id="catEmpty">No categories yet.</li><?php endif; ?>
    </ul>
</div></div></div>

<!-- Stock Modal -->
<div id="stockModal" class="modal" role="dialog" aria-modal="true" hidden><div class="modal__backdrop"></div>
<div class="modal__box"><div class="modal__header"><h3 class="modal__title">Stock Adjustment</h3><button class="modal__close" id="closeStockModal">&times;</button></div>
<div class="modal__body">
    <p class="stock-item-name" id="stockItemName"></p>
    <p class="stock-current">Current stock: <strong id="stockCurrentVal">—</strong></p>
    <form id="stockForm" novalidate><input type="hidden" id="stockItemId" name="item_id">
        <div class="form-row">
            <div class="form-group"><label for="stockChange">Adjustment <span class="required">*</span></label><input type="number" id="stockChange" name="change" placeholder="e.g. +3 or -1" required><span class="field-error" id="stockChangeError"></span></div>
            <div class="form-group"><label for="stockReason">Reason <span class="required">*</span></label><input type="text" id="stockReason" name="reason" placeholder="e.g. New delivery" maxlength="255" required><span class="field-error" id="stockReasonError"></span></div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:.5rem">
            <button type="button" class="btn btn--ghost" id="cancelStock">Cancel</button>
            <button type="submit" class="btn btn--primary" id="saveStockBtn">Apply</button>
        </div>
    </form>
    <hr class="divider"><h4 class="history-title">Stock History</h4>
    <div id="stockHistory" class="stock-history"><p class="history-loading">Loading…</p></div>
</div></div></div>

<!-- Delete Item Modal -->
<div id="deleteItemModal" class="modal" role="dialog" aria-modal="true" hidden><div class="modal__backdrop"></div>
<div class="modal__box modal__box--sm"><div class="modal__header"><h3 class="modal__title">Remove Item</h3><button class="modal__close" id="closeDeleteItemModal">&times;</button></div>
<div class="modal__body"><p>Remove <strong id="deleteItemName"></strong> from inventory?</p></div>
<div class="modal__footer"><button type="button" class="btn btn--ghost" id="cancelDeleteItem">Cancel</button><button type="button" class="btn btn--danger" id="confirmDeleteItem">Yes, Remove</button></div>
</div></div>

<div id="toast" class="toast" role="alert" aria-live="polite"></div>
<script>const APP_URL='<?= e(APP_URL) ?>';</script>
<script src="<?= e(APP_URL) ?>/assets/js/inventory_list.js"></script>
</body></html>
