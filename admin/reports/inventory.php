<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php'; require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php'; require_once __DIR__.'/../../includes/functions.php';
session_guard('admin');
$db=get_db(); $search=get_param('search'); $cat_id=(int)get_param('category'); $status=get_param('status');
$where=['1=1']; $params=[];
if($search!==''){$where[]='i.name LIKE :s';$params[':s']='%'.$search.'%';}
if($cat_id>0){$where[]='i.category_id=:cat';$params[':cat']=$cat_id;}
if(in_array($status,['available','reserved','damaged','retired'],true)){$where[]='i.status=:status';$params[':status']=$status;}
$ws=implode(' AND ',$where);
$items=$db->prepare("SELECT i.name AS 'Item',c.name AS 'Category',i.size AS 'Size',i.stock AS 'Stock',i.status AS 'Status',i.rental_price AS 'Rental Price',i.sale_price AS 'Sale Price',i.created_at AS 'Added' FROM items i LEFT JOIN categories c ON c.id=i.category_id WHERE {$ws} ORDER BY c.name,i.name");
$items->execute($params); $rows=$items->fetchAll();
$summary=$db->query("SELECT COUNT(*) AS total_items,SUM(stock) AS total_stock,SUM(status='available') AS available,SUM(status='reserved') AS reserved,SUM(status='damaged') AS damaged,SUM(status='retired') AS retired,SUM(stock*rental_price) AS rental_value,SUM(stock*sale_price) AS sale_value FROM items")->fetch();
$by_cat=$db->query("SELECT c.name AS label,SUM(i.stock) AS value FROM items i JOIN categories c ON c.id=i.category_id GROUP BY c.id ORDER BY value DESC")->fetchAll();
$cats=$db->query("SELECT id,name FROM categories ORDER BY name")->fetchAll();
$active_nav='reports';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Inventory Report — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/inventory.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/reports.css">
</head><body><?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content"><header class="topbar"><h2>Reports</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body">
<?php require __DIR__.'/partials/report_tabs.php'; ?>
<div class="report-header"><h3 class="report-title">Inventory Report</h3>
<button class="btn btn--ghost btn--sm export-btn" data-type="inventory" data-params="<?= e(http_build_query(array_filter(['search'=>$search,'category'=>$cat_id?:'','status'=>$status]))) ?>">⬇ Export CSV</button></div>
<div class="summary-grid">
    <div class="summary-card"><span class="summary-num"><?= (int)$summary['total_items'] ?></span><span class="summary-label">Total Items</span></div>
    <div class="summary-card"><span class="summary-num"><?= (int)$summary['total_stock'] ?></span><span class="summary-label">Total Stock</span></div>
    <div class="summary-card summary-card--good"><span class="summary-num"><?= (int)$summary['available'] ?></span><span class="summary-label">Available</span></div>
    <div class="summary-card summary-card--warn"><span class="summary-num"><?= (int)$summary['reserved'] ?></span><span class="summary-label">Reserved</span></div>
    <div class="summary-card summary-card--danger"><span class="summary-num"><?= (int)$summary['damaged'] ?></span><span class="summary-label">Damaged</span></div>
    <div class="summary-card summary-card--money"><span class="summary-num"><?= fmt_money($summary['rental_value']??0) ?></span><span class="summary-label">Rental Value</span></div>
    <div class="summary-card summary-card--money"><span class="summary-num"><?= fmt_money($summary['sale_value']??0) ?></span><span class="summary-label">Sale Value</span></div>
</div>
<div class="chart-row">
    <div class="chart-card"><h4 class="chart-title">Stock by Category</h4><div class="chart-wrap"><canvas id="stockByCatChart"></canvas></div></div>
    <div class="chart-card"><h4 class="chart-title">Status Distribution</h4><div class="chart-wrap chart-wrap--sm"><canvas id="statusChart"></canvas></div></div>
</div>
<div class="table-toolbar">
    <form method="GET" class="toolbar-filters">
        <input type="search" name="search" placeholder="Search item…" value="<?= e($search) ?>" class="toolbar-search">
        <select name="category" class="toolbar-select"><option value="">All categories</option><?php foreach($cats as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $cat_id===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
        <select name="status" class="toolbar-select"><option value="">All statuses</option><option value="available" <?= $status==='available'?'selected':'' ?>>Available</option><option value="reserved" <?= $status==='reserved'?'selected':'' ?>>Reserved</option><option value="damaged" <?= $status==='damaged'?'selected':'' ?>>Damaged</option><option value="retired" <?= $status==='retired'?'selected':'' ?>>Retired</option></select>
        <button type="submit" class="btn btn--primary">Filter</button>
        <?php if($search||$cat_id||$status): ?><a href="inventory.php" class="btn btn--ghost">Clear</a><?php endif; ?>
    </form>
    <span class="table-count"><?= count($rows) ?> items</span>
</div>
<div class="table-wrap"><table class="data-table report-table">
<thead><tr><th>Item</th><th>Category</th><th>Size</th><th>Stock</th><th>Status</th><th>Rental Price</th><th>Sale Price</th><th>Added</th></tr></thead>
<tbody>
<?php if(empty($rows)): ?><tr><td colspan="8" class="table-empty">No items match your filters.</td></tr>
<?php else: foreach($rows as $r): ?>
<tr><td><?= e($r['Item']) ?></td><td><?= e($r['Category']??'—') ?></td><td><?= e($r['Size']??'—') ?></td>
<td><span class="stock-badge <?= (int)$r['Stock']===0?'stock-badge--zero':'' ?>"><?= (int)$r['Stock'] ?></span></td>
<td><span class="badge badge--<?= e(strtolower($r['Status'])) ?>"><?= e(ucfirst($r['Status'])) ?></span></td>
<td><?= fmt_money($r['Rental Price']) ?></td><td><?= fmt_money($r['Sale Price']) ?></td><td><?= fmt_date($r['Added']) ?></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div>
</section></main>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>const APP_URL='<?= e(APP_URL) ?>';const chartData={byCat:{labels:<?= json_encode(array_column($by_cat,'label')) ?>,values:<?= json_encode(array_map('intval',array_column($by_cat,'value'))) ?>},status:{labels:['Available','Reserved','Damaged','Retired'],values:[<?= (int)$summary['available'] ?>,<?= (int)$summary['reserved'] ?>,<?= (int)$summary['damaged'] ?>,<?= (int)$summary['retired'] ?>]}};</script>
<script src="<?= e(APP_URL) ?>/assets/js/reports.js"></script>
</body></html>
