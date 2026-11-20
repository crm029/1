<?php ob_start(); ?>
<div class="card p-4">
    <h5 class="mb-4">ویرایش تصویر</h5>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/content/gallery/edit/<?= $item['id'] ?>/submit" enctype="multipart/form-data">
        <?= Helpers::csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">عنوان</label>
                <input type="text" name="title" class="form-control" value="<?= Helpers::e($item['title'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">خدمت مرتبط</label>
                <select name="service_id" class="form-select">
                    <option value="">—</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $item['service_id'] == $s['id'] ? 'selected' : '' ?>><?= Helpers::e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">ترتیب</label>
                <input type="number" name="sort_order" class="form-control" value="<?= (int)$item['sort_order'] ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">تصویر «قبل» (در صورت تغییر)</label>
                <input type="file" name="before_image" class="form-control" accept="image/jpeg,image/png">
                <img src="<?= BASE_URL ?>/public/<?= Helpers::e($item['before_image']) ?>" style="max-width:150px;margin-top:8px;border-radius:8px;">
            </div>
            <div class="col-md-6">
                <label class="form-label">تصویر «بعد» (در صورت تغییر)</label>
                <input type="file" name="after_image" class="form-control" accept="image/jpeg,image/png">
                <img src="<?= BASE_URL ?>/public/<?= Helpers::e($item['after_image']) ?>" style="max-width:150px;margin-top:8px;border-radius:8px;">
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-brand">ذخیره تغییرات</button>
            <a href="<?= BASE_URL ?>/content/gallery" class="btn btn-light">انصراف</a>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>