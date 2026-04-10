<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php'; require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php'; require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/log_activity.php'; require_once __DIR__.'/../../includes/txn_helpers.php';
session_guard(['admin','staff']);
if($_SERVER['REQUEST_METHOD']!=='POST') json_response(['success'=>false],405);
$id=(int)($_POST['id']??0); $notes=trim($_POST['notes']??'');
if($id<=0) json_response(['success'=>false,'message'=>'Invalid request.'],400);
$db=get_db(); $txn=$db->prepare("SELECT t.*,i.name AS item_name,u.name AS customer_name FROM transactions t JOIN items i ON i.id=t.item_id JOIN users u ON u.id=t.customer_id WHERE t.id=:id AND t.status IN ('active','overdue') LIMIT 1");
$txn->execute([':id'=>$id]); $txn=$txn->fetch();
if(!$txn) json_response(['success'=>false,'message'=>'Transaction not found.'],404);
$today=date('Y-m-d'); $penalty=calc_penalty($txn['due_date'],$today);
$db->beginTransaction();
try {
    $db->prepare("UPDATE transactions SET status='returned',return_date=:rd,penalty_fee=:p,notes=:n,staff_id=:s WHERE id=:id")->execute([':rd'=>$today,':p'=>$penalty,':n'=>$notes?:null,':s'=>$_SESSION['user_id'],':id'=>$id]);
    notify_user((int)$txn['customer_id'],"Your return of \"{$txn['item_name']}\" has been received.".($penalty>0?" Penalty: ₱".number_format($penalty,2):" No penalty."),'return_received');
    $db->commit();
} catch(Exception $e){$db->rollBack();error_log($e->getMessage());json_response(['success'=>false,'message'=>'Database error.'],500);}
log_activity('process_return',"TXN #{$id} penalty={$penalty}");
json_response(['success'=>true,'penalty'=>$penalty]);
