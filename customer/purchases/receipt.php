<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_guard.php';
require_once __DIR__ . '/../../includes/functions.php';

session_guard('customer');

$db  = get_db();
$uid = (int)$_SESSION['user_id'];
$id  = (int)get_param('id');

if ($id <= 0) redirect(APP_URL . '/customer/purchases/index.php');

// Strict ownership check
$txn = $db->prepare(
    "SELECT t.*, i.name AS item_name, c.name AS category,
            u.name AS customer_name, u.email, u.phone, u.address
     FROM transactions t
     JOIN items i ON i.id = t.item_id
     LEFT JOIN categories c ON c.id = i.category_id
     JOIN users u ON u.id = t.customer_id
     WHERE t.id = :id AND t.customer_id = :uid
       AND t.type = 'sale' AND t.status = 'completed'
     LIMIT 1"
);
$txn->execute([':id' => $id, ':uid' => $uid]);
$txn = $txn->fetch();
if (!$txn) redirect(APP_URL . '/customer/purchases/index.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?= (int)$txn['id'] ?> — <?= e(APP_NAME) ?></title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; color: #1a1a2e;
               padding: 2.5rem; max-width: 600px; margin: 0 auto; }
        .receipt-header { text-align: center; margin-bottom: 2rem; border-bottom: 2px solid #c9a84c; padding-bottom: 1rem; }
        .receipt-header h1 { font-size: 1.6rem; color: #0d1b4b; }
        .receipt-header p  { color: #6b7280; font-size: .875rem; }
        .section { margin-bottom: 1.5rem; }
        .section-title { font-size: .8rem; font-weight: 700; color: #6b7280;
                         text-transform: uppercase; letter-spacing: .05em;
                         margin-bottom: .5rem; }
        .detail-row { display: flex; justify-content: space-between;
                      padding: .4rem 0; border-bottom: 1px solid #f1f5f9; font-size: .9rem; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #6b7280; }
        .detail-value { font-weight: 500; }
        .total-row { display: flex; justify-content: space-between;
                     padding: .75rem 0; font-size: 1.1rem; font-weight: 700;
                     border-top: 2px solid #0d1b4b; margin-top: .5rem; }
        .receipt-footer { text-align: center; color: #6b7280; font-size: .8rem;
                          margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; }
        .receipt-no { background: #f8faff; border: 1px solid #e2e8f0;
                      border-radius: 8px; padding: .5rem 1rem;
                      display: inline-block; font-family: monospace;
                      font-size: .9rem; margin-bottom: 1rem; }
        @media print {
            .no-print { display: none; }
            body { padding: 1rem; }
        }
    </style>
</head>
<body>
    <div class="receipt-header">
        <h1><?= e(APP_NAME) ?></h1>
        <p>Sales Receipt</p>
        <div class="receipt-no">Receipt #<?= str_pad((string)$txn['id'], 6, '0', STR_PAD_LEFT) ?></div>
    </div>

    <div class="section">
        <div class="section-title">Customer</div>
        <div class="detail-row">
            <span class="detail-label">Name</span>
            <span class="detail-value"><?= e($txn['customer_name']) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Email</span>
            <span class="detail-value"><?= e($txn['email']) ?></span>
        </div>
        <?php if ($txn['phone']): ?>
        <div class="detail-row">
            <span class="detail-label">Phone</span>
            <span class="detail-value"><?= e($txn['phone']) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="section">
        <div class="section-title">Item Purchased</div>
        <div class="detail-row">
            <span class="detail-label">Item</span>
            <span class="detail-value"><?= e($txn['item_name']) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Category</span>
            <span class="detail-value"><?= e($txn['category'] ?? '—') ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Sale Date</span>
            <span class="detail-value"><?= fmt_date($txn['created_at'], 'F d, Y') ?></span>
        </div>
    </div>

    <div class="section">
        <div class="total-row">
            <span>Total Paid</span>
            <span><?= fmt_money($txn['amount_paid']) ?></span>
        </div>
    </div>

    <div class="receipt-footer">
        <p>Thank you for your purchase!</p>
        <p style="margin-top:.3rem">
            For inquiries: <?= e(setting('contact_email','admin@kaiagowns.com')) ?>
        </p>
    </div>

    <div class="no-print" style="text-align:center;margin-top:2rem">
        <button onclick="window.print()"
                style="padding:.6rem 1.5rem;background:#0d1b4b;color:#fff;
                       border:none;border-radius:6px;cursor:pointer;font-size:.9rem">
            🖨 Print Receipt
        </button>
        <button onclick="window.close()"
                style="padding:.6rem 1.5rem;background:transparent;
                       border:1px solid #e2e8f0;border-radius:6px;
                       cursor:pointer;font-size:.9rem;margin-left:.5rem">
            Close
        </button>
    </div>
</body>
</html>