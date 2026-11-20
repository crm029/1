<?php 
require_once __DIR__ . '/../../../app/models/Setting.php';
$__s = new Setting();
$__logo = $__s->get('clinic_logo', '');
$__name = $__s->get('clinic_name', 'نایریکا');
ob_start(); 
?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <?php if ($__logo): ?>
                    <img src="<?= BASE_URL ?>/public/<?= Helpers::e($__logo) ?>" style="max-height:120px;max-width:250px;" class="mb-4">
                <?php endif; ?>
                <h2 class="mb-3">درباره <?= Helpers::e($__name) ?></h2>
                <p class="text-muted mb-4">
                    کلینیک تخصصی لیزر و زیبایی با هدف ارائه خدمات با کیفیت و مدرن به شما عزیزان فعالیت می‌کند.
                </p>

                <div class="row g-4 mt-4 text-start">
                    <div class="col-md-4">
                        <div class="card p-4 h-100 text-center">
                            <i class="bi bi-award" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                            <h6 class="mt-3">تجهیزات مدرن</h6>
                            <p class="small text-muted mb-0">بهترین دستگاه‌های روز دنیا</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-4 h-100 text-center">
                            <i class="bi bi-people" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                            <h6 class="mt-3">کادر مجرب</h6>
                            <p class="small text-muted mb-0">متخصصین آموزش‌دیده</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-4 h-100 text-center">
                            <i class="bi bi-shield-check" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                            <h6 class="mt-3">ایمنی بالا</h6>
                            <p class="small text-muted mb-0">استانداردهای بهداشتی</p>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <a href="<?= BASE_URL ?>/booking" class="btn btn-brand btn-lg px-5">رزرو نوبت آنلاین</a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/public.php'; ?>