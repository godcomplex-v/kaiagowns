<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/session_guard.php';
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/txn_helpers.php';
session_guard('admin');
flag_overdue_rentals();
$db=get_db();
$s=$db->query("SELECT (SELECT COUNT(*) FROM users WHERE role='customer') AS c,(SELECT COUNT(*) FROM users WHERE role='staff') AS st,(SELECT COUNT(*) FROM items) AS it,(SELECT COUNT(*) FROM transactions WHERE status='pending') AS pend,(SELECT COUNT(*) FROM transactions WHERE status='overdue') AS ov,(SELECT COUNT(*) FROM transactions WHERE status='active') AS ac")->fetch();
$active_nav='dashboard';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Dashboard — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/transactions.css">
</head><body>
<?php require __DIR__.'/partials/sidebar.php'; ?>
<main class="main-content">
<header class="topbar"><h2>Dashboard</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body">
<div class="stats-bar">
    <a href="<?= e(APP_URL) ?>/admin/users/customers.php" class="stat-card"><span class="stat-num"><?= (int)$s['c'] ?></span><span class="stat-label">Customers</span></a>
    <a href="<?= e(APP_URL) ?>/admin/users/staff.php" class="stat-card"><span class="stat-num"><?= (int)$s['st'] ?></span><span class="stat-label">Staff</span></a>
    <a href="<?= e(APP_URL) ?>/admin/inventory/index.php" class="stat-card"><span class="stat-num"><?= (int)$s['it'] ?></span><span class="stat-label">Items</span></a>
    <a href="<?= e(APP_URL) ?>/admin/transactions/pending.php" class="stat-card stat-card--pending"><span class="stat-num"><?= (int)$s['pend'] ?></span><span class="stat-label">Pending</span></a>
    <a href="<?= e(APP_URL) ?>/admin/transactions/active.php" class="stat-card stat-card--active"><span class="stat-num"><?= (int)$s['ac'] ?></span><span class="stat-label">Active Rentals</span></a>
    <a href="<?= e(APP_URL) ?>/admin/transactions/overdue.php" class="stat-card stat-card--overdue"><span class="stat-num"><?= (int)$s['ov'] ?></span><span class="stat-label">Overdue</span></a>
</div>
</section></main></body></html>
