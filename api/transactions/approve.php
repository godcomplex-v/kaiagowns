<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php'; require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php'; require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/log_activity.php'; require_once __DIR__.'/../../includes/txn_helpers.php';
session_guard('admin');
if($_SERVER['REQUEST_METHOD']!=='POST') json_response(['success'=>false],405);
$id=(int)($_POST['id']??0); if($id<=0) json_response(['success'=>false,'message'=>'Invalid request.'],400);
$db=get_db();
$txn=$db->prepare("SELECT t.*,i.stock,i.name AS item_name,u.name AS customer_name FROM transactions t JOIN items i ON i.id=t.item_id JOIN users u ON u.id=t.customer_id WHERE t.id=:id AND t.status='pending' LIMIT 1");
$txn->execute([':id'=>$id]); $txn=$txn->fetch();
if(!$txn) json_response(['success'=>false,'message'=>'Transaction not found or not pending.'],404);
if($txn['stock']<1) json_response(['success'=>false,'message'=>'Item is out of stock.'],409);
$db->beginTransaction();
try {
    if($txn['type']==='rent'){
        $db->prepare("UPDATE transactions SET status='active',staff_id=:s WHERE id=:id")->execute([':s'=>$_SESSION['user_id'],':id'=>$id]);
        if($txn['borrow_date']&&$txn['due_date']) $db->prepare('INSERT INTO availability (item_id,blocked_from,blocked_to,reason) VALUES (:i,:f,:t,:r)')->execute([':i'=>$txn['item_id'],':f'=>$txn['borrow_date'],':t'=>$txn['due_date'],':r'=>"Transaction #{$id}"]);
        notify_user((int)$txn['customer_id'],"Your rental request for \"{$txn['item_name']}\" has been approved!",'approved');
    } else {
        $db->prepare("UPDATE transactions SET status='completed',staff_id=:s WHERE id=:id")->execute([':s'=>$_SESSION['user_id'],':id'=>$id]);
        notify_user((int)$txn['customer_id'],"Your purchase of \"{$txn['item_name']}\" has been confirmed!",'approved');
    }
    decrement_stock((int)$txn['item_id'],(int)$_SESSION['user_id'],"Approved transaction #{$id} ({$txn['type']})");
    $db->commit();
} catch(Exception $e){$db->rollBack();error_log($e->getMessage());json_response(['success'=>false,'message'=>'Database error.'],500);}
log_activity('approve_txn',"TXN #{$id} type={$txn['type']} item={$txn['item_name']}");
json_response(['success'=>true,'type'=>$txn['type']]);
