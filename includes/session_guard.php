<?php
declare(strict_types=1);
function session_guard(string|array $allowed_roles): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $allowed = (array)$allowed_roles;
    if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) { header('Location: '.APP_URL.'/login.php'); exit; }
    if (!in_array($_SESSION['role'], $allowed, true)) {
        $d = ['admin'=>'/admin/index.php','staff'=>'/staff/index.php','customer'=>'/customer/index.php'];
        header('Location: '.APP_URL.($d[$_SESSION['role']]??'/login.php')); exit;
    }
    if (!empty($_SESSION['last_active']) && (time()-$_SESSION['last_active'])>SESSION_LIFETIME) {
        session_unset(); session_destroy(); header('Location: '.APP_URL.'/login.php?reason=timeout'); exit;
    }
    $_SESSION['last_active'] = time();
}
