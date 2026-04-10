<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_guard.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/log_activity.php';

session_guard('customer');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['success'=>false], 405);

$uid = (int)$_SESSION['user_id'];
$id  = (int)($_POST['id'] ?? 0);
if ($id <= 0) json_response(['success'=>false,'message'=>'Invalid request.'], 400);

$db  = get_db();
$txn = $db->prepare(
    "SELECT id FROM transactions
     WHERE id=:id AND customer_id=:uid AND status='pending' LIMIT 1"
);
$txn->execute([':id'=>$id, ':uid'=>$uid]);
if (!$txn->fetch()) {
    json_response(['success'=>false,'message'=>'Request not found or already processed.'], 404);
}

$db->prepare(
    "UPDATE transactions SET status='cancelled' WHERE id=:id"
)->execute([':id' => $id]);

log_activity('cancel_request', "TXN #{$id} cancelled by customer uid={$uid}");
json_response(['success'=>true]);