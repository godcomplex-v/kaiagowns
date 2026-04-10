<?php
$counts=$db->query("SELECT SUM(status='pending') AS pending,SUM(status='active') AS active,SUM(status='overdue') AS overdue,SUM(status='returned') AS returned,SUM(status='completed' AND type='rent') AS completed,SUM(status='completed' AND type='sale') AS sales FROM transactions")->fetch();
?>
<div class="stats-bar">
    <a href="<?= e(APP_URL) ?>/admin/transactions/pending.php" class="stat-card stat-card--pending"><span class="stat-num"><?= (int)$counts['pending'] ?></span><span class="stat-label">Pending</span></a>
    <a href="<?= e(APP_URL) ?>/admin/transactions/active.php" class="stat-card stat-card--active"><span class="stat-num"><?= (int)$counts['active'] ?></span><span class="stat-label">Active Rentals</span></a>
    <a href="<?= e(APP_URL) ?>/admin/transactions/overdue.php" class="stat-card stat-card--overdue"><span class="stat-num"><?= (int)$counts['overdue'] ?></span><span class="stat-label">Overdue</span></a>
    <a href="<?= e(APP_URL) ?>/admin/transactions/returns.php" class="stat-card stat-card--returned"><span class="stat-num"><?= (int)$counts['returned'] ?></span><span class="stat-label">Awaiting Confirm</span></a>
    <a href="<?= e(APP_URL) ?>/admin/transactions/completed.php" class="stat-card stat-card--completed"><span class="stat-num"><?= (int)$counts['completed'] ?></span><span class="stat-label">Completed</span></a>
    <a href="<?= e(APP_URL) ?>/admin/transactions/sales.php" class="stat-card stat-card--sales"><span class="stat-num"><?= (int)$counts['sales'] ?></span><span class="stat-label">Sales</span></a>
</div>
