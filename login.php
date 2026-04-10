<?php
declare(strict_types=1);
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/log_activity.php';
if (session_status()===PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) {
    $m=['admin'=>'/admin/index.php','staff'=>'/staff/index.php','customer'=>'/customer/index.php'];
    redirect(APP_URL.($m[$_SESSION['role']]??'/login.php'));
}
$error=''; $reason=get_param('reason');
if (is_post()) {
    $email=post('email'); $pw=post('password');
    if (!$email||!$pw) { $error='Email and password are required.'; }
    elseif (!filter_var($email,FILTER_VALIDATE_EMAIL)) { $error='Please enter a valid email address.'; }
    else {
        $s=get_db()->prepare('SELECT id,name,email,password,role,status FROM users WHERE email=:e LIMIT 1');
        $s->execute([':e'=>$email]); $u=$s->fetch();
        if (!$u||!password_verify($pw,$u['password'])) { $error='Invalid email or password.'; log_activity('login_failed',"Attempt: {$email}"); }
        elseif ($u['status']!=='active') { $error='Your account is '.$u['status'].'. Contact support.'; log_activity('login_blocked',"uid={$u['id']}"); }
        else {
            session_regenerate_id(true);
            $_SESSION['user_id']=(int)$u['id']; $_SESSION['name']=$u['name']; $_SESSION['role']=$u['role']; $_SESSION['last_active']=time();
            log_activity('login',"Role={$u['role']}",(int)$u['id']);
            $d=['admin'=>'/admin/index.php','staff'=>'/staff/index.php','customer'=>'/customer/index.php'];
            redirect(APP_URL.$d[$u['role']]);
        }
    }
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Login — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/auth.css">
</head><body class="auth-body">
<main class="auth-card">
    <div class="auth-brand"><h1><?= e(APP_NAME) ?></h1><p>Rental &amp; Sales Management</p></div>
    <?php if ($reason==='timeout'): ?><div class="alert alert--warning">Session expired. Please log in again.</div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert--error"><?= e($error) ?></div><?php endif; ?>
    <form id="loginForm" method="POST" novalidate>
        <div class="form-group"><label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= e(post('email')) ?>" autocomplete="email" required placeholder="you@example.com">
            <span class="field-error" id="emailError"></span></div>
        <div class="form-group"><label for="password">Password</label>
            <div class="input-wrap"><input type="password" id="password" name="password" autocomplete="current-password" required placeholder="••••••••">
            <button type="button" class="toggle-pw" data-target="password" aria-label="Show password">👁</button></div>
            <span class="field-error" id="passwordError"></span></div>
        <button type="submit" class="btn btn--primary btn--full">Sign In</button>
    </form>
    <p class="auth-switch" style="text-align:center;margin-top:1rem;font-size:.875rem">
        New customer? <a href="<?= e(APP_URL) ?>/register.php" style="color:var(--admin-accent);font-weight:600;text-decoration:none">Create an account →</a>
    </p>
</main>
<script src="<?= e(APP_URL) ?>/assets/js/auth.js"></script>
</body></html>
