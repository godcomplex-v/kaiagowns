<?php
declare(strict_types=1);
require_once __DIR__.'/db.php';
function log_activity(string $action, string $details='', ?int $user_id=null): void {
    if (session_status()===PHP_SESSION_NONE) session_start();
    $uid = $user_id ?? ($_SESSION['user_id']??null);
    $ip  = $_SERVER['HTTP_X_FORWARDED_FOR']??$_SERVER['REMOTE_ADDR']??null;
    get_db()->prepare('INSERT INTO logs (user_id,action,details,ip) VALUES (:uid,:action,:details,:ip)')
        ->execute([':uid'=>$uid,':action'=>$action,':details'=>$details?:null,':ip'=>$ip]);
}
