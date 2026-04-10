<?php $nav = $active_nav ?? ''; ?>
<aside class="sidebar sidebar--staff">
    <div class="sidebar__brand"><?= e(APP_NAME) ?></div>
    <nav>
        <a href="<?= e(APP_URL) ?>/staff/index.php"
           class="nav-link <?= $nav==='dashboard'     ?'active':'' ?>">Dashboard</a>
        <a href="<?= e(APP_URL) ?>/staff/transactions/index.php"
           class="nav-link <?= $nav==='transactions'  ?'active':'' ?>">Transactions</a>
        <a href="<?= e(APP_URL) ?>/staff/transactions/create.php"
           class="nav-link <?= $nav==='create'        ?'active':'' ?>">+ New Request</a>
        <a href="<?= e(APP_URL) ?>/staff/inventory/index.php"
           class="nav-link <?= $nav==='inventory'     ?'active':'' ?>">Inventory</a>
        <a href="<?= e(APP_URL) ?>/staff/reports/index.php"
           class="nav-link <?= $nav==='reports'       ?'active':'' ?>">Reports</a>
        <a href="<?= e(APP_URL) ?>/staff/notifications.php"
           class="nav-link <?= $nav==='notifications' ?'active':'' ?>">
            Notifications
            <?php
            // Unread badge
            if (session_status() === PHP_SESSION_NONE) session_start();
            $unread = get_db()->prepare(
                'SELECT COUNT(*) FROM notifications
                 WHERE user_id=:uid AND is_read=0'
            );
            $unread->execute([':uid' => $_SESSION['user_id']]);
            $unread_count = (int)$unread->fetchColumn();
            if ($unread_count > 0):
            ?>
                <span class="notif-badge"><?= $unread_count ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= e(APP_URL) ?>/logout.php"
           class="nav-link nav-link--logout">Logout</a>
    </nav>
</aside>