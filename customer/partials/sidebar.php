<?php
$nav = $active_nav ?? '';

// Unread notification count
if (session_status() === PHP_SESSION_NONE) session_start();
$uc = get_db()->prepare(
    'SELECT COUNT(*) FROM notifications WHERE user_id=:uid AND is_read=0'
);
$uc->execute([':uid' => $_SESSION['user_id']]);
$unread = (int)$uc->fetchColumn();
?>
<aside class="sidebar sidebar--customer">
    <div class="sidebar__brand"><?= e(APP_NAME) ?></div>
    <nav>
        <a href="<?= e(APP_URL) ?>/customer/index.php"
           class="nav-link <?= $nav==='home'          ?'active':'' ?>">Home</a>
        <a href="<?= e(APP_URL) ?>/customer/catalog/index.php"
           class="nav-link <?= $nav==='catalog'       ?'active':'' ?>">Browse Catalog</a>
        <a href="<?= e(APP_URL) ?>/customer/requests/index.php"
           class="nav-link <?= $nav==='requests'      ?'active':'' ?>">My Requests</a>
        <a href="<?= e(APP_URL) ?>/customer/rentals/index.php"
           class="nav-link <?= $nav==='rentals'       ?'active':'' ?>">My Rentals</a>
        <a href="<?= e(APP_URL) ?>/customer/purchases/index.php"
           class="nav-link <?= $nav==='purchases'     ?'active':'' ?>">My Purchases</a>
        <a href="<?= e(APP_URL) ?>/customer/returns/index.php"
           class="nav-link <?= $nav==='returns'       ?'active':'' ?>">Returns</a>
        <a href="<?= e(APP_URL) ?>/customer/notifications.php"
           class="nav-link <?= $nav==='notifications' ?'active':'' ?>">
            Notifications
            <?php if ($unread > 0): ?>
                <span class="notif-badge"><?= $unread ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= e(APP_URL) ?>/customer/profile.php"
           class="nav-link <?= $nav==='profile'       ?'active':'' ?>">My Profile</a>
        <a href="<?= e(APP_URL) ?>/logout.php"
           class="nav-link nav-link--logout">Logout</a>
    </nav>
</aside>