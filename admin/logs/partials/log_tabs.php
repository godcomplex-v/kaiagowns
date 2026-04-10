<?php $cur=basename($_SERVER['PHP_SELF'],'.php'); ?>
<div class="tab-bar" style="margin-bottom:1.5rem">
    <a href="<?= e(APP_URL) ?>/admin/logs/activity.php" class="tab-link <?= $cur==='activity'?'tab-link--active':'' ?>">Activity Log</a>
    <a href="<?= e(APP_URL) ?>/admin/logs/login_history.php" class="tab-link <?= $cur==='login_history'?'tab-link--active':'' ?>">Login History</a>
    <a href="<?= e(APP_URL) ?>/admin/logs/transactions.php" class="tab-link <?= $cur==='transactions'?'tab-link--active':'' ?>">Transaction Log</a>
</div>
