<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php'; require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php'; require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/log_activity.php';
session_guard(['admin','staff']);
if($_SERVER['REQUEST_METHOD']!=='POST') json_response(['success'=>false],405);
$item_id=(int)($_POST['item_id']??0); $change=(int)($_POST['change']??0); $reason=trim($_POST['reason']??'');
if($item_id<=0) json_response(['success'=>false,'errors'=>['change'=>'Invalid item.']],400);
if($change===0) json_response(['success'=>false,'errors'=>['change'=>'Adjustment cannot be zero.']],422);
if(!$reason) json_response(['success'=>false,'errors'=>['reason'=>'Reason is required.']],422);
$db=get_db(); $item=$db->prepare('SELECT id,stock FROM items WHERE id=:id LIMIT 1'); $item->execute([':id'=>$item_id]); $item=$item->fetch();
if(!$item) json_response(['success'=>false,'message'=>'Item not found.'],404);
$new=$item['stock']+$change;
if($new<0) json_response(['success'=>false,'errors'=>['change'=>"Cannot reduce below 0. Current stock: {$item['stock']}."]],422);
$db->beginTransaction();
try {
    $db->prepare('UPDATE items SET stock=:s WHERE id=:id')->execute([':s'=>$new,':id'=>$item_id]);
    $db->prepare('INSERT INTO stock_history (item_id,`change`,reason,staff_id) VALUES (:i,:c,:r,:s)')->execute([':i'=>$item_id,':c'=>$change,':r'=>$reason,':s'=>$_SESSION['user_id']]);
    $db->commit();
} catch(Exception $e){$db->rollBack();error_log($e->getMessage());json_response(['success'=>false,'message'=>'Database error.'],500);}
log_activity('adjust_stock',"Item ID={$item_id} change={$change} new={$new}");
json_response(['success'=>true,'new_stock'=>$new]);
