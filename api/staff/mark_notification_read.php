// api/staff/mark_notification_read.php

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_guard.php';
require_once __DIR__ . '/../../includes/functions.php';

session_guard(['admin','staff','customer']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['success'=>false], 405);

$uid    = (int)$_SESSION['user_id'];
$id     = (int)($_POST['id']  ?? 0);
$all    = !empty($_POST['all']);

$db = get_db();

if ($all) {
    $db->prepare('UPDATE notifications SET is_read=1 WHERE user_id=:uid')
       ->execute([':uid' => $uid]);
} elseif ($id > 0) {
    // Scoped to this user — prevents marking other users' notifications
    $db->prepare(
        'UPDATE notifications SET is_read=1 WHERE id=:id AND user_id=:uid'
    )->execute([':id' => $id, ':uid' => $uid]);
} else {
    json_response(['success'=>false,'message'=>'Invalid request.'], 400);
}

json_response(['success'=>true]);