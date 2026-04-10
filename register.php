<?php
declare(strict_types=1);
require_once __DIR__.'/includes/config.php';
require_once __DIR__.'/includes/db.php';
require_once __DIR__.'/includes/functions.php';
require_once __DIR__.'/includes/log_activity.php';
if (session_status()===PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) { $m=['admin'=>'/admin/index.php','staff'=>'/staff/index.php','customer'=>'/customer/index.php']; redirect(APP_URL.($m[$_SESSION['role']]??'/login.php')); }
$errors=[]; $old=[];
if (is_post()) {
    $old=['name'=>post('name'),'email'=>post('email'),'phone'=>post('phone'),'address'=>post('address')];
    $pw=post('password'); $conf=post('password_confirm');
    if (!$old['name']) $errors['name']='Full name is required.';
    elseif (mb_strlen($old['name'])<2) $errors['name']='Name must be at least 2 characters.';
    if (!$old['email']) $errors['email']='Email is required.';
    elseif (!filter_var($old['email'],FILTER_VALIDATE_EMAIL)) $errors['email']='Enter a valid email.';
    if (!$pw) $errors['password']='Password is required.';
    elseif (strlen($pw)<8) $errors['password']='At least 8 characters required.';
    elseif (!preg_match('/[A-Za-z]/',$pw)||!preg_match('/[0-9]/',$pw)) $errors['password']='Must contain letters and numbers.';
    if ($pw&&!$conf) $errors['confirm']='Please confirm your password.';
    elseif ($pw&&$pw!==$conf) $errors['confirm']='Passwords do not match.';
    if (empty($errors['email'])) { $d=get_db()->prepare('SELECT id FROM users WHERE email=:e LIMIT 1'); $d->execute([':e'=>$old['email']]); if($d->fetch()) $errors['email']='Email already registered.'; }
    if (empty($errors)) {
        $hash=password_hash($pw,PASSWORD_BCRYPT,['cost'=>12]);
        get_db()->prepare('INSERT INTO users (name,email,password,phone,address,role,status) VALUES (:n,:e,:p,:ph,:a,:r,:s)')
            ->execute([':n'=>$old['name'],':e'=>$old['email'],':p'=>$hash,':ph'=>$old['phone']?:null,':a'=>$old['address']?:null,':r'=>'customer',':s'=>'active']);
        $nid=(int)get_db()->lastInsertId();
        log_activity('register',"New customer uid={$nid}",$nid);
        session_regenerate_id(true);
        $_SESSION['user_id']=$nid; $_SESSION['name']=$old['name']; $_SESSION['role']='customer'; $_SESSION['last_active']=time();
        redirect(APP_URL.'/customer/index.php?welcome=1');
    }
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Create Account — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/auth.css">
</head><body class="auth-body auth-body--register">
<main class="auth-card auth-card--wide">
    <div class="auth-brand"><h1><?= e(APP_NAME) ?></h1><p>Create your account</p></div>
    <?php if (!empty($errors)): ?><div class="alert alert--error">Please fix the errors below.</div><?php endif; ?>
    <form id="registerForm" method="POST" novalidate>
        <div class="register-grid">
            <div class="register-col">
                <div class="form-group"><label for="name">Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" value="<?= e($old['name']??'') ?>" maxlength="150" required placeholder="Your full name">
                    <span class="field-error" id="nameError"><?= e($errors['name']??'') ?></span></div>
                <div class="form-group"><label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" value="<?= e($old['email']??'') ?>" maxlength="150" required placeholder="you@example.com">
                    <span class="field-error" id="emailError"><?= e($errors['email']??'') ?></span></div>
                <div class="form-group"><label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?= e($old['phone']??'') ?>" maxlength="30" placeholder="+63 9XX XXX XXXX"></div>
                <div class="form-group"><label for="address">Address</label>
                    <textarea id="address" name="address" rows="3" maxlength="500" placeholder="Street, City, Province"><?= e($old['address']??'') ?></textarea></div>
            </div>
            <div class="register-col">
                <div class="form-group"><label for="password">Password <span class="required">*</span></label>
                    <div class="input-wrap"><input type="password" id="password" name="password" maxlength="100" required placeholder="At least 8 characters">
                    <button type="button" class="toggle-pw" data-target="password" aria-label="Show">👁</button></div>
                    <span class="field-error" id="passwordError"><?= e($errors['password']??'') ?></span>
                    <div class="pw-strength" id="pwStrength" hidden><div class="pw-strength-bar"><div class="pw-strength-fill" id="pwFill"></div></div><span class="pw-strength-label" id="pwLabel"></span></div></div>
                <div class="form-group"><label for="password_confirm">Confirm Password <span class="required">*</span></label>
                    <div class="input-wrap"><input type="password" id="password_confirm" name="password_confirm" maxlength="100" required placeholder="Repeat password">
                    <button type="button" class="toggle-pw" data-target="password_confirm" aria-label="Show">👁</button></div>
                    <span class="field-error" id="confirmError"><?= e($errors['confirm']??'') ?></span></div>
                <ul class="pw-rules" id="pwRules">
                    <li id="rule-len" class="pw-rule">At least 8 characters</li>
                    <li id="rule-let" class="pw-rule">Contains a letter</li>
                    <li id="rule-num" class="pw-rule">Contains a number</li>
                    <li id="rule-match" class="pw-rule">Passwords match</li>
                </ul>
                <div class="auth-terms">By creating an account you agree to our <a href="#">Terms of Service</a>.</div>
            </div>
        </div>
        <div class="register-actions">
            <button type="submit" class="btn btn--primary btn--full">Create Account</button>
            <p class="auth-switch">Already have an account? <a href="<?= e(APP_URL) ?>/login.php">Sign in →</a></p>
        </div>
    </form>
</main>
<script src="<?= e(APP_URL) ?>/assets/js/auth.js"></script>
</body></html>
