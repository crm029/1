<?php ob_start(); ?>
<div class="card p-4">
    <h5 class="mb-4">پرونده جدید</h5>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/clients/create/submit" enctype="multipart/form-data">
        <?= Helpers::csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">نام و نام خانوادگی *</label>
                <input type="text" name="full_name" class="form-control" required pattern="[\u0600-\u06FF\s]{2,100}">
            </div>
            <div class="col-md-6">
                <label class="form-label">جنسیت *</label>
                <select name="gender" class="form-select" required>
                    <option value="">انتخاب کنید</option>
                    <option value="male">آقا</option>
                    <option value="female">خانم</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">شماره موبایل *</label>
                <input type="text" name="mobile" class="form-control" required pattern="09[0-9]{9}">
            </div>
            <div class="col-md-6">
                <label class="form-label">شماره تماس اضطراری *</label>
                <input type="text" name="emergency_mobile" class="form-control" required pattern="09[0-9]{9}">
            </div>
            <div class="col-md-6">
                <label class="form-label">تلفن منزل *</label>
                <input type="text" name="home_phone" class="form-control" required pattern="[0-9]{8,15}">
            </div>
            <div class="col-md-12">
                <label class="form-label">آدرس محل سکونت *</label>
                <textarea name="address" class="form-control" rows="2" required></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">تصویر روی فرم اطلاعات *</label>
                <input type="file" name="id_card_front" class="form-control" accept="image/jpeg,image/png" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">تصویر پشت فرم اطلاعات *</label>
                <input type="file" name="id_card_back" class="form-control" accept="image/jpeg,image/png" required>
            </div>
        </div>

        <div class="mt-4">
            <button class="btn btn-brand">ایجاد پرونده</button>
            <a href="<?= BASE_URL ?>/clients" class="btn btn-light">انصراف</a>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>