<?php $row_num=isset($i)?$i+1:e($c['id']); ?>
<tr id="customer-row-<?= (int)$c['id'] ?>">
    <td><?= $row_num ?></td><td><?= e($c['name']) ?></td><td><?= e($c['email']) ?></td>
    <td><?= e($c['phone']??'—') ?></td>
    <td><span class="badge badge--<?= e($c['status']) ?>"><?= e(ucfirst($c['status'])) ?></span></td>
    <td><?= fmt_date($c['created_at']) ?></td>
    <td><?php if($c['status']==='active'): ?>
        <button class="btn btn--sm btn--danger toggle-status" data-id="<?= (int)$c['id'] ?>" data-action="deactivate">Deactivate</button>
        <?php else: ?>
        <button class="btn btn--sm btn--success toggle-status" data-id="<?= (int)$c['id'] ?>" data-action="activate">Activate</button>
        <?php endif; ?></td>
</tr>
