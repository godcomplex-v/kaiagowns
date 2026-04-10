<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session_guard.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/pagination.php';

session_guard('staff');

$db  = get_db();
$uid = (int)$_SESSION['user_id'];

$filter = get_param('filter');  // 'unread' | ''

$where  = ['user_id = :uid'];
$params = [':uid' => $uid];

if ($filter === 'unread') {
    $where[]  = 'is_read = 0';
}
$where_sql = implode(' AND ', $where);

$cnt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE {$where_sql}");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pag   = paginate($total, 20);

$stmt = $db->prepare(
    "SELECT id, message, type, is_read, created_at
     FROM notifications
     WHERE {$where_sql}
     ORDER BY created_at DESC
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $pag['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pag['offset'],   PDO::PARAM_INT);
$stmt->execute();
$notifs = $stmt->fetchAll();

$unread_count = (int)$db->prepare(
    'SELECT COUNT(*) FROM notifications WHERE user_id=:uid AND is_read=0'
)->execute([':uid'=>$uid]) ? 0 : 0;

$uc = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=:uid AND is_read=0');
$uc->execute([':uid' => $uid]);
$unread_count = (int)$uc->fetchColumn();

$url_pattern = APP_URL . '/staff/notifications.php?' . ($filter?'filter='.e($filter).'&':'') . 'page=%d';
$active_nav  = 'notifications';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/staff.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/staff_dash.css">
</head>
<body>
<?php require __DIR__ . '/partials/sidebar.php'; ?>
<main class="main-content">
    <header class="topbar">
        <h2>Notifications</h2>
        <span>Welcome, <?= e($_SESSION['name']) ?></span>
    </header>
    <section class="page-body">

        <div class="table-toolbar">
            <div style="display:flex;gap:.5rem;align-items:center">
                <a href="notifications.php"
                   class="btn btn--sm <?= !$filter?'btn--primary':'btn--ghost' ?>">All</a>
                <a href="notifications.php?filter=unread"
                   class="btn btn--sm <?= $filter==='unread'?'btn--primary':'btn--ghost' ?>">
                    Unread <?= $unread_count>0?"({$unread_count})":'' ?>
                </a>
            </div>
            <?php if ($unread_count > 0): ?>
            <button class="btn btn--ghost btn--sm" id="markAllRead">
                ✓ Mark All Read
            </button>
            <?php endif; ?>
        </div>

        <div class="notif-full-list">
            <?php if (empty($notifs)): ?>
                <div class="table-empty" style="padding:2.5rem;text-align:center">
                    No notifications.
                </div>
            <?php else: ?>
                <?php foreach ($notifs as $n): ?>
                <div class="notif-full-item <?= !$n['is_read']?'notif-full-item--unread':'' ?>"
                     id="notif-<?= (int)$n['id'] ?>">
                    <div class="notif-full-icon notif-icon--<?= e($n['type']) ?>">
                        <?php
                        $icons = [
                            'approved'       => '✅',
                            'rejected'       => '❌',
                            'return_received'=> '📦',
                            'completed'      => '🎉',
                            'overdue'        => '⚠',
                            'info'           => 'ℹ',
                        ];
                        echo $icons[$n['type']] ?? 'ℹ';
                        ?>
                    </div>
                    <div class="notif-full-body">
                        <p class="notif-full-msg"><?= e($n['message']) ?></p>
                        <span class="notif-full-time">
                            <?= fmt_date($n['created_at'], 'M d, Y H:i') ?>
                        </span>
                    </div>
                    <?php if (!$n['is_read']): ?>
                    <button class="btn btn--sm btn--ghost mark-read-btn"
                            data-id="<?= (int)$n['id'] ?>">
                        Mark Read
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?= pagination_html($pag, $url_pattern) ?>

    </section>
</main>

<div id="toast" class="toast" role="alert" aria-live="polite"></div>
<script>const APP_URL = '<?= e(APP_URL) ?>';</script>
<script src="<?= e(APP_URL) ?>/assets/js/staff_notifications.js"></script>
</body>
</html>