<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">ویرایش قالب</h5>
        <a href="<?= BASE_URL ?>/notification-templates" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right"></i> بازگشت
        </a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/notification-templates/edit/<?= $template['id'] ?>/submit">
        <?= Helpers::csrfField() ?>

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">کد</label>
                <input type="text" class="form-control" value="<?= Helpers::e($template['code']) ?>" disabled>
                <small class="text-muted">کد قابل ویرایش نیست</small>
            </div>

            <div class="col-md-6">
                <label class="form-label">عنوان <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="<?= Helpers::e($template['title']) ?>" required>
            </div>

            <div class="col-12">
                <label class="form-label">توضیحات (اختیاری)</label>
                <input type="text" name="description" class="form-control" value="<?= Helpers::e($template['description']) ?>">
            </div>

            <div class="col-12">
                <div class="alert alert-info small">
                    <b>Placeholderهای مجاز برای این قالب:</b><br>
                    <?php foreach (($placeholders[$template['code']] ?? []) as $ph): ?>
                        <code class="me-1">{<?= $ph ?>}</code>
                    <?php endforeach; ?>
                    <br><br>
                    <b>نمونه:</b> سلام {client_name}، نوبت شما با کد {booking_code} تایید شد.
                </div>
            </div>

            <div class="col-12">
                <label class="form-label">متن پیام <span class="text-danger">*</span></label>
                <textarea name="text" class="form-control" rows="8" required><?= Helpers::e($template['text']) ?></textarea>
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= $template['is_active'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">فعال باشد</label>
                </div>
            </div>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg"></i> ذخیره تغییرات</button>
            <a href="<?= BASE_URL ?>/notification-templates" class="btn btn-outline-secondary">انصراف</a>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>