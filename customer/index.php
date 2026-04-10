<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session_guard.php';
require_once __DIR__ . '/../includes/functions.php';

session_guard('customer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Account — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/customer.css">
</head>
<body>
<aside class="sidebar sidebar--customer">
    <div class="sidebar__brand"><?= e(APP_NAME) ?></div>
    <nav>
        <a href="#" class="nav-link active">Home</a>
        <a href="#" class="nav-link">Browse Catalog</a>
        <a href="#" class="nav-link">My Requests</a>
        <a href="#" class="nav-link">My Rentals</a>
        <a href="#" class="nav-link">My Purchases</a>
        <a href="#" class="nav-link">Returns</a>
        <a href="#" class="nav-link">Notifications</a>
        <a href="#" class="nav-link">My Profile</a>
        <a href="<?= e(APP_URL) ?>/logout.php" class="nav-link nav-link--logout">Logout</a>
    </nav>
</aside>
<main class="main-content">
    <header class="topbar">
        <h2>Welcome, <?= e($_SESSION['name']) ?></h2>
    </header>
    <section class="page-body">
        <p>Customer dashboard — coming soon.</p>
    </section>
</main>
</body>
</html>