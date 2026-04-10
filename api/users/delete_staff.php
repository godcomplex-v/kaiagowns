<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/log_activity.php';
session_guard('admin');
if($_SERVER['REQUEST_METHOD']!=='POST') json_response(['success'=>false],405);
$id=(int)($_POST['id']??0); if($id<=0) json_response(['success'=>false,'message'=>'Invalid ID.'],400);
$s=get_db()->prepare("SELECT id,name FROM users WHERE id=:id AND role='staff' LIMIT 1"); $s->execute([':id'=>$id]); $user=$s->fetch();
if(!$user) json_response(['success'=>false,'message'=>'Staff not found.'],404);
get_db()->prepare('DELETE FROM users WHERE id=:id')->execute([':id'=>$id]);
log_activity('delete_staff',"Removed staff ID={$id} name={$user['name']}");
json_response(['success'=>true,'message'=>"Staff member \"{$user['name']}\" removed."]);
