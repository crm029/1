<?php ob_start(); ?>
<section class="hero text-center">
    <div class="container">
        <h1 class="display-4 mb-3">کلینیک تخصصی لیزر و زیبایی</h1>
        <p class="lead mb-4">با بهترین تجهیزات و متخصصین مجرب، زیبایی خود را به ما بسپارید.</p>
        <a href="<?= BASE_URL ?>/booking" class="btn btn-light btn-lg px-5">
            <i class="bi bi-calendar-plus"></i> رزرو نوبت آنلاین
        </a>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">خدمات ما</h2>
        <div class="row g-4">
            <?php if (empty($services)): ?>
                <div class="col-12 text-center text-muted">خدمتی برای نمایش وجود ندارد.</div>
            <?php else: foreach (array_slice($services, 0, 6) as $s): ?>
                <div class="col-md-4">
                    <div class="card card-service p-4 text-center h-100">
                        <div style="width:60px;height:60px;border-radius:50%;background:<?= Helpers::e($s['color']) ?>;margin:0 auto 15px;"></div>
                        <h5><?= Helpers::e($s['name']) ?></h5>
                        <p class="text-muted small"><?= Helpers::e(mb_substr($s['description'] ?? '', 0, 80)) ?></p>
                        <?php if ($s['show_price_public']): ?>
                            <p class="fw-bold" style="color:<?= BRAND_COLOR ?>;"><?= number_format($s['price']) ?> تومان</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
        <?php if (count($services) > 6): ?>
            <div class="text-center mt-4">
                <a href="<?= BASE_URL ?>/site/services" class="btn btn-outline-secondary">مشاهده همه خدمات</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container text-center">
        <h2 class="mb-4">چرا نایریکا؟</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <i class="bi bi-award" style="font-size:3rem;color:<?= BRAND_COLOR ?>;"></i>
                <h5 class="mt-3">تجهیزات مدرن</h5>
                <p class="text-muted">بهترین دستگاه‌های روز دنیا</p>
            </div>
            <div class="col-md-4">
                <i class="bi bi-people" style="font-size:3rem;color:<?= BRAND_COLOR ?>;"></i>
                <h5 class="mt-3">متخصصین مجرب</h5>
                <p class="text-muted">کادری حرفه‌ای و آموزش‌دیده</p>
            </div>
            <div class="col-md-4">
                <i class="bi bi-shield-check" style="font-size:3rem;color:<?= BRAND_COLOR ?>;"></i>
                <h5 class="mt-3">ایمنی بالا</h5>
                <p class="text-muted">استانداردهای بهداشتی کامل</p>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container text-center">
        <h3 class="mb-3">همین حالا نوبت خود را رزرو کنید</h3>
        <p class="text-muted mb-4">با چند کلیک ساده، زمان دلخواه خود را انتخاب کنید</p>
        <a href="<?= BASE_URL ?>/booking" class="btn btn-brand btn-lg px-5">شروع رزرو</a>
    </div>
</section>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/public.php'; ?>