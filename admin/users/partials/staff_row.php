<?php $row_num=isset($i)?$i+1:e($s['id']); ?>
<tr id="staff-row-<?= (int)$s['id'] ?>">
    <td><?= $row_num ?></td><td><?= e($s['name']) ?></td><td><?= e($s['email']) ?></td>
    <td><?= e($s['phone']??'—') ?></td>
    <td><span class="badge badge--<?= e($s['status']) ?>"><?= e(ucfirst($s['status'])) ?></span></td>
    <td><?= fmt_date($s['created_at']) ?></td>
    <td class="actions-cell">
        <button class="btn btn--sm btn--ghost edit-staff" data-id="<?= (int)$s['id'] ?>" data-name="<?= e($s['name']) ?>" data-email="<?= e($s['email']) ?>" data-phone="<?= e($s['phone']??'') ?>" data-status="<?= e($s['status']) ?>">Edit</button>
        <button class="btn btn--sm btn--danger delete-staff" data-id="<?= (int)$s['id'] ?>" data-name="<?= e($s['name']) ?>">Remove</button>
    </td>
</tr>
