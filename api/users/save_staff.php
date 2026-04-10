<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/log_activity.php';
session_guard('admin');
if($_SERVER['REQUEST_METHOD']!=='POST') json_response(['success'=>false],405);
$id=(int)trim($_POST['id']??'0'); $name=trim($_POST['name']??''); $email=trim($_POST['email']??'');
$phone=trim($_POST['phone']??''); $status=trim($_POST['status']??'active');
$pw=trim($_POST['password']??''); $conf=trim($_POST['password_confirm']??''); $is_edit=$id>0;
$errors=[];
if(!$name) $errors['name']='Full name is required.';
if(!$email) $errors['email']='Email is required.';
elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)) $errors['email']='Enter a valid email.';
if(!$is_edit&&!$pw) $errors['password']='Password is required.';
elseif($pw&&strlen($pw)<8) $errors['password']='Password must be at least 8 characters.';
elseif($pw&&$pw!==$conf) $errors['confirm']='Passwords do not match.';
if(!empty($errors)) json_response(['success'=>false,'errors'=>$errors],422);
$db=get_db();
$dup=$db->prepare('SELECT id FROM users WHERE email=:e AND id!=:id LIMIT 1');
$dup->execute([':e'=>$email,':id'=>$id]);
if($dup->fetch()) json_response(['success'=>false,'errors'=>['email'=>'This email is already in use.']],422);
if($is_edit){
    $chk=$db->prepare("SELECT id FROM users WHERE id=:id AND role='staff' LIMIT 1"); $chk->execute([':id'=>$id]);
    if(!$chk->fetch()) json_response(['success'=>false,'message'=>'Staff not found.'],404);
    if($pw){$hash=password_hash($pw,PASSWORD_BCRYPT,['cost'=>12]);$db->prepare('UPDATE users SET name=:n,email=:e,phone=:p,status=:s,password=:pw WHERE id=:id')->execute([':n'=>$name,':e'=>$email,':p'=>$phone?:null,':s'=>$status,':pw'=>$hash,':id'=>$id]);}
    else{$db->prepare('UPDATE users SET name=:n,email=:e,phone=:p,status=:s WHERE id=:id')->execute([':n'=>$name,':e'=>$email,':p'=>$phone?:null,':s'=>$status,':id'=>$id]);}
    log_activity('edit_staff',"Staff ID={$id}"); $action_done='updated';
}else{
    $hash=password_hash($pw,PASSWORD_BCRYPT,['cost'=>12]);
    $db->prepare('INSERT INTO users (name,email,phone,password,role,status) VALUES (:n,:e,:p,:pw,:r,:s)')->execute([':n'=>$name,':e'=>$email,':p'=>$phone?:null,':pw'=>$hash,':r'=>'staff',':s'=>$status]);
    $id=(int)$db->lastInsertId(); log_activity('add_staff',"New staff ID={$id}"); $action_done='created';
}
$rs=get_db()->prepare('SELECT id,name,email,phone,status,created_at FROM users WHERE id=:id'); $rs->execute([':id'=>$id]); $s=$rs->fetch(); $i=null;
ob_start(); require __DIR__.'/../../admin/users/partials/staff_row.php'; $html=ob_get_clean();
json_response(['success'=>true,'message'=>"Staff member {$action_done} successfully.",'html'=>$html,'id'=>$id,'is_edit'=>$is_edit]);
