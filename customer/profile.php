<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session_guard.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/log_activity.php';

session_guard('customer');

$db      = get_db();
$uid     = (int)$_SESSION['user_id'];
$success = '';
$errors  = [];

$user = $db->prepare('SELECT * FROM users WHERE id=:id LIMIT 1');
$user->execute([':id' => $uid]);
$user = $user->fetch();

if (is_post()) {
    $action = post('action');

    if ($action === 'update_profile') {
        $name    = post('name');
        $phone   = post('phone');
        $address = post('address');

        if ($name === '') $errors['name'] = 'Name is required.';

        // Avatar upload
        $photo = $user['profile_photo'];
        if (!empty($_FILES['profile_photo']['name'])) {
            $f       = $_FILES['profile_photo'];
            $allowed = ['image/jpeg','image/png','image/webp'];
            $ext_map = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if ($f['error'] !== UPLOAD_ERR_OK) {
                $errors['photo'] = 'Upload failed.';
            } elseif ($f['size'] > 1*1024*1024) {
                $errors['photo'] = 'Photo must be under 1 MB.';
            } elseif (!in_array($f['type'], $allowed, true)) {
                $errors['photo'] = 'Only JPG, PNG, WebP allowed.';
            } else {
                $ext   = $ext_map[$f['type']];
                $fname = 'avatar_' . $uid . '_' . time() . '.' . $ext;
                $dest  = UPLOAD_DIR . 'avatars/' . $fname;
                if (!is_dir(UPLOAD_DIR . 'avatars/')) mkdir(UPLOAD_DIR . 'avatars/', 0755, true);
                if (move_uploaded_file($f['tmp_name'], $dest)) {
                    if ($photo && file_exists(UPLOAD_DIR . 'avatars/' . $photo)) {
                        unlink(UPLOAD_DIR . 'avatars/' . $photo);
                    }
                    $photo = $fname;
                } else {
                    $errors['photo'] = 'Could not save photo.';
                }
            }
        }

        if (empty($errors)) {
            $db->prepare(
                'UPDATE users SET name=:n, phone=:p, address=:a, profile_photo=:ph WHERE id=:id'
            )->execute([':n'=>$name,':p'=>$phone?:null,':a'=>$address?:null,':ph'=>$photo,':id'=>$uid]);
            $_SESSION['name'] = $name;
            log_activity('update_profile', "Customer uid={$uid}");
            $success = 'Profile updated.';
            $user['name']    = $name;
            $user['phone']   = $phone;
            $user['address'] = $address;
            $user['profile_photo'] = $photo;
        }
    }

    if ($action === 'change_password') {
        $cur  = post('current_password');
        $nw   = post('new_password');
        $conf = post('confirm_password');

        if (!password_verify($cur, $user['password'])) {
            $errors['pw_cur'] = 'Current password is incorrect.';
        } elseif (strlen($nw) < 8) {
            $errors['pw_new'] = 'Must be at least 8 characters.';
        } elseif ($nw !== $conf) {
            $errors['pw_conf'] = 'Passwords do not match.';
        } else {
            $db->prepare('UPDATE users SET password=:pw WHERE id=:id')
               ->execute([':pw' => password_hash($nw, PASSWORD_BCRYPT, ['cost'=>12]),
                          ':id' => $uid]);
            log_activity('change_password', "Customer uid={$uid}");
            $success = 'Password changed successfully.';
        }
    }
}

$active_nav = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/customer.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/logs.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/customer_dash.css">
</head>
<body>
<?php require __DIR__ . '/partials/sidebar.php'; ?>
<main class="main-content">
    <header class="topbar"><h2>My Profile</h2></header>
    <section class="page-body settings-body">

        <?php if ($success !== ''): ?>
            <div class="alert alert--success"><?= e($success) ?></div>
        <?php endif; ?>

        <!-- Profile info card -->
        <div class="settings-card">
            <div class="settings-card__header">
                <h3>Personal Information</h3>
                <p>Account status:
                    <span class="badge badge--<?= e($user['status']) ?>">
                        <?= e(ucfirst($user['status'])) ?>
                    </span>
                </p>
            </div>
            <div class="settings-card__body">
                <form method="POST" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="profile-grid">
                        <!-- Avatar -->
                        <div class="profile-avatar-wrap">
                            <img id="avatarPreview"
                                 src="<?= $user['profile_photo']
                                    ? e(UPLOAD_URL.'avatars/'.$user['profile_photo'])
                                    : e(APP_URL.'/assets/images/no-image.svg') ?>"
                                 alt="Profile photo" class="profile-avatar">
                            <label class="btn btn--sm btn--ghost" style="cursor:pointer;margin-top:.5rem">
                                Change Photo
                                <input type="file" name="profile_photo"
                                       accept="image/jpeg,image/png,image/webp"
                                       class="img-file-input" id="avatarInput">
                            </label>
                            <?php if (!empty($errors['photo'])): ?>
                                <span class="field-error"><?= e($errors['photo']) ?></span>
                            <?php endif; ?>
                            <p class="field-hint">JPG, PNG or WebP · max 1 MB</p>
                        </div>

                        <!-- Fields -->
                        <div style="flex:1;display:flex;flex-direction:column;gap:0">
                            <div class="form-group">
                                <label for="name">Full Name <span class="required">*</span></label>
                                <input type="text" id="name" name="name"
                                       value="<?= e($user['name']) ?>" maxlength="150" required>
                                <span class="field-error"><?= e($errors['name'] ?? '') ?></span>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" value="<?= e($user['email']) ?>"
                                       disabled class="input--readonly">
                                <span class="field-hint">Email cannot be changed.</span>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone"
                                       value="<?= e($user['phone'] ?? '') ?>" maxlength="30">
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address"
                                          rows="3" maxlength="500"><?= e($user['address'] ?? '') ?></textarea>
                            </div>
                            <div class="form-actions" style="justify-content:flex-start">
                                <button type="submit" class="btn btn--primary">Save Changes</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change password card -->
        <div class="settings-card">
            <div class="settings-card__header">
                <h3>Change Password</h3>
            </div>
            <div class="settings-card__body">
                <form method="POST" novalidate>
                    <input type="hidden" name="action" value="change_password">
                    <div class="settings-pw-grid">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password"
                                   name="current_password" required autocomplete="current-password">
                            <span class="field-error"><?= e($errors['pw_cur'] ?? '') ?></span>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password"
                                   name="new_password" required autocomplete="new-password">
                            <span class="field-error"><?= e($errors['pw_new'] ?? '') ?></span>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password"
                                   name="confirm_password" required autocomplete="new-password">
                            <span class="field-error"><?= e($errors['pw_conf'] ?? '') ?></span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn--primary">Update Password</button>
                </form>
            </div>
        </div>

    </section>
</main>
<script>
// Avatar preview
document.getElementById('avatarInput')?.addEventListener('change', function () {
    if (this.files[0]) {
        const r = new FileReader();
        r.onload = e => { document.getElementById('avatarPreview').src = e.target.result; };
        r.readAsDataURL(this.files[0]);
    }
});
</script>
</body>
</html>