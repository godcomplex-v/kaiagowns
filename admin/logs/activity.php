<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/pagination.php';
session_guard('admin');
$db=get_db(); $search=get_param('search'); $action_f=get_param('action'); $role_f=get_param('role'); $range=date_range();
$where=['1=1']; $params=[];
if($search!==''){$where[]='(u.name LIKE :s OR l.details LIKE :s OR l.ip LIKE :s)';$params[':s']='%'.$search.'%';}
if($action_f!==''){$where[]='l.action=:action';$params[':action']=$action_f;}
if(in_array($role_f,['admin','staff','customer'],true)){$where[]='u.role=:role';$params[':role']=$role_f;}
$where[]='DATE(l.created_at) BETWEEN :from AND :to'; $params[':from']=$range['from']; $params[':to']=$range['to'];
$ws=implode(' AND ',$where);
$cnt=$db->prepare("SELECT COUNT(*) FROM logs l LEFT JOIN users u ON u.id=l.user_id WHERE {$ws}"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
$pag=paginate($total,25);
$stmt=$db->prepare("SELECT l.id,l.action,l.details,l.ip,l.created_at,u.name AS user_name,u.role AS user_role FROM logs l LEFT JOIN users u ON u.id=l.user_id WHERE {$ws} ORDER BY l.created_at DESC LIMIT :l OFFSET :o");
foreach($params as $k=>$v) $stmt->bindValue($k,$v); $stmt->bindValue(':l',$pag['per_page'],PDO::PARAM_INT); $stmt->bindValue(':o',$pag['offset'],PDO::PARAM_INT); $stmt->execute(); $rows=$stmt->fetchAll();
$actions=$db->query("SELECT DISTINCT action FROM logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
$qs=http_build_query(array_filter(['search'=>$search,'action'=>$action_f,'role'=>$role_f,'from'=>$range['from'],'to'=>$range['to']]));
$url_pattern=APP_URL.'/admin/logs/activity.php?'.($qs?$qs.'&':'').'page=%d';
$active_nav='logs';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Activity Log — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/logs.css">
</head><body><?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content"><header class="topbar"><h2>Logs</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body">
<?php require __DIR__.'/partials/log_tabs.php'; ?>
<div class="table-toolbar" style="flex-wrap:wrap;gap:.6rem">
    <form method="GET" class="toolbar-filters" style="flex-wrap:wrap">
        <input type="search" name="search" placeholder="Search user, detail, IP…" value="<?= e($search) ?>" class="toolbar-search">
        <select name="action" class="toolbar-select"><option value="">All actions</option><?php foreach($actions as $a): ?><option value="<?= e($a) ?>" <?= $action_f===$a?'selected':'' ?>><?= e($a) ?></option><?php endforeach; ?></select>
        <select name="role" class="toolbar-select"><option value="">All roles</option><option value="admin" <?= $role_f==='admin'?'selected':'' ?>>Admin</option><option value="staff" <?= $role_f==='staff'?'selected':'' ?>>Staff</option><option value="customer" <?= $role_f==='customer'?'selected':'' ?>>Customer</option></select>
        <input type="date" name="from" value="<?= e($range['from']) ?>" class="date-input">
        <span style="color:var(--text-muted);font-size:.85rem">to</span>
        <input type="date" name="to" value="<?= e($range['to']) ?>" class="date-input">
        <button type="submit" class="btn btn--primary">Filter</button>
        <a href="activity.php" class="btn btn--ghost">Clear</a>
    </form>
    <span class="table-count"><?= number_format($total) ?> entries</span>
</div>
<div class="table-wrap"><table class="data-table log-table">
<thead><tr><th>Time</th><th>User</th><th>Role</th><th>Action</th><th>Details</th><th>IP Address</th></tr></thead>
<tbody><?php if(empty($rows)): ?><tr><td colspan="6" class="table-empty">No log entries found.</td></tr>
<?php else: foreach($rows as $r): ?>
<tr>
    <td class="log-time"><?= fmt_date($r['created_at'],'M d, Y H:i:s') ?></td>
    <td><?= e($r['user_name']??'—') ?></td>
    <td><?php if($r['user_role']): ?><span class="badge badge--role-<?= e($r['user_role']) ?>"><?= e(ucfirst($r['user_role'])) ?></span><?php else: ?>—<?php endif; ?></td>
    <td><code class="action-code"><?= e($r['action']) ?></code></td>
    <td class="log-detail"><?= e($r['details']??'—') ?></td>
    <td class="log-ip"><?= e($r['ip']??'—') ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?= pagination_html($pag,$url_pattern) ?>
</section></main>
</body></html>
