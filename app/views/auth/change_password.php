<?php ob_start(); ?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card p-4">
            <div class="text-center mb-4">
                <i class="bi bi-shield-lock" style="font-size:3rem;color:<?= BRAND_COLOR ?>;"></i>
                <h5 class="mt-3 mb-1">تغییر رمز عبور</h5>
                <?php if (!empty($_SESSION['must_change_password'])): ?>
                    <p class="text-muted small mb-0">
                        ⚠️ برای امنیت حساب، در اولین ورود باید رمز خود را تغییر دهید.
                    </p>
                <?php endif; ?>
            </div>

            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
            <?php endif; ?>

            <div class="alert alert-info small">
                <b>قوانین رمز جدید:</b>
                <ul class="mb-0 mt-1">
                    <li>حداقل ۸ کاراکتر</li>
                    <li>شامل حرف بزرگ انگلیسی (A-Z)</li>
                    <li>شامل حرف کوچک انگلیسی (a-z)</li>
                    <li>شامل عدد (0-9)</li>
                    <li>نباید با شماره موبایل یکسان باشد</li>
                </ul>
            </div>

            <form method="POST" action="<?= BASE_URL ?>/change-password/submit">
                <?= Helpers::csrfField() ?>
                
                <div class="mb-3">
                    <label class="form-label">رمز فعلی *</label>
                    <input type="password" name="current_password" class="form-control" required autofocus>
                    <small class="text-muted">رمز فعلی = شماره موبایل شما (اگر تغییر نداده‌اید)</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">رمز جدید *</label>
                    <input type="password" name="new_password" class="form-control" required minlength="8">
                </div>
                <div class="mb-3">
                    <label class="form-label">تکرار رمز جدید *</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label"><?= Helpers::e($captcha) ?> *</label>
                    <input type="text" name="captcha" class="form-control" required>
                </div>
                
                <button class="btn btn-brand w-100">تغییر رمز</button>
            </form>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>