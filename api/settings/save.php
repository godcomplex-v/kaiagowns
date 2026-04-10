<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/log_activity.php';
session_guard('admin');
if($_SERVER['REQUEST_METHOD']!=='POST') json_response(['success'=>false],405);
$key=trim($_POST['key']??''); $value=trim($_POST['value']??'');
$allowed=['app_name','penalty_per_day','items_per_page','contact_email','rental_days_default'];
if(!in_array($key,$allowed,true)) json_response(['success'=>false,'message'=>'Unknown setting key.'],400);
$error=match($key){
    'app_name'=>$value===''?'App name cannot be empty.':null,
    'contact_email'=>!filter_var($value,FILTER_VALIDATE_EMAIL)?'Enter a valid email address.':null,
    'penalty_per_day'=>(!is_numeric($value)||(float)$value<0)?'Penalty must be 0 or more.':null,
    'items_per_page'=>(!ctype_digit($value)||(int)$value<5||(int)$value>100)?'Must be between 5 and 100.':null,
    'rental_days_default'=>(!ctype_digit($value)||(int)$value<1||(int)$value>365)?'Must be between 1 and 365.':null,
    default=>null,
};
if($error) json_response(['success'=>false,'message'=>$error],422);
get_db()->prepare('UPDATE settings SET value=:v WHERE key_name=:k')->execute([':v'=>$value,':k'=>$key]);
log_activity('update_setting',"key={$key} value={$value}");
json_response(['success'=>true,'message'=>'Setting saved.']);
