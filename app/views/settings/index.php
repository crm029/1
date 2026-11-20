<?php ob_start(); ?>
<div class="card p-4">
    <h5 class="mb-4">تنظیمات سیستم</h5>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/settings/save" enctype="multipart/form-data">
        <?= Helpers::csrfField() ?>

        <h6 class="mb-3 mt-3" style="color:<?= BRAND_COLOR ?>;">اطلاعات کلینیک</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">نام کلینیک</label>
                <input type="text" name="clinic_name" class="form-control" value="<?= Helpers::e($settings['clinic_name'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">تلفن کلینیک</label>
                <input type="text" name="clinic_phone" class="form-control" value="<?= Helpers::e($settings['clinic_phone'] ?? '') ?>">
            </div>
            <div class="col-md-12">
                <label class="form-label">آدرس</label>
                <textarea name="clinic_address" class="form-control" rows="2"><?= Helpers::e($settings['clinic_address'] ?? '') ?></textarea>
            </div>
            <?php if ($is_super): ?>
                <div class="col-md-12">
                    <label class="form-label">لوگوی کلینیک (فقط سوپر ادمین)</label>
                    <input type="file" name="clinic_logo" class="form-control" accept="image/jpeg,image/png,image/svg+xml">
                    <?php if (!empty($settings['clinic_logo'])): ?>
                        <div class="mt-2">
                            <img src="<?= BASE_URL ?>/public/<?= Helpers::e($settings['clinic_logo']) ?>" style="max-height:80px;border-radius:8px;">
                        </div>
                    <?php endif; ?>
                    <small class="text-muted">فرمت jpg/png/svg، حداکثر ۲ مگابایت</small>
                </div>
            <?php endif; ?>
        </div>

        <h6 class="mb-3 mt-4" style="color:<?= BRAND_COLOR ?>;">ساعت کاری و بیعانه</h6>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">ساعت شروع کار</label>
                <input type="time" name="work_start_time" class="form-control" value="<?= Helpers::e($settings['work_start_time'] ?? '09:00') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">ساعت پایان کار</label>
                <input type="time" name="work_end_time" class="form-control" value="<?= Helpers::e($settings['work_end_time'] ?? '21:00') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">مبلغ بیعانه (تومان)</label>
                <input type="number" name="deposit_amount" class="form-control" min="0" value="<?= Helpers::e($settings['deposit_amount'] ?? '200000') ?>">
            </div>
        </div>

        <h6 class="mb-3 mt-4" style="color:<?= BRAND_COLOR ?>;">اطلاعات کارت برای بیعانه</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">شماره کارت</label>
                <input type="text" name="card_number" class="form-control" value="<?= Helpers::e($settings['card_number'] ?? '') ?>" pattern="[0-9]{16}" maxlength="16" placeholder="۱۶ رقم بدون خط تیره">
            </div>
            <div class="col-md-6">
                <label class="form-label">نام صاحب کارت</label>
                <input type="text" name="card_holder" class="form-control" value="<?= Helpers::e($settings['card_holder'] ?? '') ?>">
            </div>
        </div>

        <?php if ($is_super): ?>
        <h6 class="mb-3 mt-4" style="color:<?= BRAND_COLOR ?>;">درگاه‌ها (فقط سوپر ادمین)</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="sms_enabled" id="sms_enabled" <?= ($settings['sms_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="sms_enabled">درگاه پیامک فعال</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="bale_enabled" id="bale_enabled" <?= ($settings['bale_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="bale_enabled">ربات بله فعال</label>
                </div>
            </div>
        </div>
        <h6 class="mb-3 mt-4" style="color:<?= BRAND_COLOR ?>;">تنظیمات پیامک ۰۰۹۸ (فقط سوپر ادمین)</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">شماره فرستنده ۰۰۹۸</label>
                <input type="text" name="sms_sender" class="form-control" value="<?= Helpers::e($settings['sms_sender'] ?? '') ?>" placeholder="مثل 30005883803803">
            </div>
            <div class="col-md-6">
                <label class="form-label">نام کاربری پنل پیامک</label>
                <input type="text" name="sms_username" class="form-control" value="<?= Helpers::e($settings['sms_username'] ?? '') ?>" placeholder="مثل bsms5492">
            </div>
            <div class="col-md-6">
                <label class="form-label">رمز عبور پنل پیامک</label>
                <input type="text" name="sms_password" class="form-control" value="<?= Helpers::e($settings['sms_password'] ?? '') ?>">
                <small class="text-muted">رمز وب‌سرویس (نه رمز ورود)</small>
            </div>
        </div>

        <h6 class="mb-3 mt-4" style="color:<?= BRAND_COLOR ?>;">تنظیمات ربات بله (فقط سوپر ادمین)</h6>
        <div class="row g-3">
            <div class="col-md-12">
                <label class="form-label">توکن ربات بله</label>
                <input type="text" name="bale_token" class="form-control" value="<?= Helpers::e($settings['bale_token'] ?? '') ?>">
            </div>
        </div>
        <?php endif; ?>

        <div class="mt-4">
            <button class="btn btn-brand">ذخیره تنظیمات</button>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>