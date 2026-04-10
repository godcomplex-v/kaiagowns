<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/log_activity.php';
session_guard('admin');
$db=get_db(); $cats=$db->query("SELECT id,name FROM categories ORDER BY name")->fetchAll();
$errors=[]; $old=[];
function handle_upload(array $file, ?string $old_img=null): array {
    $allowed=['image/jpeg','image/png','image/webp']; $ext_map=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    if($file['error']!==UPLOAD_ERR_OK) return [null,'Upload failed.'];
    if($file['size']>2*1024*1024) return [null,'Image must be under 2 MB.'];
    if(!in_array($file['type'],$allowed,true)) return [null,'Only JPG, PNG, WebP allowed.'];
    $fname=uniqid('item_',true).'.'.$ext_map[$file['type']];
    $dest=UPLOAD_DIR.'items/'.$fname;
    if(!is_dir(UPLOAD_DIR.'items/')) mkdir(UPLOAD_DIR.'items/',0755,true);
    if(!move_uploaded_file($file['tmp_name'],$dest)) return [null,'Could not save image.'];
    if($old_img&&file_exists(UPLOAD_DIR.'items/'.$old_img)) unlink(UPLOAD_DIR.'items/'.$old_img);
    return [$fname,null];
}
if(is_post()){
    $old=['name'=>post('name'),'category_id'=>(int)post('category_id'),'size'=>post('size'),'stock'=>post('stock'),'rental_price'=>post('rental_price'),'sale_price'=>post('sale_price'),'status'=>post('status'),'description'=>post('description')];
    if(!$old['name']) $errors['name']='Item name is required.';
    if($old['category_id']<=0) $errors['category']='Please select a category.';
    if(!is_numeric($old['rental_price'])||(float)$old['rental_price']<0) $errors['rental']='Enter a valid rental price.';
    if(!is_numeric($old['sale_price'])||(float)$old['sale_price']<0) $errors['sale']='Enter a valid sale price.';
    if(!in_array($old['status'],['available','reserved','damaged','retired'],true)) $errors['status']='Invalid status.';
    if(!is_numeric($old['stock'])||(int)$old['stock']<0) $errors['stock']='Stock must be 0 or more.';
    $img=null;
    if(!empty($_FILES['image']['name'])){[$img,$err]=handle_upload($_FILES['image']);if($err)$errors['image']=$err;}
    if(empty($errors)){
        $db->prepare('INSERT INTO items (name,category_id,size,stock,rental_price,sale_price,status,image,description) VALUES (:n,:c,:s,:st,:r,:sa,:status,:img,:desc)')
           ->execute([':n'=>$old['name'],':c'=>$old['category_id'],':s'=>$old['size']?:null,':st'=>(int)$old['stock'],':r'=>(float)$old['rental_price'],':sa'=>(float)$old['sale_price'],':status'=>$old['status'],':img'=>$img,':desc'=>$old['description']?:null]);
        $nid=(int)$db->lastInsertId();
        if((int)$old['stock']>0) $db->prepare('INSERT INTO stock_history (item_id, `change`, reason, staff_id) VALUES (:i, :c, :r, :s)')->execute([':i'=>$nid,':c'=>(int)$old['stock'],':r'=>'Initial stock on item creation',':s'=>$_SESSION['user_id']]);

        log_activity('add_item',"Item ID={$nid} name={$old['name']}");
        redirect(APP_URL.'/admin/inventory/index.php?added=1');
    }
}
$active_nav='inventory';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Add Item — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/inventory.css">
</head><body>
<?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content">
<header class="topbar"><h2>Add Item</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body"><div class="form-card" style="max-width:960px">
<div class="form-card__header"><a href="<?= e(APP_URL) ?>/admin/inventory/index.php" class="btn btn--ghost btn--sm">← Back</a><h3>New Inventory Item</h3></div>
<?php if(!empty($errors)): ?><div class="alert alert--error" style="margin:1rem 1.5rem 0">Please fix the errors below.</div><?php endif; ?>
<form method="POST" enctype="multipart/form-data" id="itemForm" novalidate style="padding:1.5rem">
<?php require __DIR__.'/partials/item_form_fields.php'; ?>
<div class="form-actions"><a href="<?= e(APP_URL) ?>/admin/inventory/index.php" class="btn btn--ghost">Cancel</a><button type="submit" class="btn btn--primary">Save Item</button></div>
</form></div></section></main>
<script>const APP_URL='<?= e(APP_URL) ?>';</script>
<script src="<?= e(APP_URL) ?>/assets/js/inventory_form.js"></script>
</body></html>
