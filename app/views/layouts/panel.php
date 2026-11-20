<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Helpers::e($title ?? 'نایریکا') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Persian Datepicker -->
    <link rel="stylesheet" href="https://unpkg.com/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://unpkg.com/persian-date@1.1.0/dist/persian-date.min.js"></script>
    <script src="https://unpkg.com/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
    <style>
        :root { --brand: <?= BRAND_COLOR ?>; --brand-dark: #b89393; }
        body { background: #f7f5f5; font-family: Tahoma, sans-serif; }

        .sidebar {
            width: 280px;
            height: 100vh;
            max-height: 100vh;
            background: linear-gradient(180deg, var(--brand), var(--brand-dark));
            position: fixed;
            right: 0;
            top: 0;
            color: #fff;
            padding: 25px 20px;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            display: block;
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 6px;
            font-size: 1rem;
            transition: all 0.2s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.2);
            padding-right: 24px;
        }
        .main-content { margin-right: 280px; padding: 30px; }
        .topbar {
            background: #fff;
            padding: 15px 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        .card { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .btn-brand { background: var(--brand); border-color: var(--brand); color: #fff; }
        .btn-brand:hover { background: var(--brand-dark); border-color: var(--brand-dark); color: #fff; }

        /* انیمیشن چشمک */
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
        .blink-warning { animation: blink 1s infinite; }

        /* بلوک پروفایل سایدبار */
        .sidebar-profile {
            text-align: center;
            padding-bottom: 15px;
        }
        .sidebar-profile img {
            max-height: 85px;
            max-width: 100%;
            margin-bottom: 12px;
            filter: drop-shadow(0 2px 6px rgba(0,0,0,0.15));
        }
        .sidebar-profile .clinic-name {
            font-size: 1.7rem;
            font-weight: bold;
            color: #fff;
            letter-spacing: 1px;
            margin: 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .sidebar-profile .user-name {
            font-size: 1.05rem;
            opacity: 0.95;
            margin-top: 14px;
        }
        .sidebar-profile .user-name i {
            font-size: 1.15rem;
        }
        .sidebar-profile .user-role {
            font-size: 0.88rem;
            opacity: 0.85;
            background: rgba(0,0,0,0.18);
            display: inline-block;
            padding: 4px 16px;
            border-radius: 12px;
            margin-top: 8px;
        }

        /* خط جداکننده تزئینی */
        .sidebar-divider {
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.6), transparent);
            margin: 18px 0 22px;
            position: relative;
        }
        .sidebar-divider::after {
            content: '';
            position: absolute;
            top: -5px;
            left: 50%;
            transform: translateX(-50%);
            width: 12px;
            height: 12px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(255,255,255,0.9);
        }

        /* اسکرول‌بار سایدبار */
        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 3px; }
    </style>
</head>
<body>
<div class="sidebar">
    <?php
    if (in_array(Auth::role(), ['super_admin', 'admin', 'secretary'])) {
        try {
            $pendingCount = (new Model())->fetchOne("SELECT COUNT(*) AS c FROM appointments WHERE status = 'cancel_requested' AND deleted_at IS NULL")['c'] ?? 0;
            if ($pendingCount > 0):
    ?>
        <a href="<?= BASE_URL ?>/appointments?status=cancel_requested" class="blink-warning" style="background:#dc3545 !important;font-weight:bold;margin-bottom:15px;">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= $pendingCount ?> درخواست لغو
        </a>
    <?php endif; } catch (Exception $e) {} } ?>

    <?php 
    require_once __DIR__ . '/../../../app/models/Setting.php';
    $__setting = new Setting();
    $__logo = $__setting->get('clinic_logo', '');
    $__clinic_name = $__setting->get('clinic_name', 'نایریکا');
    $__roles = [
        'super_admin' => 'سوپر ادمین',
        'admin' => 'مدیر کل',
        'secretary' => 'منشی',
        'operator' => 'اپراتور',
        'accountant' => 'حسابدار',
        'content_manager' => 'مدیر محتوا',
    ];
    ?>

    <!-- بلوک پروفایل -->
<div class="sidebar-profile">
    <?php if ($__logo): ?>
        <img src="<?= BASE_URL ?>/public/<?= Helpers::e($__logo) ?>" alt="لوگو">
    <?php else: ?>
        <p class="clinic-name"><?= Helpers::e($__clinic_name) ?></p>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/profile" style="text-decoration:none;color:inherit;">
        <div class="user-name">
            <i class="bi bi-person-circle"></i> <?= Helpers::e($_SESSION['full_name'] ?? '') ?>
        </div>
        <div class="user-role">
            <?= $__roles[Auth::role()] ?? '' ?>
        </div>
    </a>
</div>

    <!-- خط جداکننده تزئینی -->
    <div class="sidebar-divider"></div>

    <!-- منو -->
    <a href="<?= BASE_URL ?>/dashboard"><i class="bi bi-speedometer2"></i> داشبورد</a>

    <?php if (in_array(Auth::role(), ['super_admin','admin'])): ?>
        <a href="<?= BASE_URL ?>/users"><i class="bi bi-people"></i> کاربران</a>
		<a href="<?= BASE_URL ?>/service-categories"><i class="bi bi-grid-3x3-gap"></i> دسته‌بندی خدمات</a>
        <a href="<?= BASE_URL ?>/services"><i class="bi bi-list-check"></i> خدمات</a>
        <a href="<?= BASE_URL ?>/clients"><i class="bi bi-person-vcard"></i> کلاینت‌ها</a>
        <a href="<?= BASE_URL ?>/schedules"><i class="bi bi-calendar-week"></i> برنامه حضور</a>
        <a href="<?= BASE_URL ?>/holidays"><i class="bi bi-calendar-x"></i> تعطیلات</a>
        <a href="<?= BASE_URL ?>/discounts"><i class="bi bi-ticket-perforated"></i> کدهای تخفیف</a>
        <a href="<?= BASE_URL ?>/settings"><i class="bi bi-gear"></i> تنظیمات</a>
    <?php endif; ?>

    <?php if (Auth::role() === 'operator'): ?>
        <a href="<?= BASE_URL ?>/operator/today"><i class="bi bi-calendar-check"></i> نوبت‌های امروز من</a>
        <a href="<?= BASE_URL ?>/schedules"><i class="bi bi-calendar-week"></i> برنامه حضور من</a>
    <?php endif; ?>

    <?php if (in_array(Auth::role(), ['super_admin','admin','content_manager'])): ?>
        <a href="<?= BASE_URL ?>/content/gallery"><i class="bi bi-images"></i> گالری</a>
        <a href="<?= BASE_URL ?>/content/comments"><i class="bi bi-chat-dots"></i> نظرات</a>
    <?php endif; ?>

<?php if (in_array(Auth::role(), ['super_admin','admin','secretary'])): ?>
    <a href="<?= BASE_URL ?>/appointments"><i class="bi bi-calendar-check"></i> نوبت‌ها</a>
    <a href="<?= BASE_URL ?>/consultations"><i class="bi bi-chat-square-text"></i> درخواست‌های مشاوره</a>
<?php endif; ?>

    <?php if (in_array(Auth::role(), ['super_admin','admin','accountant'])): ?>
        <a href="<?= BASE_URL ?>/accounting"><i class="bi bi-cash-coin"></i> حسابداری</a>
        <a href="<?= BASE_URL ?>/accounting/locks"><i class="bi bi-lock"></i> قفل دوره مالی</a>
        <a href="<?= BASE_URL ?>/reports"><i class="bi bi-graph-up"></i> گزارش‌ها</a>
    <?php endif; ?>

<?php if (in_array(Auth::role(), ['super_admin','admin'])): ?>
    <a href="<?= BASE_URL ?>/logs"><i class="bi bi-activity"></i> لاگ فعالیت‌ها</a>
<?php endif; ?>
	
<?php if (Auth::role() === 'super_admin'): ?>
    <a href="<?= BASE_URL ?>/notification-templates"><i class="bi bi-chat-square-text"></i> متن پیام‌ها</a>
    <a href="<?= BASE_URL ?>/backups"><i class="bi bi-hdd-stack"></i> بکاپ‌ها</a>
<?php endif; ?>
    <a href="<?= BASE_URL ?>/profile"><i class="bi bi-person-gear"></i> پروفایل من</a>
    <a href="<?= BASE_URL ?>/logout" class="mt-3" style="background:rgba(0,0,0,0.2);"><i class="bi bi-box-arrow-right"></i> خروج</a>
	
</div>

<div class="main-content">
    <div class="topbar d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><?= Helpers::e($title ?? '') ?></h5>
        <span><?= Helpers::e($_SESSION['full_name'] ?? '') ?></span>
    </div>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?= $content ?? '' ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>