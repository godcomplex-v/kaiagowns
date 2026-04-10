<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_guard.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/log_activity.php';

session_guard('customer');

$db  = get_db();
$id  = (int)get_param('id');
$uid = (int)$_SESSION['user_id'];

if ($id <= 0) redirect(APP_URL . '/customer/catalog/index.php');

$item = $db->prepare(
    "SELECT i.*, c.name AS category
     FROM items i
     LEFT JOIN categories c ON c.id = i.category_id
     WHERE i.id = :id AND i.status != 'retired' LIMIT 1"
);
$item->execute([':id' => $id]);
$item = $item->fetch();
if (!$item) redirect(APP_URL . '/customer/catalog/index.php');

// Blocked date ranges for calendar
$blocked = $db->prepare(
    "SELECT blocked_from, blocked_to FROM availability
     WHERE item_id = :id AND blocked_to >= CURDATE()
     UNION
     SELECT borrow_date AS blocked_from, due_date AS blocked_to
     FROM transactions
     WHERE item_id = :id2
       AND status IN ('active','overdue','approved')
       AND due_date >= CURDATE()"
);
$blocked->execute([':id' => $id, ':id2' => $id]);
$blocked_ranges = $blocked->fetchAll();

// Handle request form submission
$req_error   = '';
$req_success = '';

if (is_post()) {
    $type       = post('type');
    $borrow     = post('borrow_date');
    $due        = post('due_date');
    $amount     = post('amount_paid');

    if (!in_array($type, ['rent','sale'], true)) {
        $req_error = 'Invalid request type.';
    } elseif ($type === 'rent' && (!$borrow || !$due)) {
        $req_error = 'Please select borrow and due dates for a rental.';
    } elseif ($type === 'rent' && $due < $borrow) {
        $req_error = 'Due date must be after borrow date.';
    } elseif (!is_numeric($amount) || (float)$amount < 0) {
        $req_error = 'Please enter a valid amount.';
    } elseif ($item['stock'] < 1) {
        $req_error = 'This item is currently out of stock.';
    } else {
        $db->prepare(
            "INSERT INTO transactions
             (customer_id, item_id, type, status, borrow_date, due_date, amount_paid)
             VALUES (:cid, :iid, :type, 'pending', :borrow, :due, :amt)"
        )->execute([
            ':cid'    => $uid,
            ':iid'    => $id,
            ':type'   => $type,
            ':borrow' => $type==='rent' ? $borrow : null,
            ':due'    => $type==='rent' ? $due    : null,
            ':amt'    => (float)$amount,
        ]);
        $req_success = $type === 'rent'
            ? 'Rental request submitted! We will notify you once approved.'
            : 'Purchase request submitted! We will notify you once processed.';

        log_activity('submit_request',
            "Customer uid={$uid} item_id={$id} type={$type}");
    }
}

$default_days = (int)(setting('rental_days_default') ?: 7);
$active_nav   = 'catalog';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($item['name']) ?> — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/customer.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/customer_dash.css">
</head>
<body>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>
<main class="main-content">
    <header class="topbar">
        <a href="index.php" class="btn btn--ghost btn--sm">← Back to Catalog</a>
    </header>
    <section class="page-body">

        <div class="item-detail-grid">

            <!-- Left: image -->
            <div class="item-detail-img">
                <img src="<?= $item['image']
                    ? e(UPLOAD_URL.'items/'.$item['image'])
                    : e(APP_URL.'/assets/images/no-image.svg') ?>"
                     alt="<?= e($item['name']) ?>">
            </div>

            <!-- Right: info + request form -->
            <div class="item-detail-info">
                <span class="item-category-tag"><?= e($item['category'] ?? '') ?></span>
                <h1 class="item-title"><?= e($item['name']) ?></h1>

                <?php if ($item['size']): ?>
                    <p class="item-size">Size: <strong><?= e($item['size']) ?></strong></p>
                <?php endif; ?>

                <div class="item-price-row">
                    <?php if ($item['rental_price'] > 0): ?>
                        <div class="item-price-block">
                            <span class="price-label">Rental</span>
                            <span class="price-value"><?= fmt_money($item['rental_price']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($item['sale_price'] > 0): ?>
                        <div class="item-price-block">
                            <span class="price-label">Sale</span>
                            <span class="price-value"><?= fmt_money($item['sale_price']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="item-stock-row">
                    <?php if ((int)$item['stock'] > 0): ?>
                        <span class="stock-badge"><?= (int)$item['stock'] ?> in stock</span>
                    <?php else: ?>
                        <span class="stock-badge stock-badge--zero">Out of stock</span>
                    <?php endif; ?>
                    <span class="badge badge--<?= e($item['status']) ?>">
                        <?= e(ucfirst($item['status'])) ?>
                    </span>
                </div>

                <?php if ($item['description']): ?>
                    <p class="item-description"><?= nl2br(e($item['description'])) ?></p>
                <?php endif; ?>

                <!-- Availability calendar -->
                <div class="avail-calendar-wrap">
                    <h3 class="avail-title">Availability</h3>
                    <div id="availCalendar" class="avail-calendar"></div>
                    <div class="avail-legend">
                        <span class="legend-dot legend-dot--free"></span> Available
                        <span class="legend-dot legend-dot--blocked"></span> Blocked
                        <span class="legend-dot legend-dot--today"></span> Today
                    </div>
                </div>

                <!-- Request form -->
                <?php if ((int)$item['stock'] > 0): ?>
                <div class="request-form-wrap">
                    <h3 class="request-title">Make a Request</h3>

                    <?php if ($req_success !== ''): ?>
                        <div class="alert alert--success"><?= e($req_success) ?></div>
                    <?php endif; ?>
                    <?php if ($req_error !== ''): ?>
                        <div class="alert alert--error"><?= e($req_error) ?></div>
                    <?php endif; ?>

                    <form method="POST" id="requestForm" novalidate>
                        <div class="form-group">
                            <label>Request Type</label>
                            <div class="type-toggle">
                                <?php if ($item['rental_price'] > 0): ?>
                                <label class="type-opt">
                                    <input type="radio" name="type" value="rent"
                                           <?= (post('type')||'rent')==='rent'?'checked':'' ?>>
                                    <span>Rent — <?= fmt_money($item['rental_price']) ?></span>
                                </label>
                                <?php endif; ?>
                                <?php if ($item['sale_price'] > 0): ?>
                                <label class="type-opt">
                                    <input type="radio" name="type" value="sale"
                                           <?= post('type')==='sale'?'checked':'' ?>>
                                    <span>Buy — <?= fmt_money($item['sale_price']) ?></span>
                                </label>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div id="rentDates" class="form-row">
                            <div class="form-group">
                                <label for="borrow_date">Borrow Date</label>
                                <input type="date" id="borrow_date" name="borrow_date"
                                       value="<?= e(post('borrow_date') ?: date('Y-m-d')) ?>"
                                       min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="form-group">
                                <label for="due_date">Return By</label>
                                <input type="date" id="due_date" name="due_date"
                                       value="<?= e(post('due_date') ?: '') ?>"
                                       min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="amount_paid">
                                Amount to Pay (₱)
                                <span id="amtHint" class="field-hint"></span>
                            </label>
                            <input type="number" id="amount_paid" name="amount_paid"
                                   value="<?= e(post('amount_paid') ?: number_format($item['rental_price'], 2)) ?>"
                                   min="0" step="0.01" required>
                        </div>

                        <div class="form-actions" style="justify-content:flex-start">
                            <button type="submit" class="btn btn--primary btn--full">
                                Submit Request
                            </button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                    <div class="alert alert--warning" style="margin-top:1rem">
                        This item is currently out of stock. Check back later.
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </section>
</main>

<script>
const BLOCKED_RANGES = <?= json_encode($blocked_ranges) ?>;
const RENTAL_PRICE   = <?= (float)$item['rental_price'] ?>;
const SALE_PRICE     = <?= (float)$item['sale_price'] ?>;
const DEFAULT_DAYS   = <?= $default_days ?>;
const APP_URL        = '<?= e(APP_URL) ?>';
</script>
<script src="<?= e(APP_URL) ?>/assets/js/item_detail.js"></script>
</body>
</html>