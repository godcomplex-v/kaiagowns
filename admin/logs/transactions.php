<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/pagination.php';
session_guard('admin');
$db=get_db(); $search=get_param('search'); $range=date_range();
$txn_actions=['approve_txn','reject_txn','process_return','complete_txn','adjust_stock','add_item','edit_item','delete_item','toggle_customer_status','add_staff','edit_staff','delete_staff'];
$placeholders=implode(',',array_fill(0,count($txn_actions),'?'));
$where=["l.action IN ({$placeholders})"]; $params=$txn_actions;
if($search!==''){$where[]='(u.name LIKE ? OR l.details LIKE ?)';$params[]='%'.$search.'%';$params[]='%'.$search.'%';}
$where[]='DATE(l.created_at) BETWEEN ? AND ?'; $params[]=$range['from']; $params[]=$range['to'];
$ws=implode(' AND ',$where);
$cnt=$db->prepare("SELECT COUNT(*) FROM logs l LEFT JOIN users u ON u.id=l.user_id WHERE {$ws}"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
$pag=paginate($total,25);
$stmt=$db->prepare("SELECT l.action,l.details,l.ip,l.created_at,u.name AS user_name,u.role AS user_role FROM logs l LEFT JOIN users u ON u.id=l.user_id WHERE {$ws} ORDER BY l.created_at DESC LIMIT ? OFFSET ?");
$params[]=$pag['per_page']; $params[]=$pag['offset']; $stmt->execute($params); $rows=$stmt->fetchAll();
$qs=http_build_query(array_filter(['search'=>$search,'from'=>$range['from'],'to'=>$range['to']]));
$url_pattern=APP_URL.'/admin/logs/transactions.php?'.($qs?$qs.'&':'').'page=%d';
$active_nav='logs';
function action_severity(string $a): string {
    if(str_contains($a,'delete')||str_contains($a,'reject')) return 'danger';
    if(str_contains($a,'approve')||str_contains($a,'complete')) return 'success';
    if(str_contains($a,'edit')||str_contains($a,'adjust')) return 'warn';
    return 'neutral';
}
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Transaction Log — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/logs.css">
</head><body><?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content"><header class="topbar"><h2>Logs</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body">
<?php require __DIR__.'/partials/log_tabs.php'; ?>
<div class="table-toolbar" style="flex-wrap:wrap">
    <form method="GET" class="toolbar-filters" style="flex-wrap:wrap">
        <input type="search" name="search" placeholder="Search user or detail…" value="<?= e($search) ?>" class="toolbar-search">
        <input type="date" name="from" value="<?= e($range['from']) ?>" class="date-input">
        <span style="color:var(--text-muted);font-size:.85rem">to</span>
        <input type="date" name="to" value="<?= e($range['to']) ?>" class="date-input">
        <button type="submit" class="btn btn--primary">Filter</button>
        <a href="transactions.php" class="btn btn--ghost">Clear</a>
    </form>
    <span class="table-count"><?= number_format($total) ?> entries</span>
</div>
<div class="table-wrap"><table class="data-table log-table">
<thead><tr><th>Time</th><th>Staff / Admin</th><th>Action</th><th>Details</th></tr></thead>
<tbody><?php if(empty($rows)): ?><tr><td colspan="4" class="table-empty">No transaction log entries.</td></tr>
<?php else: foreach($rows as $r): ?>
<tr>
    <td class="log-time"><?= fmt_date($r['created_at'],'M d, Y H:i:s') ?></td>
    <td><?= e($r['user_name']??'—') ?><?php if($r['user_role']): ?> <span class="badge badge--role-<?= e($r['user_role']) ?>" style="font-size:.7rem"><?= e(ucfirst($r['user_role'])) ?></span><?php endif; ?></td>
    <td><code class="action-code action-code--<?= e(action_severity($r['action'])) ?>"><?= e($r['action']) ?></code></td>
    <td class="log-detail"><?= e($r['details']??'—') ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?= pagination_html($pag,$url_pattern) ?>
</section></main>
</body></html>
