<?php
$thumb=$item['image'] ? e(UPLOAD_URL.'items/'.$item['image']) : e(APP_URL.'/assets/images/no-image.svg');
?>
<tr id="item-row-<?= (int)$item['id'] ?>">
    <td><img src="<?= $thumb ?>" alt="<?= e($item['name']) ?>" class="inv-thumb" loading="lazy"></td>
    <td class="item-name-cell"><?= e($item['name']) ?></td>
    <td><?= e($item['category']??'—') ?></td>
    <td><?= e($item['size']??'—') ?></td>
    <td><span class="stock-badge <?= (int)$item['stock']===0?'stock-badge--zero':'' ?>"><?= (int)$item['stock'] ?></span></td>
    <td><?= $item['rental_price']>0 ? fmt_money($item['rental_price']) : '—' ?></td>
    <td><?= $item['sale_price']>0 ? fmt_money($item['sale_price']) : '—' ?></td>
    <td><span class="badge badge--<?= e($item['status']) ?>"><?= e(ucfirst($item['status'])) ?></span></td>
    <td class="actions-cell">
        <button class="btn btn--sm btn--ghost adjust-stock" data-id="<?= (int)$item['id'] ?>" data-name="<?= e($item['name']) ?>" data-stock="<?= (int)$item['stock'] ?>">Stock</button>
        <a href="<?= e(APP_URL) ?>/admin/inventory/edit.php?id=<?= (int)$item['id'] ?>" class="btn btn--sm btn--ghost">Edit</a>
        <button class="btn btn--sm btn--danger delete-item" data-id="<?= (int)$item['id'] ?>" data-name="<?= e($item['name']) ?>">Remove</button>
    </td>
</tr>
