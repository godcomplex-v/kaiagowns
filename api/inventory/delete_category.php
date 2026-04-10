<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php'; require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php'; require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/log_activity.php';
session_guard('admin');
if($_SERVER['REQUEST_METHOD']!=='POST') json_response(['success'=>false],405);
$id=(int)($_POST['id']??0); if($id<=0) json_response(['success'=>false,'message'=>'Invalid ID.'],400);
$used=get_db()->prepare('SELECT COUNT(*) FROM items WHERE category_id=:id'); $used->execute([':id'=>$id]);
if((int)$used->fetchColumn()>0) json_response(['success'=>false,'message'=>'Cannot delete: items are using this category.'],409);
get_db()->prepare('DELETE FROM categories WHERE id=:id')->execute([':id'=>$id]);
log_activity('delete_category',"ID={$id}"); json_response(['success'=>true]);
