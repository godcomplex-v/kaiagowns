<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_guard.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/pagination.php';

session_guard('customer');

$db     = get_db();
$search = get_param('search');
$cat_id = (int)get_param('category');
$avail  = get_param('availability');  // 'rent','sale','available'
$size   = get_param('size');
$sort   = get_param('sort') ?: 'name';

$where  = ["i.status != 'retired'"];
$params = [];

if ($search !== '') {
    $where[]      = '(i.name LIKE :s OR i.description LIKE :s)';
    $params[':s'] = '%' . $search . '%';
}
if ($cat_id > 0) {
    $where[]       = 'i.category_id = :cat';
    $params[':cat'] = $cat_id;
}
if ($avail === 'rent')      { $where[] = 'i.rental_price > 0 AND i.stock > 0'; }
if ($avail === 'sale')      { $where[] = 'i.sale_price > 0   AND i.stock > 0'; }
if ($avail === 'available') { $where[] = "i.status = 'available' AND i.stock > 0"; }
if ($size !== '')  {
    $where[]       = 'i.size = :size';
    $params[':size'] = $size;
}

$order = match($sort) {
    'price_asc'  => 'i.rental_price ASC',
    'price_desc' => 'i.rental_price DESC',
    'newest'     => 'i.created_at DESC',
    default      => 'i.name ASC',
};

$where_sql = implode(' AND ', $where);

$cnt = $db->prepare(
    "SELECT COUNT(*) FROM items i WHERE {$where_sql}"
);
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pag   = paginate($total, 12);   // 12 per page for grid layout

$stmt = $db->prepare(
    "SELECT i.id, i.name, i.size, i.stock, i.rental_price, i.sale_price,
            i.status, i.image, i.description, c.name AS category
     FROM items i
     LEFT JOIN categories c ON c.id = i.category_id
     WHERE {$where_sql}
     ORDER BY {$order}
     LIMIT :limit OFFSET :offset"
);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $pag['per_page'], PDO::PARAM_INT);
$stmt->bindValue(':offset', $pag['offset'],   PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();

$cats  = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$sizes = $db->query(
    "SELECT DISTINCT size FROM items WHERE size IS NOT NULL AND size != '' ORDER BY size"
)->fetchAll(PDO::FETCH_COLUMN);

$qs = http_build_query(array_filter([
    'search' => $search, 'category' => $cat_id ?: '',
    'availability' => $avail, 'size' => $size, 'sort' => $sort !== 'name' ? $sort : ''
]));
$url_pattern = APP_URL . '/customer/catalog/index.php?' . ($qs?$qs.'&':'') . 'page=%d';
$active_nav  = 'catalog';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Catalog — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/customer.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/customer_dash.css">
</head>
<body>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>
<main class="main-content">
    <header class="topbar">
        <h2>Browse Catalog</h2>
    </header>
    <section class="page-body">

        <!-- Filters -->
        <form method="GET" class="catalog-filters" id="filterForm">
            <input type="search" name="search" placeholder="Search gowns…"
                   value="<?= e($search) ?>" class="toolbar-search catalog-search">
            <select name="category" class="toolbar-select">
                <option value="">All categories</option>
                <?php foreach ($cats as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"
                        <?= $cat_id===(int)$c['id']?'selected':'' ?>>
                        <?= e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="size" class="toolbar-select">
                <option value="">All sizes</option>
                <?php foreach ($sizes as $s): ?>
                    <option value="<?= e($s) ?>" <?= $size===$s?'selected':'' ?>>
                        <?= e($s) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="availability" class="toolbar-select">
                <option value="">All items</option>
                <option value="available" <?= $avail==='available'?'selected':'' ?>>In stock</option>
                <option value="rent"      <?= $avail==='rent'     ?'selected':'' ?>>For rent</option>
                <option value="sale"      <?= $avail==='sale'     ?'selected':'' ?>>For sale</option>
            </select>
            <select name="sort" class="toolbar-select">
                <option value="name"       <?= $sort==='name'      ?'selected':'' ?>>A–Z</option>
                <option value="newest"     <?= $sort==='newest'    ?'selected':'' ?>>Newest</option>
                <option value="price_asc"  <?= $sort==='price_asc' ?'selected':'' ?>>Price ↑</option>
                <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price ↓</option>
            </select>
            <button type="submit" class="btn btn--primary">Filter</button>
            <?php if ($search||$cat_id||$avail||$size||$sort!=='name'): ?>
                <a href="index.php" class="btn btn--ghost">Clear</a>
            <?php endif; ?>
        </form>

        <div class="catalog-meta">
            <span class="table-count"><?= $total ?> item<?= $total!==1?'s':'' ?></span>
        </div>

        <!-- Grid -->
        <?php if (empty($items)): ?>
            <div class="catalog-empty">
                <p>No items match your search. <a href="index.php">Clear filters →</a></p>
            </div>
        <?php else: ?>
        <div class="catalog-grid">
            <?php foreach ($items as $item): ?>
            <a href="<?= e(APP_URL) ?>/customer/catalog/item.php?id=<?= (int)$item['id'] ?>"
               class="catalog-card">
                <div class="catalog-card__img">
                    <img src="<?= $item['image']
                        ? e(UPLOAD_URL.'items/'.$item['image'])
                        : e(APP_URL.'/assets/images/no-image.svg') ?>"
                         alt="<?= e($item['name']) ?>" loading="lazy">
                    <?php if ((int)$item['stock'] === 0): ?>
                        <span class="card-ribbon card-ribbon--out">Out of Stock</span>
                    <?php elseif ($item['status']==='reserved'): ?>
                        <span class="card-ribbon card-ribbon--reserved">Reserved</span>
                    <?php endif; ?>
                </div>
                <div class="catalog-card__body">
                    <h3 class="card-name"><?= e($item['name']) ?></h3>
                    <span class="card-category"><?= e($item['category'] ?? '') ?></span>
                    <?php if ($item['size']): ?>
                        <span class="card-size">Size: <?= e($item['size']) ?></span>
                    <?php endif; ?>
                    <div class="card-prices">
                        <?php if ($item['rental_price'] > 0): ?>
                            <span class="price-rent">
                                <?= fmt_money($item['rental_price']) ?><small>/rental</small>
                            </span>
                        <?php endif; ?>
                        <?php if ($item['sale_price'] > 0): ?>
                            <span class="price-sale">
                                <?= fmt_money($item['sale_price']) ?><small> sale</small>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?= pagination_html($pag, $url_pattern) ?>

    </section>
</main>
</body>
</html>