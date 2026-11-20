<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">دسته‌بندی جدید</h5>
        <a href="<?= BASE_URL ?>/service-categories" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right"></i> بازگشت
        </a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/service-categories/create/submit">
        <?= Helpers::csrfField() ?>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">نام دسته <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="<?= Helpers::e($_POST['name'] ?? '') ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">آیکون (کلاس FontAwesome)</label>
                <input type="text" name="icon" class="form-control" value="<?= Helpers::e($_POST['icon'] ?? 'fa-solid fa-spa') ?>" placeholder="مثال: fa-solid fa-spa">
                <small class="text-muted">پیش‌فرض: <code>fa-solid fa-spa</code></small>
            </div>

            <div class="col-md-6">
                <label class="form-label">رنگ</label>
                <div class="d-flex gap-2 align-items-center">
                    <input type="color" name="color" class="form-control form-control-color" value="<?= Helpers::e($_POST['color'] ?? '#D1B1B0') ?>">
                    <small class="text-muted">پیش‌فرض: رنگ برند (#D1B1B0)</small>
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label">ترتیب نمایش</label>
                <input type="number" name="sort_order" class="form-control" value="<?= Helpers::e($_POST['sort_order'] ?? 0) ?>" min="0">
                <small class="text-muted">عدد کمتر = نمایش بالاتر</small>
            </div>

            <div class="col-12">
                <label class="form-label">توضیحات (اختیاری)</label>
                <textarea name="description" class="form-control" rows="3"><?= Helpers::e($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= (!isset($_POST['name']) || isset($_POST['is_active'])) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">فعال باشد</label>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> ذخیره</button>
            <a href="<?= BASE_URL ?>/service-categories" class="btn btn-outline-secondary">انصراف</a>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>