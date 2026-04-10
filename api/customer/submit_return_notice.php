<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_guard.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/log_activity.php';
require_once __DIR__ . '/../../includes/txn_helpers.php';

session_guard('customer');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['success'=>false], 405);

$uid    = (int)$_SESSION['user_id'];
$txn_id = (int)($_POST['transaction_id'] ?? 0);
if ($txn_id <= 0) json_response(['success'=>false,'message'=>'Invalid request.'], 400);

$db  = get_db();

// Verify ownership + active status
$txn = $db->prepare(
    "SELECT t.id, i.name AS item_name FROM transactions t
     JOIN items i ON i.id=t.item_id
     WHERE t.id=:id AND t.customer_id=:uid
       AND t.type='rent' AND t.status IN ('active','overdue') LIMIT 1"
);
$txn->execute([':id'=>$txn_id, ':uid'=>$uid]);
$txn = $txn->fetch();
if (!$txn) json_response(['success'=>false,'message'=>'Transaction not found.'], 404);

// Prevent duplicate notices
$dup = $db->prepare(
    "SELECT id FROM return_notices
     WHERE transaction_id=:id AND pickup_status != 'confirmed' LIMIT 1"
);
$dup->execute([':id' => $txn_id]);
if ($dup->fetch()) {
    json_response(['success'=>false,'message'=>'A return notice already exists for this item.'], 409);
}

$db->prepare(
    'INSERT INTO return_notices (transaction_id, customer_id, pickup_status)
     VALUES (:txn, :uid, :status)'
)->execute([':txn'=>$txn_id, ':uid'=>$uid, ':status'=>'pending_pickup']);

notify_user($uid,
    "Your return notice for \"{$txn['item_name']}\" has been sent to staff.",
    'info'
);

log_activity('submit_return_notice',
    "Customer uid={$uid} txn_id={$txn_id} item={$txn['item_name']}");

json_response(['success'=>true]);