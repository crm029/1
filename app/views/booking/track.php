<?php ob_start(); ?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card p-4">
                    <h4 class="text-center mb-2">پیگیری نوبت</h4>
                    <p class="text-center text-muted small mb-4">کد نوبت ۸ رقمی و شماره موبایل خود را وارد کنید</p>

                    <?php if (!empty($_SESSION['track_error'])): ?>
                        <div class="alert alert-danger"><?= Helpers::e($_SESSION['track_error']); unset($_SESSION['track_error']); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="<?= BASE_URL ?>/booking/track/submit">
                        <?= Helpers::csrfField() ?>
                        <div class="mb-3">
                            <label class="form-label">کد نوبت (۸ رقمی) *</label>
                            <input type="text" name="booking_code" class="form-control text-center" required pattern="[0-9]{8}" maxlength="8" style="letter-spacing:4px;font-size:1.2rem;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">شماره موبایل *</label>
                            <input type="text" name="mobile" class="form-control" required pattern="09[0-9]{9}">
                        </div>
                        <button class="btn btn-brand w-100">مشاهده نوبت</button>
                    </form>

                    <hr class="my-4">
                    <a href="<?= BASE_URL ?>/booking" class="btn btn-light w-100">رزرو نوبت جدید</a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/public.php'; ?>