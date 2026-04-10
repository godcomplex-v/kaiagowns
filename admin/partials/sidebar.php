<?php $nav=$active_nav??''; ?>
<aside class="sidebar sidebar--admin">
    <div class="sidebar__brand"><?= e(APP_NAME) ?></div>
    <nav>
        <a href="<?= e(APP_URL) ?>/admin/index.php" class="nav-link <?= $nav==='dashboard'?'active':'' ?>">Dashboard</a>
        <a href="<?= e(APP_URL) ?>/admin/users/customers.php" class="nav-link <?= $nav==='users'?'active':'' ?>">Manage Users</a>
        <a href="<?= e(APP_URL) ?>/admin/inventory/index.php" class="nav-link <?= $nav==='inventory'?'active':'' ?>">Inventory</a>
        <a href="<?= e(APP_URL) ?>/admin/transactions/pending.php" class="nav-link <?= $nav==='transactions'?'active':'' ?>">Transactions</a>
        <a href="<?= e(APP_URL) ?>/admin/reports/inventory.php" class="nav-link <?= $nav==='reports'?'active':'' ?>">Reports</a>
        <a href="<?= e(APP_URL) ?>/admin/logs/activity.php" class="nav-link <?= $nav==='logs'?'active':'' ?>">Logs</a>
        <a href="<?= e(APP_URL) ?>/admin/settings/index.php" class="nav-link <?= $nav==='settings'?'active':'' ?>">Settings</a>
        <a href="<?= e(APP_URL) ?>/logout.php" class="nav-link nav-link--logout">Logout</a>
    </nav>
</aside>
