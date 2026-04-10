<form method="GET" class="date-filter-form">
<?php foreach($_GET as $k=>$v) if(!in_array($k,['from','to'],true)) echo '<input type="hidden" name="'.e($k).'" value="'.e($v).'">'; ?>
<label for="from" class="date-label">From</label>
<input type="date" id="from" name="from" value="<?= e($range['from']) ?>" class="date-input">
<label for="to" class="date-label">To</label>
<input type="date" id="to" name="to" value="<?= e($range['to']) ?>" class="date-input">
<button type="submit" class="btn btn--primary">Apply</button>
<a href="<?= e(basename($_SERVER['PHP_SELF'])) ?>" class="btn btn--ghost">Reset</a>
</form>
