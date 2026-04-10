<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php'; require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php'; require_once __DIR__.'/../../includes/functions.php';
session_guard(['admin','staff']);
$id=(int)get_param('item_id'); if($id<=0) json_response(['success'=>false,'message'=>'Invalid item.'],400);
$rows=get_db()->prepare("SELECT sh.`change`,sh.reason,sh.created_at,u.name AS staff_name FROM stock_history sh LEFT JOIN users u ON u.id=sh.staff_id WHERE sh.item_id=:id ORDER BY sh.created_at DESC LIMIT 30");
$rows->execute([':id'=>$id]); json_response(['success'=>true,'history'=>$rows->fetchAll()]);
