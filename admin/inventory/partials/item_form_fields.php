<?php $v=fn(string $k,$d='')=>$old[$k]??$d; ?>
<div class="item-form-grid">
<div class="item-form-fields">
    <div class="form-group"><label for="name">Item Name <span class="required">*</span></label>
        <input type="text" id="name" name="name" value="<?= e((string)$v('name')) ?>" maxlength="200" required>
        <span class="field-error"><?= e($errors['name']??'') ?></span></div>
    <div class="form-row">
        <div class="form-group"><label for="category_id">Category <span class="required">*</span></label>
            <select id="category_id" name="category_id" required><option value="">Select category</option>
                <?php foreach($cats as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= (int)$v('category_id')===(int)$cat['id']?'selected':'' ?>><?= e($cat['name']) ?></option><?php endforeach; ?>
            </select><span class="field-error"><?= e($errors['category']??'') ?></span></div>
        <div class="form-group"><label for="size">Size</label>
            <input type="text" id="size" name="size" value="<?= e((string)$v('size')) ?>" maxlength="50" placeholder="S, M, L, Free Size"></div>
    </div>
    <div class="form-row">
        <div class="form-group"><label for="rental_price">Rental Price (₱) <span class="required">*</span></label>
            <input type="number" id="rental_price" name="rental_price" value="<?= e((string)$v('rental_price','0.00')) ?>" min="0" step="0.01" required>
            <span class="field-error"><?= e($errors['rental']??'') ?></span></div>
        <div class="form-group"><label for="sale_price">Sale Price (₱) <span class="required">*</span></label>
            <input type="number" id="sale_price" name="sale_price" value="<?= e((string)$v('sale_price','0.00')) ?>" min="0" step="0.01" required>
            <span class="field-error"><?= e($errors['sale']??'') ?></span></div>
    </div>
    <div class="form-row">
        <?php if(!isset($item)): ?>
        <div class="form-group"><label for="stock">Initial Stock <span class="required">*</span></label>
            <input type="number" id="stock" name="stock" value="<?= e((string)$v('stock','0')) ?>" min="0" step="1" required>
            <span class="field-error"><?= e($errors['stock']??'') ?></span></div>
        <?php else: ?>
        <div class="form-group"><label>Current Stock</label>
            <input type="text" value="<?= (int)($old['stock']??0) ?>" disabled class="input--readonly">
            <span class="field-hint">Use Stock Adjustment to change stock.</span></div>
        <?php endif; ?>
        <div class="form-group"><label for="status">Status <span class="required">*</span></label>
            <select id="status" name="status" required>
                <option value="available" <?= $v('status','available')==='available'?'selected':'' ?>>Available</option>
                <option value="reserved"  <?= $v('status')==='reserved' ?'selected':'' ?>>Reserved</option>
                <option value="damaged"   <?= $v('status')==='damaged'  ?'selected':'' ?>>Damaged</option>
                <option value="retired"   <?= $v('status')==='retired'  ?'selected':'' ?>>Retired</option>
            </select></div>
    </div>
    <div class="form-group"><label for="description">Description</label>
        <textarea id="description" name="description" rows="4" maxlength="2000" placeholder="Fabric, colour, occasion…"><?= e((string)$v('description')) ?></textarea></div>
</div>
<div class="item-form-image">
    <label class="img-upload-label">Item Image
        <div class="img-drop-zone" id="imgDropZone">
            <?php $ps=$old['image']??null; ?>
            <img id="imgPreview" src="<?= $ps?e(UPLOAD_URL.'items/'.$ps):e(APP_URL.'/assets/images/no-image.svg') ?>" alt="Preview" class="img-preview <?= $ps?'img-preview--loaded':'' ?>">
            <span class="img-drop-hint" id="imgDropHint"><?= $ps?'Click or drop to replace':'Click or drag image here' ?></span>
        </div>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" class="img-file-input">
    </label>
    <span class="field-error"><?= e($errors['image']??'') ?></span>
    <p class="field-hint">JPG, PNG or WebP · max 2 MB</p>
</div>
</div>
