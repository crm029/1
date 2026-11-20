<?php ob_start(); ?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card p-4 text-center">
                    <i class="bi bi-check-circle-fill" style="font-size:4rem;color:#28a745;"></i>
                    <h3 class="mt-3">رزرو شما با موفقیت ثبت شد</h3>
                    <p class="text-muted">پس از تایید رسید توسط منشی، نوبت شما قطعی می‌شود.</p>

                    <hr>

                    <h6 style="color:<?= BRAND_COLOR ?>;">کدهای پیگیری شما</h6>
                    <div class="d-flex flex-wrap justify-content-center gap-2 my-3">
                        <?php foreach ($codes as $c): ?>
                            <span class="badge bg-dark" style="font-size:1.1rem;padding:10px 18px;letter-spacing:2px;"><?= Helpers::e($c) ?></span>
                        <?php endforeach; ?>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <b>مبلغ بیعانه پرداخت‌شده:</b> <?= number_format($total) ?> تومان<br>
                        <small>لطفاً کد پیگیری را تا پایان مراجعه نزد خود نگه دارید.</small>
                    </div>

                    <p class="small text-muted">
                        پس از تایید رسید توسط منشی، پیام تاییدیه از طریق پیامک/ربات بله برای شما ارسال می‌شود.
                    </p>

                    <a href="<?= BASE_URL ?>/" class="btn btn-brand mt-3">بازگشت به صفحه اصلی</a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/public.php'; ?>