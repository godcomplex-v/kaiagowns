<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/log_activity.php';
session_guard('admin');
$db=get_db(); $settings=get_settings(); $pw_error=''; $pw_ok=false;
if(is_post()&&post('action')==='change_password'){
    $cur=post('current_password'); $nw=post('new_password'); $conf=post('confirm_password');
    $user=$db->prepare('SELECT id,password FROM users WHERE id=:id LIMIT 1'); $user->execute([':id'=>$_SESSION['user_id']]); $user=$user->fetch();
    if(!password_verify($cur,$user['password'])) $pw_error='Current password is incorrect.';
    elseif(strlen($nw)<8) $pw_error='New password must be at least 8 characters.';
    elseif($nw!==$conf) $pw_error='New passwords do not match.';
    else {
        $db->prepare('UPDATE users SET password=:pw WHERE id=:id')->execute([':pw'=>password_hash($nw,PASSWORD_BCRYPT,['cost'=>12]),':id'=>$_SESSION['user_id']]);
        log_activity('change_password','Admin changed own password'); $pw_ok=true;
    }
}
$active_nav='settings';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Settings — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/logs.css">
</head><body><?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content"><header class="topbar"><h2>Settings</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body settings-body">

<div class="settings-card">
    <div class="settings-card__header"><h3>Application Settings</h3><p>Core configuration for <?= e(APP_NAME) ?>.</p></div>
    <div class="settings-card__body">
        <div class="settings-row">
            <div class="settings-meta"><label class="settings-label">Application Name</label><span class="settings-hint">Displayed in the browser title and sidebar.</span></div>
            <div class="settings-control"><input type="text" class="settings-input" data-key="app_name" value="<?= e($settings['app_name']??'') ?>" maxlength="100"><button class="btn btn--sm btn--primary save-setting" data-key="app_name">Save</button></div>
        </div>
        <div class="settings-row">
            <div class="settings-meta"><label class="settings-label">Contact Email</label><span class="settings-hint">Used in customer-facing notifications.</span></div>
            <div class="settings-control"><input type="email" class="settings-input" data-key="contact_email" value="<?= e($settings['contact_email']??'') ?>" maxlength="150"><button class="btn btn--sm btn--primary save-setting" data-key="contact_email">Save</button></div>
        </div>
        <div class="settings-row">
            <div class="settings-meta"><label class="settings-label">Rows Per Page</label><span class="settings-hint">Default pagination size.</span></div>
            <div class="settings-control"><input type="number" class="settings-input settings-input--sm" data-key="items_per_page" value="<?= e($settings['items_per_page']??'15') ?>" min="5" max="100" step="5"><button class="btn btn--sm btn--primary save-setting" data-key="items_per_page">Save</button></div>
        </div>
    </div>
</div>

<div class="settings-card">
    <div class="settings-card__header"><h3>Rental Settings</h3><p>Fees and defaults for rental transactions.</p></div>
    <div class="settings-card__body">
        <div class="settings-row">
            <div class="settings-meta"><label class="settings-label">Penalty Per Day (₱)</label><span class="settings-hint">Currently: <strong><?= e($settings['penalty_per_day']??'50.00') ?></strong></span></div>
            <div class="settings-control"><input type="number" class="settings-input settings-input--sm" data-key="penalty_per_day" value="<?= e($settings['penalty_per_day']??'50.00') ?>" min="0" step="0.50"><button class="btn btn--sm btn--primary save-setting" data-key="penalty_per_day">Save</button></div>
        </div>
        <div class="settings-row">
            <div class="settings-meta"><label class="settings-label">Default Rental Period (days)</label><span class="settings-hint">Pre-filled due date offset when creating a rental.</span></div>
            <div class="settings-control"><input type="number" class="settings-input settings-input--sm" data-key="rental_days_default" value="<?= e($settings['rental_days_default']??'7') ?>" min="1" max="365"><button class="btn btn--sm btn--primary save-setting" data-key="rental_days_default">Save</button></div>
        </div>
    </div>
</div>

<div class="settings-card">
    <div class="settings-card__header"><h3>Change Password</h3><p>Update the password for your admin account.</p></div>
    <div class="settings-card__body">
        <?php if($pw_ok): ?><div class="alert alert--success">Password changed successfully.</div><?php endif; ?>
        <?php if($pw_error): ?><div class="alert alert--error"><?= e($pw_error) ?></div><?php endif; ?>
        <form method="POST" id="pwForm" novalidate>
            <input type="hidden" name="action" value="change_password">
            <div class="settings-pw-grid">
                <div class="form-group"><label for="current_password">Current Password</label><input type="password" id="current_password" name="current_password" required autocomplete="current-password"><span class="field-error" id="curPwErr"></span></div>
                <div class="form-group"><label for="new_password">New Password</label><input type="password" id="new_password" name="new_password" required autocomplete="new-password" minlength="8"><span class="field-error" id="newPwErr"></span></div>
                <div class="form-group"><label for="confirm_password">Confirm New Password</label><input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password"><span class="field-error" id="confPwErr"></span></div>
            </div>
            <button type="submit" class="btn btn--primary">Update Password</button>
        </form>
    </div>
</div>

</section></main>
<div id="toast" class="toast" role="alert" aria-live="polite"></div>
<script>const APP_URL='<?= e(APP_URL) ?>';</script>
<script src="<?= e(APP_URL) ?>/assets/js/settings.js"></script>
</body></html>
