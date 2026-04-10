<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
require_once __DIR__.'/../../includes/pagination.php';
session_guard('admin');
$db=get_db(); $search=get_param('search'); $status=get_param('status'); $range=date_range();
$where=["l.action IN ('login','login_failed','login_blocked')"]; $params=[];
if($search!==''){$where[]='(u.name LIKE :s OR l.ip LIKE :s OR l.details LIKE :s)';$params[':s']='%'.$search.'%';}
if($status==='success'){$where[]="l.action='login'";}
elseif($status==='failed'){$where[]="l.action IN ('login_failed','login_blocked')";}
$where[]='DATE(l.created_at) BETWEEN :from AND :to'; $params[':from']=$range['from']; $params[':to']=$range['to'];
$ws=implode(' AND ',$where);
$cnt=$db->prepare("SELECT COUNT(*) FROM logs l LEFT JOIN users u ON u.id=l.user_id WHERE {$ws}"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
$pag=paginate($total,25);
$stmt=$db->prepare("SELECT l.action,l.details,l.ip,l.created_at,u.name AS user_name,u.role AS user_role FROM logs l LEFT JOIN users u ON u.id=l.user_id WHERE {$ws} ORDER BY l.created_at DESC LIMIT :l OFFSET :o");
foreach($params as $k=>$v) $stmt->bindValue($k,$v); $stmt->bindValue(':l',$pag['per_page'],PDO::PARAM_INT); $stmt->bindValue(':o',$pag['offset'],PDO::PARAM_INT); $stmt->execute(); $rows=$stmt->fetchAll();
$stats=$db->prepare("SELECT SUM(action='login') AS successes,SUM(action='login_failed') AS failures,SUM(action='login_blocked') AS blocked,COUNT(DISTINCT ip) AS unique_ips FROM logs WHERE action IN ('login','login_failed','login_blocked') AND DATE(created_at) BETWEEN :from AND :to");
$stats->execute([':from'=>$range['from'],':to'=>$range['to']]); $stats=$stats->fetch();
$qs=http_build_query(array_filter(['search'=>$search,'status'=>$status,'from'=>$range['from'],'to'=>$range['to']]));
$url_pattern=APP_URL.'/admin/logs/login_history.php?'.($qs?$qs.'&':'').'page=%d';
$active_nav='logs';
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Login History — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/admin.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css"><link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/logs.css">
<link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/reports.css">
</head><body><?php require __DIR__.'/../partials/sidebar.php'; ?>
<main class="main-content"><header class="topbar"><h2>Logs</h2><span>Welcome, <?= e($_SESSION['name']) ?></span></header>
<section class="page-body">
<?php require __DIR__.'/partials/log_tabs.php'; ?>
<div class="summary-grid" style="grid-template-columns:repeat(auto-fit,minmax(120px,1fr));margin-bottom:1.25rem">
    <div class="summary-card summary-card--good"><span class="summary-num"><?= (int)$stats['successes'] ?></span><span class="summary-label">Successful</span></div>
    <div class="summary-card summary-card--danger"><span class="summary-num"><?= (int)$stats['failures'] ?></span><span class="summary-label">Failed</span></div>
    <div class="summary-card summary-card--warn"><span class="summary-num"><?= (int)$stats['blocked'] ?></span><span class="summary-label">Blocked</span></div>
    <div class="summary-card"><span class="summary-num"><?= (int)$stats['unique_ips'] ?></span><span class="summary-label">Unique IPs</span></div>
</div>
<div class="table-toolbar" style="flex-wrap:wrap">
    <form method="GET" class="toolbar-filters" style="flex-wrap:wrap">
        <input type="search" name="search" placeholder="Search user or IP…" value="<?= e($search) ?>" class="toolbar-search">
        <select name="status" class="toolbar-select"><option value="">All attempts</option><option value="success" <?= $status==='success'?'selected':'' ?>>Successful</option><option value="failed" <?= $status==='failed'?'selected':'' ?>>Failed / Blocked</option></select>
        <input type="date" name="from" value="<?= e($range['from']) ?>" class="date-input">
        <span style="color:var(--text-muted);font-size:.85rem">to</span>
        <input type="date" name="to" value="<?= e($range['to']) ?>" class="date-input">
        <button type="submit" class="btn btn--primary">Filter</button>
        <a href="login_history.php" class="btn btn--ghost">Clear</a>
    </form>
    <span class="table-count"><?= number_format($total) ?> entries</span>
</div>
<div class="table-wrap"><table class="data-table log-table">
<thead><tr><th>Time</th><th>User</th><th>Role</th><th>Result</th><th>Details</th><th>IP</th></tr></thead>
<tbody><?php if(empty($rows)): ?><tr><td colspan="6" class="table-empty">No login records found.</td></tr>
<?php else: foreach($rows as $r):
    $rc=match($r['action']){'login'=>'login-success','login_failed'=>'login-failed','login_blocked'=>'login-blocked',default=>''};
    $rl=match($r['action']){'login'=>'Success','login_failed'=>'Failed','login_blocked'=>'Blocked',default=>$r['action']};
?>
<tr>
    <td class="log-time"><?= fmt_date($r['created_at'],'M d, Y H:i:s') ?></td>
    <td><?= e($r['user_name']??'—') ?></td>
    <td><?php if($r['user_role']): ?><span class="badge badge--role-<?= e($r['user_role']) ?>"><?= e(ucfirst($r['user_role'])) ?></span><?php else: ?>—<?php endif; ?></td>
    <td><span class="login-badge <?= $rc ?>"><?= $rl ?></span></td>
    <td class="log-detail"><?= e($r['details']??'—') ?></td>
    <td class="log-ip"><?= e($r['ip']??'—') ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?= pagination_html($pag,$url_pattern) ?>
</section></main>
</body></html>
