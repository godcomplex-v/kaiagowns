<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/log_activity.php';
session_guard('admin');
$db=get_db(); $id=(int)get_param('id');
if($id<=0) redirect(APP_URL.'/admin/inventory/index.php');
$stmt=$db->prepare('SELECT * FROM items WHERE id=:id LIMIT 1'); $stmt->execute([':id'=>$id]); $item=$stmt->fetch();
if(!$item) redirect(APP_URL.'/admin/inventory/index.php');
$cats=$db->query("SELECT id,name FROM categories ORDER BY name")->fetchAll();
$errors=[]; $old=$item;
if(is_post()){
    $old=['id'=>$id,'name'=>post('name'),'category_id'=>(int)post('category_id'),'size'=>post('size'),'stock'=>$item['stock'],'rental_price'=>post('rental_price'),'sale_price'=>post('sale_price'),'status'=>post('status'),'description'=>post('description'),'image'=>$item['image']];
    if(!$old['name']) $errors['name']='Item name is required.';
    if($old['category_id']<=0) $errors['category']='Please select a category.';
    if(!is_numeric($old['rental_price'])||(float)$old['rental_price']<0) $errors['rental']='Enter a valid rental price.';
    if(!is_numeric($old['sale_price'])||(float)$old['sale_price']<0) $errors['sale']='Enter a valid sale price.';
    $img=$item['image'];
    if(!empty($_FILES['image']['name'])){
        $allowed=['image/jpeg','image/png','image/webp']; $em=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp']; $f=$_FILES['image'];
        if($f['error']!==UPLOAD_ERR_OK) $errors['image']='Upload failed.';
        elseif($f['size']>2*1024*1024) $errors['image']='Image must be under 2 MB.';
        elseif(!in_array($f['type'],$allowed,true)) $errors['image']='Only JPG, PNG, WebP.';
        else {
            $fname=uniqid('item_',true).'.'.$em[$f['type']];
            $dest=UPLOAD_DIR.'items/'.$fname;
            if(!is_dir(UPLOAD_DIR.'items/')) mkdir(UPLOAD_DIR.'items/',0755,true);
            if(move_uploaded_file($f['tmp_name'],$dest)){if($item['image']&&file_exists(UPLOAD_DIR.'items/'.$item['image']))unlink(UPLOAD_DIR.'items/'.$item['image']);$img=$fname;}
            else $errors['image']='Could not save image.';
        }
    }
    if(empty($errors)){
        $db->prepare('UPDATE items SET name=:n,category_id=:c,size=:s,rental_price=:r,sale_price=:sa,status=:status,image=:img,description=:desc WHERE id=:id')
           ->execute([':n'=>$old['name'],':c'=>$old['category_id'],':s'=>$old['size']?:null,':r'=>(float)$old['rental_price'],':sa'=>(float)$old['sale_price'],':status'=>$old['status'],':img'=>$img,':desc'=>$old['description']?:null,':id'=>$id]);
        log_activity('edit_item',"Item ID={$id} name={$old['name']}");
        redirect(APP_URL.'/admin/inventory/index.php?edited=1');
    }
    $old['image']=$img;
}
$active_nav='inventory';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Edit Item — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/inventory.css">
</head><body>
<?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content">
<header class="topbar"><h2>Edit Item</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body"><div class="form-card" style="max-width:960px">
<div class="form-card__header"><a href="<?= e(APP_URL) ?>/admin/inventory/index.php" class="btn btn--ghost btn--sm">← Back</a><h3>Editing: <?= e($item['name']) ?></h3></div>
<?php if(!empty($errors)): ?><div class="alert alert--error" style="margin:1rem 1.5rem 0">Please fix the errors below.</div><?php endif; ?>
<form method="POST" enctype="multipart/form-data" id="itemForm" novalidate style="padding:1.5rem">
<?php require __DIR__.'/partials/item_form_fields.php'; ?>
<div class="form-actions"><a href="<?= e(APP_URL) ?>/admin/inventory/index.php" class="btn btn--ghost">Cancel</a><button type="submit" class="btn btn--primary">Update Item</button></div>
</form></div></section></main>
<script>const APP_URL='<?= e(APP_URL) ?>';</script>
<script src="<?= e(APP_URL) ?>/assets/js/inventory_form.js"></script>
</body></html>
