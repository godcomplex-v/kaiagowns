<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_guard.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/pagination.php';

session_guard('staff');

$db     = get_db();
$search = get_param('search');
$cat_id = (int)get_param('category');
$status = get_param('status');

$where  = ['1=1'];
$params = [];

if ($search !== '') {
    $where[]      = '(i.name LIKE :s OR i.description LIKE :s)';
    $params[':s'] = '%' . $search . '%';
}
if ($cat_id > 0) {
    $where[]      = 'i.category_id = :cat';
    $params[':cat'] = $cat_id;
}
if (in_array($status, ['available','reserved','damaged','retired'], true)) {
    $where[]          = 'i.status = :status';
    $params[':status'] = $status;
}
$where_sql = implode(' AND ', $where);

$cnt = $db->prepare(
    "SELECT COUNT(*) FROM items i WHERE {$where_sql}"
);
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pag   = paginate($total, 20);

$stmt = $db->prepare(
    "SELECT i.id, i.name, c.name AS category, i.size,
            i.stock, i.rental_price, i.sale_price,
            i.status, i.image, i.description
     FROM items i
     LEFT JOIN categories c ON c.id = i.category_id
     WHERE {$where_sql}
     ORDER BY i.name
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $pag['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pag['offset'],   PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

$cats = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

$qs = http_build_query(array_filter([
    'search' => $search, 'category' => $cat_id ?: '', 'status' => $status
]));
$url_pattern = APP_URL . '/staff/inventory/index.php?' . ($qs?$qs.'&':'') . 'page=%d';
$active_nav  = 'inventory';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/staff.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/inventory.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/staff_dash.css">
</head>
<body>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>
<main class="main-content">
    <header class="topbar">
        <h2>Inventory</h2>
        <span>Welcome, <?= e($_SESSION['name']) ?></span>
    </header>
    <section class="page-body">

        <div class="table-toolbar">
            <form method="GET" class="toolbar-filters" style="flex-wrap:wrap">
                <input type="search" name="search" placeholder="Search items…"
                       value="<?= e($search) ?>" class="toolbar-search">
                <select name="category" class="toolbar-select">
                    <option value="">All categories</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"
                            <?= $cat_id===(int)$c['id']?'selected':'' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="status" class="toolbar-select">
                    <option value="">All statuses</option>
                    <option value="available" <?= $status==='available'?'selected':'' ?>>Available</option>
                    <option value="reserved"  <?= $status==='reserved' ?'selected':'' ?>>Reserved</option>
                    <option value="damaged"   <?= $status==='damaged'  ?'selected':'' ?>>Damaged</option>
                    <option value="retired"   <?= $status==='retired'  ?'selected':'' ?>>Retired</option>
                </select>
                <button type="submit" class="btn btn--primary">Filter</button>
                <?php if ($search || $cat_id || $status): ?>
                    <a href="index.php" class="btn btn--ghost">Clear</a>
                <?php endif; ?>
            </form>
            <span class="table-count"><?= $total ?> item<?= $total!==1?'s':'' ?></span>
        </div>

        <div class="table-wrap">
            <table class="data-table" id="invTable">
                <thead>
                <tr>
                    <th>Image</th><th>Name</th><th>Category</th><th>Size</th>
                    <th>Stock</th><th>Rental</th><th>Sale</th>
                    <th>Status</th><th>Update Status</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="9" class="table-empty">No items found.</td></tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <tr id="inv-row-<?= (int)$item['id'] ?>">
                        <td>
                            <img src="<?= $item['image']
                                ? e(UPLOAD_URL.'items/'.$item['image'])
                                : e(APP_URL.'/assets/images/no-image.svg') ?>"
                                 alt="" class="inv-thumb" loading="lazy">
                        </td>
                        <td>
                            <div>
                                <div style="font-weight:500"><?= e($item['name']) ?></div>
                                <?php if ($item['description']): ?>
                                <div style="font-size:.78rem;color:var(--text-muted)">
                                    <?= e(mb_substr($item['description'], 0, 60))
                                      . (mb_strlen($item['description'])>60?'…':'') ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?= e($item['category'] ?? '—') ?></td>
                        <td><?= e($item['size'] ?? '—') ?></td>
                        <td>
                            <span class="stock-badge <?= (int)$item['stock']===0?'stock-badge--zero':'' ?>">
                                <?= (int)$item['stock'] ?>
                            </span>
                        </td>
                        <td><?= $item['rental_price']>0 ? fmt_money($item['rental_price']) : '—' ?></td>
                        <td><?= $item['sale_price']  >0 ? fmt_money($item['sale_price'])   : '—' ?></td>
                        <td>
                            <span class="badge badge--<?= e($item['status']) ?>"
                                  id="status-badge-<?= (int)$item['id'] ?>">
                                <?= e(ucfirst($item['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <select class="status-select toolbar-select"
                                    data-id="<?= (int)$item['id'] ?>"
                                    style="font-size:.82rem;padding:.3rem .5rem">
                                <option value="">Change…</option>
                                <?php foreach (['available','reserved','damaged'] as $st): ?>
                                    <?php if ($st !== $item['status']): ?>
                                    <option value="<?= $st ?>">
                                        → <?= ucfirst($st) ?>
                                    </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?= pagination_html($pag, $url_pattern) ?>

    </section>
</main>

<div id="toast" class="toast" role="alert" aria-live="polite"></div>
<script>const APP_URL = '<?= e(APP_URL) ?>';</script>
<script src="<?= e(APP_URL) ?>/assets/js/staff_inventory.js"></script>
</body>
</html>