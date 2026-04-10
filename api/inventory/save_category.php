<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php'; require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php'; require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/log_activity.php';
session_guard('admin');
if($_SERVER['REQUEST_METHOD']!=='POST') json_response(['success'=>false],405);
$id=(int)trim($_POST['id']??'0'); $name=trim($_POST['name']??'');
if(!$name) json_response(['success'=>false,'errors'=>['name'=>'Category name is required.']],422);
$db=get_db(); $dup=$db->prepare('SELECT id FROM categories WHERE name=:n AND id!=:id LIMIT 1'); $dup->execute([':n'=>$name,':id'=>$id]);
if($dup->fetch()) json_response(['success'=>false,'errors'=>['name'=>'A category with this name already exists.']],422);
if($id>0){$db->prepare('UPDATE categories SET name=:n WHERE id=:id')->execute([':n'=>$name,':id'=>$id]);log_activity('edit_category',"ID={$id}");$action='updated';}
else{$db->prepare('INSERT INTO categories (name) VALUES (:n)')->execute([':n'=>$name]);$id=(int)$db->lastInsertId();log_activity('add_category',"ID={$id}");$action='created';}
json_response(['success'=>true,'id'=>$id,'name'=>$name,'action'=>$action]);
