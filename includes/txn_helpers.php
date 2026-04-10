<?php
declare(strict_types=1);
require_once __DIR__.'/db.php';
require_once __DIR__.'/log_activity.php';
function calc_penalty(string $due, ?string $ret=null): float {
    $d = new DateTimeImmutable($due);
    $a = $ret ? new DateTimeImmutable($ret) : new DateTimeImmutable('today');
    if ($a<=$d) return 0.0;
    return round((int)$a->diff($d)->days * PENALTY_PER_DAY, 2);
}
function decrement_stock(int $item_id, int $staff_id, string $reason): void {
    get_db()->prepare('UPDATE items SET stock=GREATEST(0,stock-1) WHERE id=:id')->execute([':id'=>$item_id]);
    get_db()->prepare('INSERT INTO stock_history (item_id, `change`, reason , staff_id) VALUES (:i,-1,:r,:s)')->execute([':i'=>$item_id,':r'=>$reason,':s'=>$staff_id]);
}
function increment_stock(int $item_id, int $staff_id, string $reason): void {
    get_db()->prepare('UPDATE items SET stock=stock+1 WHERE id=:id')->execute([':id'=>$item_id]);
    get_db()->prepare('INSERT INTO stock_history (item_id, `change` ,reason, staff_id) VALUES (:i,1,:r,:s)')->execute([':i'=>$item_id,':r'=>$reason,':s'=>$staff_id]);
}
function notify_user(int $uid, string $msg, string $type='info'): void {
    get_db()->prepare('INSERT INTO notifications (user_id,message,type) VALUES (:u,:m,:t)')->execute([':u'=>$uid,':m'=>$msg,':t'=>$type]);
}
function flag_overdue_rentals(): int {
    $s = get_db()->prepare("UPDATE transactions SET status='overdue' WHERE type='rent' AND status='active' AND due_date<CURDATE()");
    $s->execute(); $c=$s->rowCount();
    if($c>0) log_activity('auto_flag_overdue',"Flagged {$c} overdue rental(s)");
    return $c;
}
