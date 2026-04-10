// api/staff/create_request.php  — handled by staff/transactions/create.php (full-page POST)
// No separate AJAX endpoint needed for create — it's a standard form submission.

// api/staff/update_item_status.php

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_guard.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/log_activity.php';

session_guard(['admin','staff']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['success'=>false], 405);

$item_id   = (int)($_POST['item_id'] ?? 0);
$new_status = trim($_POST['status'] ?? '');

$allowed = ['available','reserved','damaged'];  // staff cannot retire items
if ($item_id <= 0 || !in_array($new_status, $allowed, true)) {
    json_response(['success'=>false,'message'=>'Invalid request.'], 400);
}

$db   = get_db();
$item = $db->prepare('SELECT id, name, status FROM items WHERE id=:id LIMIT 1');
$item->execute([':id' => $item_id]);
$item = $item->fetch();

if (!$item) json_response(['success'=>false,'message'=>'Item not found.'], 404);

$db->prepare('UPDATE items SET status=:s WHERE id=:id')
   ->execute([':s' => $new_status, ':id' => $item_id]);

log_activity('update_item_status',
    "Item ID={$item_id} ({$item['name']}) {$item['status']} → {$new_status}");

json_response(['success'=>true, 'new_status'=>$new_status]);