<?php $cur=basename($_SERVER['PHP_SELF'],'.php');
$tabs=['inventory'=>'Inventory','rentals'=>'Rentals','sales'=>'Sales','overdue'=>'Overdue','staff_activity'=>'Staff Activity'];
?><div class="tab-bar" style="margin-bottom:1.5rem">
<?php foreach($tabs as $file=>$label): ?>
    <a href="<?= e(APP_URL) ?>/admin/reports/<?= $file ?>.php" class="tab-link <?= $cur===$file?'tab-link--active':'' ?>"><?= $label ?></a>
<?php endforeach; ?>
</div>
