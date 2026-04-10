<form method="GET" class="toolbar-filters" id="filterForm">
    <input type="search" name="search" placeholder="Search customer or item…" value="<?= e($search) ?>" class="toolbar-search">
    <select name="type" class="toolbar-select"><option value="">Rent &amp; Sale</option>
        <option value="rent" <?= ($type_filter??'')==='rent'?'selected':'' ?>>Rent only</option>
        <option value="sale" <?= ($type_filter??'')==='sale'?'selected':'' ?>>Sale only</option>
    </select>
    <button type="submit" class="btn btn--primary">Filter</button>
    <?php if($search||($type_filter??'')): ?><a href="<?= e($_SERVER['PHP_SELF']) ?>" class="btn btn--ghost">Clear</a><?php endif; ?>
</form>
