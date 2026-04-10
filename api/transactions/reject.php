<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php'; require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php'; require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/log_activity.php'; require_once __DIR__.'/../../includes/txn_helpers.php';
session_guard('admin');
if($_SERVER['REQUEST_METHOD']!=='POST') json_response(['success'=>false],405);
$id=(int)($_POST['id']??0); $reason=trim($_POST['reason']??'');
if($id<=0) json_response(['success'=>false,'message'=>'Invalid request.'],400);
if(!$reason) json_response(['success'=>false,'errors'=>['reason'=>'Rejection reason is required.']],422);
$db=get_db(); $txn=$db->prepare("SELECT t.customer_id,i.name AS item_name FROM transactions t JOIN items i ON i.id=t.item_id WHERE t.id=:id AND t.status='pending' LIMIT 1");
$txn->execute([':id'=>$id]); $txn=$txn->fetch();
if(!$txn) json_response(['success'=>false,'message'=>'Transaction not found or not pending.'],404);
$db->prepare("UPDATE transactions SET status='rejected',notes=:r,staff_id=:s WHERE id=:id")->execute([':r'=>$reason,':s'=>$_SESSION['user_id'],':id'=>$id]);
notify_user((int)$txn['customer_id'],"Your request for \"{$txn['item_name']}\" was rejected. Reason: {$reason}",'rejected');
log_activity('reject_txn',"TXN #{$id} reason={$reason}");
json_response(['success'=>true]);
