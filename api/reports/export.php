<?php
declare(strict_types=1);
require_once __DIR__.'/../../includes/config.php';
require_once __DIR__.'/../../includes/db.php';
require_once __DIR__.'/../../includes/session_guard.php';
require_once __DIR__.'/../../includes/functions.php';
session_guard('admin');
$type=get_param('type'); $db=get_db(); $range=date_range();
switch($type){
    case 'inventory':
        $s=get_param('search'); $c=(int)get_param('category'); $st=get_param('status');
        $w=['1=1']; $p=[];
        if($s){$w[]='i.name LIKE :s';$p[':s']='%'.$s.'%';}
        if($c){$w[]='i.category_id=:c';$p[':c']=$c;}
        if(in_array($st,['available','reserved','damaged','retired'],true)){$w[]='i.status=:st';$p[':st']=$st;}
        $stmt=$db->prepare("SELECT i.name AS 'Item',c.name AS 'Category',i.size AS 'Size',i.stock AS 'Stock',i.status AS 'Status',i.rental_price AS 'Rental Price',i.sale_price AS 'Sale Price',i.created_at AS 'Added' FROM items i LEFT JOIN categories c ON c.id=i.category_id WHERE ".implode(' AND ',$w)." ORDER BY c.name,i.name");
        $stmt->execute($p); stream_csv($stmt->fetchAll(),'inventory_report_'.date('Y-m-d'));
    case 'rentals':
        $stmt=$db->prepare("SELECT t.id AS '#',u.name AS 'Customer',i.name AS 'Item',t.borrow_date AS 'Borrow Date',t.due_date AS 'Due Date',t.return_date AS 'Return Date',t.status AS 'Status',t.amount_paid AS 'Amount Paid',t.penalty_fee AS 'Penalty Fee' FROM transactions t JOIN users u ON u.id=t.customer_id JOIN items i ON i.id=t.item_id WHERE t.type='rent' AND DATE(t.created_at) BETWEEN :from AND :to ORDER BY t.created_at DESC");
        $stmt->execute([':from'=>$range['from'],':to'=>$range['to']]); stream_csv($stmt->fetchAll(),'rental_report_'.$range['from'].'_'.$range['to']);
    case 'sales':
        $stmt=$db->prepare("SELECT t.id AS '#',u.name AS 'Customer',i.name AS 'Item',c.name AS 'Category',t.amount_paid AS 'Amount Paid',t.created_at AS 'Sale Date' FROM transactions t JOIN users u ON u.id=t.customer_id JOIN items i ON i.id=t.item_id LEFT JOIN categories c ON c.id=i.category_id WHERE t.type='sale' AND t.status='completed' AND DATE(t.created_at) BETWEEN :from AND :to ORDER BY t.created_at DESC");
        $stmt->execute([':from'=>$range['from'],':to'=>$range['to']]); stream_csv($stmt->fetchAll(),'sales_report_'.$range['from'].'_'.$range['to']);
    case 'overdue':
        $stmt=$db->query("SELECT t.id AS '#',u.name AS 'Customer',u.phone AS 'Phone',i.name AS 'Item',t.borrow_date AS 'Borrow Date',t.due_date AS 'Due Date',DATEDIFF(CURDATE(),t.due_date) AS 'Days Late',(DATEDIFF(CURDATE(),t.due_date)*".PENALTY_PER_DAY.") AS 'Est. Penalty' FROM transactions t JOIN users u ON u.id=t.customer_id JOIN items i ON i.id=t.item_id WHERE t.status='overdue' ORDER BY `Days Late` DESC");
        stream_csv($stmt->fetchAll(),'overdue_report_'.date('Y-m-d'));
    case 'staff_activity':
        $stmt=$db->prepare("SELECT u.name AS 'Staff Member',COUNT(l.id) AS 'Total Actions',SUM(l.action='login') AS 'Logins',SUM(l.action='approve_txn') AS 'Approvals',SUM(l.action='reject_txn') AS 'Rejections',SUM(l.action='process_return') AS 'Returns',MAX(l.created_at) AS 'Last Active' FROM logs l JOIN users u ON u.id=l.user_id WHERE u.role IN ('admin','staff') AND DATE(l.created_at) BETWEEN :from AND :to GROUP BY u.id ORDER BY `Total Actions` DESC");
        $stmt->execute([':from'=>$range['from'],':to'=>$range['to']]); stream_csv($stmt->fetchAll(),'staff_activity_'.$range['from'].'_'.$range['to']);
    default: http_response_code(400); exit('Invalid report type.');
}
