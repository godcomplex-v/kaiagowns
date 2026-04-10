<?php
declare(strict_types=1);
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/log_activity.php';
if (session_status()===PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) log_activity('logout','User logged out');
session_unset(); session_destroy();
if (ini_get('session.use_cookies')) { $p=session_get_cookie_params(); setcookie(session_name(),'',time()-3600,$p['path'],$p['domain'],$p['secure'],$p['httponly']); }
redirect(APP_URL.'/login.php');
