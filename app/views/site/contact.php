<?php 
require_once __DIR__ . '/../../../app/models/Setting.php';
$__s = new Setting();
$__logo = $__s->get('clinic_logo', '');
$__name = $__s->get('clinic_name', 'نایریکا');
ob_start(); 
?>
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <?php if ($__logo): ?>
                <img src="<?= BASE_URL ?>/public/<?= Helpers::e($__logo) ?>" style="max-height:100px;max-width:200px;" class="mb-3">
            <?php endif; ?>
            <h2><?= Helpers::e($__name) ?></h2>
            <p class="text-muted">تماس با ما</p>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-md-5">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-telephone-fill" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                    <h5 class="mt-3">تلفن</h5>
                    <p class="mb-0"><?= Helpers::e($settings['clinic_phone'] ?? 'در حال ثبت') ?></p>
                </div>
            </div>
            <div class="col-md-5">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-geo-alt-fill" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                    <h5 class="mt-3">آدرس</h5>
                    <p class="mb-0"><?= Helpers::e($settings['clinic_address'] ?? 'در حال ثبت') ?></p>
                </div>
            </div>
        </div>
        <div class="text-center mt-5">
            <a href="<?= BASE_URL ?>/site/consultation" class="btn btn-brand btn-lg px-5">
                <i class="bi bi-chat-dots"></i> درخواست مشاوره
            </a>
        </div>
    </div>
</section>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/public.php'; ?>