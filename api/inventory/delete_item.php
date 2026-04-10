<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php'; require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php'; require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/log_activity.php';
session_guard('admin');
if($_SERVER['REQUEST_METHOD']!=='POST') json_response(['success'=>false],405);
$id=(int)($_POST['id']??0); if($id<=0) json_response(['success'=>false,'message'=>'Invalid ID.'],400);
$item=get_db()->prepare('SELECT id,name,image FROM items WHERE id=:id LIMIT 1'); $item->execute([':id'=>$id]); $item=$item->fetch();
if(!$item) json_response(['success'=>false,'message'=>'Item not found.'],404);
$active=get_db()->prepare("SELECT COUNT(*) FROM transactions WHERE item_id=:id AND status IN ('pending','approved','active','overdue')"); $active->execute([':id'=>$id]);
if((int)$active->fetchColumn()>0) json_response(['success'=>false,'message'=>'Cannot remove: item has active transactions.'],409);
get_db()->prepare('DELETE FROM items WHERE id=:id')->execute([':id'=>$id]);
if($item['image']&&file_exists(UPLOAD_DIR.'items/'.$item['image'])) unlink(UPLOAD_DIR.'items/'.$item['image']);
log_activity('delete_item',"ID={$id} name={$item['name']}"); json_response(['success'=>true]);
