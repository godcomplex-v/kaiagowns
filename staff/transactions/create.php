<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_guard.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/log_activity.php';

session_guard('staff');

$db         = get_db();
$errors     = [];
$success    = false;
$old        = [];

// Load customers and available items for form dropdowns
$customers = $db->query(
    "SELECT id, name, phone FROM users
     WHERE role='customer' AND status='active'
     ORDER BY name"
)->fetchAll();

$items = $db->query(
    "SELECT i.id, i.name, i.size, i.rental_price, i.sale_price,
            i.stock, c.name AS category
     FROM items i
     LEFT JOIN categories c ON c.id = i.category_id
     WHERE i.status = 'available' AND i.stock > 0
     ORDER BY i.name"
)->fetchAll();

$default_days = (int)(setting('rental_days_default') ?: 7);

if (is_post()) {
    $old = [
        'customer_id'  => (int)post('customer_id'),
        'item_id'      => (int)post('item_id'),
        'type'         => post('type'),
        'borrow_date'  => post('borrow_date'),
        'due_date'     => post('due_date'),
        'amount_paid'  => post('amount_paid'),
        'notes'        => post('notes'),
    ];

    // ── Validation ───────────────────────────────────────────────
    if ($old['customer_id'] <= 0) $errors['customer'] = 'Please select a customer.';
    if ($old['item_id']     <= 0) $errors['item']     = 'Please select an item.';
    if (!in_array($old['type'], ['rent','sale'], true))
                                  $errors['type']     = 'Select a transaction type.';
    if (!is_numeric($old['amount_paid']) || (float)$old['amount_paid'] < 0)
                                  $errors['amount']   = 'Enter a valid amount paid.';
    if ($old['type'] === 'rent') {
        if (!$old['borrow_date']) $errors['borrow'] = 'Borrow date is required.';
        if (!$old['due_date'])    $errors['due']    = 'Due date is required.';
        if ($old['borrow_date'] && $old['due_date']
            && $old['due_date'] < $old['borrow_date'])
                                  $errors['due']    = 'Due date must be after borrow date.';
    }

    // Verify item still in stock
    if (empty($errors)) {
        $item_check = $db->prepare(
            "SELECT id, stock FROM items WHERE id=:id AND status='available' LIMIT 1"
        );
        $item_check->execute([':id' => $old['item_id']]);
        $item_row = $item_check->fetch();
        if (!$item_row || (int)$item_row['stock'] < 1) {
            $errors['item'] = 'This item is no longer available.';
        }
    }

    if (empty($errors)) {
        $stmt = $db->prepare(
            "INSERT INTO transactions
             (customer_id, item_id, staff_id, type, status,
              borrow_date, due_date, amount_paid, notes)
             VALUES
             (:cid, :iid, :sid, :type, 'pending',
              :borrow, :due, :amt, :notes)"
        );
        $stmt->execute([
            ':cid'   => $old['customer_id'],
            ':iid'   => $old['item_id'],
            ':sid'   => $_SESSION['user_id'],
            ':type'  => $old['type'],
            ':borrow'=> $old['type']==='rent' ? $old['borrow_date'] : null,
            ':due'   => $old['type']==='rent' ? $old['due_date']    : null,
            ':amt'   => (float)$old['amount_paid'],
            ':notes' => $old['notes'] ?: null,
        ]);
        $new_id = (int)$db->lastInsertId();
        log_activity('create_request',
            "TXN #{$new_id} type={$old['type']} staff created for customer_id={$old['customer_id']}");
        $success = true;
        $old     = [];  // reset form
    }
}

$active_nav = 'create';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Request — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/base.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/staff.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/tables.css">
    <link rel="stylesheet" href="<?= e(APP_URL) ?>/assets/css/staff_dash.css">
</head>
<body>
<?php require __DIR__ . '/../partials/sidebar.php'; ?>
<main class="main-content">
    <header class="topbar">
        <h2>New Request</h2>
        <span>Welcome, <?= e($_SESSION['name']) ?></span>
    </header>
    <section class="page-body">
        <div class="form-card" style="max-width:700px">
            <div class="form-card__header">
                <a href="<?= e(APP_URL) ?>/staff/transactions/index.php"
                   class="btn btn--ghost btn--sm">← Back</a>
                <h3>Create Borrow / Sale Request</h3>
            </div>

            <?php if ($success): ?>
                <div class="alert alert--success" style="margin:1rem 1.5rem 0">
                    Request created successfully — awaiting admin approval.
                    <a href="<?= e(APP_URL) ?>/staff/transactions/index.php">View all transactions →</a>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert--error" style="margin:1rem 1.5rem 0">
                    Please fix the errors below.
                </div>
            <?php endif; ?>

            <form method="POST" id="createForm" novalidate style="padding:1.5rem">
                <div class="form-row">
                    <div class="form-group">
                        <label for="customer_id">Customer <span class="required">*</span></label>
                        <select id="customer_id" name="customer_id" required>
                            <option value="">Select customer</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= (int)$c['id'] ?>"
                                    <?= (int)($old['customer_id']??0)===(int)$c['id']?'selected':'' ?>>
                                    <?= e($c['name']) ?>
                                    <?= $c['phone'] ? '(' . e($c['phone']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="field-error"><?= e($errors['customer'] ?? '') ?></span>
                    </div>
                    <div class="form-group">
                        <label for="type">Type <span class="required">*</span></label>
                        <select id="type" name="type" required>
                            <option value="">Select type</option>
                            <option value="rent" <?= ($old['type']??'')==='rent'?'selected':'' ?>>
                                Rent / Borrow
                            </option>
                            <option value="sale" <?= ($old['type']??'')==='sale'?'selected':'' ?>>
                                Sale / Purchase
                            </option>
                        </select>
                        <span class="field-error"><?= e($errors['type'] ?? '') ?></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="item_id">Item <span class="required">*</span></label>
                    <select id="item_id" name="item_id" required>
                        <option value="">Select item</option>
                        <?php foreach ($items as $it): ?>
                            <option value="<?= (int)$it['id'] ?>"
                                    data-rental="<?= (float)$it['rental_price'] ?>"
                                    data-sale="<?= (float)$it['sale_price'] ?>"
                                <?= (int)($old['item_id']??0)===(int)$it['id']?'selected':'' ?>>
                                <?= e($it['name']) ?>
                                <?= $it['size'] ? '— '.e($it['size']) : '' ?>
                                (<?= e($it['category'] ?? '') ?>)
                                — Stock: <?= (int)$it['stock'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="field-error"><?= e($errors['item'] ?? '') ?></span>
                </div>

                <!-- Rental date fields (shown only when type=rent) -->
                <div id="rentFields" style="display:none">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="borrow_date">Borrow Date <span class="required">*</span></label>
                            <input type="date" id="borrow_date" name="borrow_date"
                                   value="<?= e($old['borrow_date'] ?? date('Y-m-d')) ?>">
                            <span class="field-error"><?= e($errors['borrow'] ?? '') ?></span>
                        </div>
                        <div class="form-group">
                            <label for="due_date">Due Date <span class="required">*</span></label>
                            <input type="date" id="due_date" name="due_date"
                                   value="<?= e($old['due_date'] ?? '') ?>">
                            <span class="field-error"><?= e($errors['due'] ?? '') ?></span>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="amount_paid">
                            Amount Paid (₱) <span class="required">*</span>
                            <span id="suggestedPrice" class="field-hint"></span>
                        </label>
                        <input type="number" id="amount_paid" name="amount_paid"
                               value="<?= e((string)($old['amount_paid'] ?? '0.00')) ?>"
                               min="0" step="0.01" required>
                        <span class="field-error"><?= e($errors['amount'] ?? '') ?></span>
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes (optional)</label>
                        <textarea id="notes" name="notes" rows="3"
                                  maxlength="500"><?= e($old['notes'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="<?= e(APP_URL) ?>/staff/transactions/index.php"
                       class="btn btn--ghost">Cancel</a>
                    <button type="submit" class="btn btn--primary">Submit Request</button>
                </div>
            </form>
        </div>
    </section>
</main>
<script>
    const APP_URL     = '<?= e(APP_URL) ?>';
    const DEFAULT_DAYS = <?= $default_days ?>;
</script>
<script src="<?= e(APP_URL) ?>/assets/js/staff_create.js"></script>
</body>
</html>