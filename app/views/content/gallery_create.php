<?php ob_start(); ?>
<div class="card p-4">
    <h5 class="mb-4">تصویر جدید گالری</h5>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/content/gallery/create/submit" enctype="multipart/form-data">
        <?= Helpers::csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">عنوان</label>
                <input type="text" name="title" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">خدمت مرتبط</label>
                <select name="service_id" class="form-select">
                    <option value="">—</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= Helpers::e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">ترتیب</label>
                <input type="number" name="sort_order" class="form-control" value="0">
            </div>
            <div class="col-md-6">
                <label class="form-label">تصویر «قبل» *</label>
                <input type="file" name="before_image" class="form-control" accept="image/jpeg,image/png" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">تصویر «بعد» *</label>
                <input type="file" name="after_image" class="form-control" accept="image/jpeg,image/png" required>
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-brand">ذخیره</button>
            <a href="<?= BASE_URL ?>/content/gallery" class="btn btn-light">انصراف</a>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>