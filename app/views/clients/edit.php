<?php ob_start(); ?>
<div class="card p-4">
    <h5 class="mb-4">ویرایش پرونده <span class="badge bg-dark"><?= Helpers::e($client['file_number']) ?></span></h5>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/clients/edit/<?= $client['id'] ?>/submit" enctype="multipart/form-data">
        <?= Helpers::csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">نام و نام خانوادگی *</label>
                <input type="text" name="full_name" class="form-control" required value="<?= Helpers::e($client['full_name']) ?>" pattern="[\u0600-\u06FF\s]{2,100}">
            </div>
            <div class="col-md-6">
                <label class="form-label">جنسیت *</label>
                <select name="gender" class="form-select" required>
                    <option value="male" <?= $client['gender'] === 'male' ? 'selected' : '' ?>>آقا</option>
                    <option value="female" <?= $client['gender'] === 'female' ? 'selected' : '' ?>>خانم</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">شماره موبایل *</label>
                <input type="text" name="mobile" class="form-control" required value="<?= Helpers::e($client['mobile']) ?>" pattern="09[0-9]{9}">
            </div>
            <div class="col-md-6">
                <label class="form-label">شماره تماس اضطراری *</label>
                <input type="text" name="emergency_mobile" class="form-control" required value="<?= Helpers::e($client['emergency_mobile']) ?>" pattern="09[0-9]{9}">
            </div>
            <div class="col-md-6">
                <label class="form-label">تلفن منزل *</label>
                <input type="text" name="home_phone" class="form-control" required value="<?= Helpers::e($client['home_phone']) ?>" pattern="[0-9]{8,15}">
            </div>
            <div class="col-md-12">
                <label class="form-label">آدرس محل سکونت *</label>
                <textarea name="address" class="form-control" rows="2" required><?= Helpers::e($client['address']) ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">تصویر روی فرم (اختیاری - در صورت تغییر)</label>
                <input type="file" name="id_card_front" class="form-control" accept="image/jpeg,image/png">
                <?php if ($client['id_card_front']): ?>
                    <img src="<?= BASE_URL ?>/public/<?= Helpers::e($client['id_card_front']) ?>" style="max-width:120px;margin-top:8px;border-radius:8px;">
                <?php endif; ?>
            </div>
            <div class="col-md-6">
                <label class="form-label">تصویر پشت فرم (اختیاری - در صورت تغییر)</label>
                <input type="file" name="id_card_back" class="form-control" accept="image/jpeg,image/png">
                <?php if ($client['id_card_back']): ?>
                    <img src="<?= BASE_URL ?>/public/<?= Helpers::e($client['id_card_back']) ?>" style="max-width:120px;margin-top:8px;border-radius:8px;">
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4">
            <button class="btn btn-brand">ذخیره تغییرات</button>
            <a href="<?= BASE_URL ?>/clients" class="btn btn-light">انصراف</a>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>