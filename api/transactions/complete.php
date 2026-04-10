<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php'; require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php'; require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/log_activity.php'; require_once __DIR__.'/../../includes/txn_helpers.php';
session_guard('admin');
if($_SERVER['REQUEST_METHOD']!=='POST') json_response(['success'=>false],405);
$id=(int)($_POST['id']??0); $notes=trim($_POST['notes']??'');
if($id<=0) json_response(['success'=>false,'message'=>'Invalid request.'],400);
$db=get_db(); $txn=$db->prepare("SELECT t.*,i.name AS item_name,u.name AS customer_name FROM transactions t JOIN items i ON i.id=t.item_id JOIN users u ON u.id=t.customer_id WHERE t.id=:id AND t.status='returned' LIMIT 1");
$txn->execute([':id'=>$id]); $txn=$txn->fetch();
if(!$txn) json_response(['success'=>false,'message'=>'Transaction not in returned status.'],404);
$db->beginTransaction();
try {
    $upd_notes=$notes?(($txn['notes']?$txn['notes'].' | ':'').$notes):$txn['notes'];
    $db->prepare("UPDATE transactions SET status='completed',notes=:n,staff_id=:s WHERE id=:id")->execute([':n'=>$upd_notes,':s'=>$_SESSION['user_id'],':id'=>$id]);
    increment_stock((int)$txn['item_id'],(int)$_SESSION['user_id'],"Return confirmed for transaction #{$id}");
    notify_user((int)$txn['customer_id'],"Your rental of \"{$txn['item_name']}\" is now complete. Thank you!",'completed');
    $db->commit();
} catch(Exception $e){$db->rollBack();error_log($e->getMessage());json_response(['success'=>false,'message'=>'Database error.'],500);}
log_activity('complete_txn',"TXN #{$id} item={$txn['item_name']}");
json_response(['success'=>true]);
