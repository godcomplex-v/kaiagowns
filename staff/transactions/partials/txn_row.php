// staff/transactions/partials/txn_row.php
// Expects $r in scope (transaction row with joins)

<?php
$is_overdue = $r['status'] === 'overdue';
$days_late  = (int)($r['days_late'] ?? 0);
$row_class  = $is_overdue ? 'overdue-row' : '';
$penalty    = calc_penalty($r['due_date'] ?? date('Y-m-d'), null);
?>
<tr id="txn-row-<?= (int)$r['id'] ?>" class="<?= $row_class ?>">
    <td><?= (int)$r['id'] ?></td>
    <td>
        <div class="customer-cell">
            <span class="customer-name"><?= e($r['customer_name']) ?></span>
            <span class="customer-sub"><?= e($r['customer_phone'] ?? '') ?></span>
        </div>
    </td>
    <td><?= e($r['item_name']) ?></td>
    <td>
        <span class="badge badge--type-<?= e($r['type']) ?>">
            <?= e(ucfirst($r['type'])) ?>
        </span>
    </td>
    <td>
        <span class="badge badge--<?= e($r['status']) ?>">
            <?= e(ucfirst($r['status'])) ?>
        </span>
    </td>
    <td><?= $r['borrow_date'] ? fmt_date($r['borrow_date']) : '—' ?></td>
    <td>
        <?php if ($r['return_date']): ?>
            <span style="color:var(--staff-primary);font-size:.85rem">
                Returned <?= fmt_date($r['return_date']) ?>
            </span>
        <?php elseif ($r['due_date']): ?>
            <?= fmt_date($r['due_date']) ?>
            <?php if ($is_overdue): ?>
                <span class="days-pill due-critical" style="margin-left:.3rem">
                    +<?= $days_late ?>d
                </span>
            <?php endif; ?>
        <?php else: ?>
            —
        <?php endif; ?>
    </td>
    <td><?= fmt_money($r['amount_paid']) ?></td>
    <td>
        <?= (float)$r['penalty_fee'] > 0
            ? '<span class="penalty-amt">' . fmt_money($r['penalty_fee']) . '</span>'
            : '—' ?>
    </td>
    <td class="actions-cell">
        <?php if (in_array($r['status'], ['active','overdue'], true)): ?>
            <button class="btn btn--sm btn--primary process-return-btn"
                    data-id="<?= (int)$r['id'] ?>"
                    data-item="<?= e($r['item_name']) ?>"
                    data-customer="<?= e($r['customer_name']) ?>"
                    data-days-late="<?= $days_late ?>"
                    data-est-penalty="<?= $is_overdue ? $penalty : 0 ?>">
                Process Return
            </button>
        <?php elseif ($r['status'] === 'pending'): ?>
            <span class="badge badge--pending">Awaiting Admin</span>
        <?php else: ?>
            <span style="color:var(--text-muted);font-size:.82rem">
                <?= e(ucfirst($r['status'])) ?>
            </span>
        <?php endif; ?>
    </td>
</tr>