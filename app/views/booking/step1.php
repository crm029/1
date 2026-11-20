<?php ob_start(); ?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card p-4">
                    <h4 class="text-center mb-2">رزرو نوبت آنلاین</h4>
                    <p class="text-center text-muted small mb-4">گام ۱ از ۴ — شناسایی</p>

                    <?php if (!empty($_SESSION['booking_error'])): ?>
                        <div class="alert alert-danger"><?= Helpers::e($_SESSION['booking_error']); unset($_SESSION['booking_error']); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="<?= BASE_URL ?>/booking/verify">
                        <?= Helpers::csrfField() ?>
                        <div class="mb-3">
                            <label class="form-label">شماره پرونده (۶ رقمی) *</label>
                            <input type="text" name="file_number" class="form-control text-center" required pattern="[0-9]{6}" maxlength="6" style="letter-spacing:5px;font-size:1.2rem;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">شماره موبایل ثبت‌شده *</label>
                            <input type="text" name="mobile" class="form-control" required pattern="09[0-9]{9}" placeholder="09123456789">
                        </div>
                        <div class="mb-3">
                            <label class="form-label"><?= Helpers::e($captcha) ?> *</label>
                            <input type="text" name="captcha" class="form-control" required>
                        </div>
                        <button class="btn btn-brand w-100">ادامه</button>
                    </form>

                    <hr class="my-4">
                    <p class="text-center small text-muted">
                        شماره پرونده را از منشی کلینیک دریافت کنید.<br>
                        اگر پرونده ندارید، با ما تماس بگیرید.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/public.php'; ?>