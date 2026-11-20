<?php ob_start(); ?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card p-4 text-center">
                    <i class="bi bi-check-circle-fill" style="font-size:4rem;color:#28a745;"></i>
                    <h3 class="mt-3"><?= Helpers::e($success) ?></h3>

                    <?php if ($refund > 0): ?>
                        <div class="alert alert-info mt-3">
                            <b>مبلغ قابل برگشت:</b> <?= number_format($refund) ?> تومان
                            <?php if ($penalty > 0): ?>
                                <br><small>جریمه کسر شده: <?= number_format($penalty) ?> تومان</small>
                            <?php endif; ?>
                            <br><small>مبلغ به همان کارت پرداخت‌کننده واریز می‌شود (طی ۲۴ تا ۴۸ ساعت کاری).</small>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mt-3">مبلغی برای برگشت وجود ندارد.</p>
                    <?php endif; ?>

                    <a href="<?= BASE_URL ?>/" class="btn btn-brand mt-3">بازگشت به صفحه اصلی</a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/public.php'; ?>