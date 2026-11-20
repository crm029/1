<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Helpers::e($title ?? 'نایریکا') ?> | کلینیک لیزر و زیبایی</title>
    <meta name="description" content="<?= Helpers::e($meta_description ?? 'کلینیک تخصصی لیزر و زیبایی نایریکا — رزرو آنلاین نوبت') ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --brand: <?= BRAND_COLOR ?>; --brand-dark: #b89393; }
        body { font-family: Tahoma, sans-serif; }
        .navbar-brand { font-weight: bold; color: var(--brand) !important; font-size: 1.5rem; }
        .btn-brand { background: var(--brand); border-color: var(--brand); color: #fff; }
        .btn-brand:hover { background: var(--brand-dark); border-color: var(--brand-dark); color: #fff; }
        .hero { background: linear-gradient(135deg, #f5e6e6, var(--brand)); color: #fff; padding: 80px 0; }
        .card-service { transition: transform 0.2s; border: none; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
        .card-service:hover { transform: translateY(-5px); }
        .footer { background: #2b2b2b; color: #bbb; padding: 40px 0 20px; margin-top: 60px; }
        .footer a { color: #ddd; text-decoration: none; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <?php 
        require_once __DIR__ . '/../../../app/models/Setting.php';
        $__pubSetting = new Setting();
        $__pubLogo = $__pubSetting->get('clinic_logo', '');
        $__pubName = $__pubSetting->get('clinic_name', 'نایریکا');
        ?>
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/">
            <?php if ($__pubLogo): ?>
                <img src="<?= BASE_URL ?>/public/<?= Helpers::e($__pubLogo) ?>" style="max-height:50px;max-width:150px;">
            <?php else: ?>
                <?= Helpers::e($__pubName) ?>
            <?php endif; ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/">صفحه اصلی</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/site/services">خدمات</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/site/gallery">گالری</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/site/contact">تماس با ما</a></li>
				<li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/site/about">درباره ما</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/site/consultation">درخواست مشاوره</a></li>
            </ul>
            <a href="<?= BASE_URL ?>/booking" class="btn btn-brand">
                <i class="bi bi-calendar-plus"></i> رزرو نوبت آنلاین
            </a>
        </div>
    </div>
</nav>

<?= $content ?? '' ?>

<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h6>نایریکا</h6>
                <p>کلینیک تخصصی لیزر و زیبایی</p>
            </div>
            <div class="col-md-4">
                <h6>لینک‌های سریع</h6>
                <ul class="list-unstyled">
                    <li><a href="<?= BASE_URL ?>/site/services">خدمات</a></li>
                    <li><a href="<?= BASE_URL ?>/site/gallery">گالری</a></li>
                    <li><a href="<?= BASE_URL ?>/booking">رزرو نوبت</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6>ارتباط با ما</h6>
                <p><i class="bi bi-telephone"></i> <?= Helpers::e($GLOBALS['settings']['clinic_phone'] ?? '') ?></p>
                <p><i class="bi bi-geo-alt"></i> <?= Helpers::e($GLOBALS['settings']['clinic_address'] ?? '') ?></p>
            </div>
        </div>
        <hr style="border-color:#444;">
        <p class="text-center mb-0 small">© <?= Helpers::toJalaliString(date('Y-m-d')) ?> نایریکا — تمام حقوق محفوظ است</p>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>