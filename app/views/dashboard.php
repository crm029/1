<?php ob_start(); ?>

<!-- نوار اعلان -->
<?php if (!empty($notifications)): ?>
    <div class="notification-bar mb-4">
        <?php foreach ($notifications as $n): ?>
            <a href="<?= Helpers::e($n['url']) ?>" target="_blank" class="notif-item notif-<?= $n['color'] ?>">
                <i class="bi <?= $n['icon'] ?>"></i>
                <span><?= Helpers::e($n['label']) ?></span>
                <i class="bi bi-arrow-left"></i>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ============ منشی ============ -->
<?php if ($role === 'secretary'): ?>
    <div class="row g-4">
        <div class="col-md-3 col-sm-6">
            <a href="<?= BASE_URL ?>/appointments?date_from=<?= date('Y-m-d') ?>&date_to=<?= date('Y-m-d') ?>" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-calendar-check" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                    <h6 class="mt-3 text-muted">نوبت‌های امروز</h6>
                    <h3 class="mb-0"><?= number_format($stats['today_appointments']) ?></h3>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="<?= BASE_URL ?>/clients" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-people" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                    <h6 class="mt-3 text-muted">کلاینت‌ها</h6>
                    <h3 class="mb-0"><?= number_format($stats['total_clients']) ?></h3>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="<?= BASE_URL ?>/appointments" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-calendar-week" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                    <h6 class="mt-3 text-muted">نوبت‌های ۷ روز آینده</h6>
                    <h3 class="mb-0"><?= number_format($stats['week_appointments']) ?></h3>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="<?= BASE_URL ?>/appointments?status=pending_receipt" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-receipt" style="font-size:2.5rem;color:<?= $stats['pending_receipts'] > 0 ? '#ffc107' : BRAND_COLOR ?>;"></i>
                    <h6 class="mt-3 text-muted">رسیدهای در انتظار</h6>
                    <h3 class="mb-0"><?= number_format($stats['pending_receipts']) ?></h3>
                </div>
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- ============ مدیر / سوپر ادمین ============ -->
<?php if (in_array($role, ['super_admin', 'admin'])): ?>
    <div class="row g-4">
        <div class="col-md-3 col-sm-6">
            <a href="<?= BASE_URL ?>/appointments?date_from=<?= date('Y-m-d') ?>&date_to=<?= date('Y-m-d') ?>" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-calendar-check" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                    <h6 class="mt-3 text-muted">نوبت‌های امروز</h6>
                    <h3 class="mb-0"><?= number_format($stats['today_appointments']) ?></h3>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="<?= BASE_URL ?>/clients" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-people" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                    <h6 class="mt-3 text-muted">کلاینت‌ها</h6>
                    <h3 class="mb-0"><?= number_format($stats['total_clients']) ?></h3>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="<?= BASE_URL ?>/accounting" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-cash-coin" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                    <h6 class="mt-3 text-muted">درآمد امروز</h6>
                    <h3 class="mb-0"><?= number_format($stats['today_income']) ?> <small class="text-muted">ت</small></h3>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="<?= BASE_URL ?>/reports" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-graph-up-arrow" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                    <h6 class="mt-3 text-muted">درآمد این ماه</h6>
                    <h3 class="mb-0"><?= number_format($stats['month_income']) ?> <small class="text-muted">ت</small></h3>
                </div>
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- ============ اپراتور ============ -->
<?php if ($role === 'operator'): ?>
    <div class="row g-4">
        <div class="col-md-3 col-sm-6">
            <a href="<?= BASE_URL ?>/operator/today" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-calendar-check" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                    <h6 class="mt-3 text-muted">نوبت‌های امروز من</h6>
                    <h3 class="mb-0"><?= number_format($stats['today_appointments']) ?></h3>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="<?= BASE_URL ?>/operator/today" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-calendar-week" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                    <h6 class="mt-3 text-muted">۷ روز آینده</h6>
                    <h3 class="mb-0"><?= number_format($stats['week_appointments']) ?></h3>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="<?= BASE_URL ?>/operator/today" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-check2-circle" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                    <h6 class="mt-3 text-muted">انجام‌شده این ماه</h6>
                    <h3 class="mb-0"><?= number_format($stats['month_done']) ?></h3>
                </div>
            </a>
        </div>

        <div class="col-md-3 col-sm-6">
            <a href="<?= BASE_URL ?>/accounting/commissions" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-cash-stack" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                    <h6 class="mt-3 text-muted">دستمزد پرداخت‌نشده</h6>
                    <h3 class="mb-0"><?= number_format($stats['unpaid_commission']) ?> <small class="text-muted">ت</small></h3>
                </div>
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- ============ حسابدار ============ -->
<?php if ($role === 'accountant'): ?>
    <div class="row g-4">
        <div class="col-md-4 col-sm-6">
            <a href="<?= BASE_URL ?>/accounting?type=deposit" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-arrow-down-circle" style="font-size:2.5rem;color:#28a745;"></i>
                    <h6 class="mt-3 text-muted">درآمد امروز</h6>
                    <h3 class="mb-0 text-success"><?= number_format($stats['today_income']) ?> <small class="text-muted">ت</small></h3>
                </div>
            </a>
        </div>

        <div class="col-md-4 col-sm-6">
            <a href="<?= BASE_URL ?>/reports" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-graph-up-arrow" style="font-size:2.5rem;color:#28a745;"></i>
                    <h6 class="mt-3 text-muted">درآمد این ماه</h6>
                    <h3 class="mb-0 text-success"><?= number_format($stats['month_income']) ?> <small class="text-muted">ت</small></h3>
                </div>
            </a>
        </div>

        <div class="col-md-4 col-sm-6">
            <a href="<?= BASE_URL ?>/accounting?type=expense" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-arrow-up-circle" style="font-size:2.5rem;color:#dc3545;"></i>
                    <h6 class="mt-3 text-muted">هزینه امروز</h6>
                    <h3 class="mb-0 text-danger"><?= number_format($stats['today_expense']) ?> <small class="text-muted">ت</small></h3>
                </div>
            </a>
        </div>

        <div class="col-md-4 col-sm-6">
            <a href="<?= BASE_URL ?>/reports" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-wallet2" style="font-size:2.5rem;color:#dc3545;"></i>
                    <h6 class="mt-3 text-muted">هزینه این ماه</h6>
                    <h3 class="mb-0 text-danger"><?= number_format($stats['month_expense']) ?> <small class="text-muted">ت</small></h3>
                </div>
            </a>
        </div>

        <div class="col-md-4 col-sm-6">
            <a href="<?= BASE_URL ?>/accounting/cash-register" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-safe" style="font-size:2.5rem;color:<?= $stats['open_register'] > 0 ? '#ffc107' : '#28a745' ?>;"></i>
                    <h6 class="mt-3 text-muted">وضعیت صندوق امروز</h6>
                    <h5 class="mb-0">
                        <?php if ($stats['open_register'] > 0): ?>
                            <span class="badge bg-warning text-dark">باز</span>
                        <?php else: ?>
                            <span class="badge bg-success">بسته</span>
                        <?php endif; ?>
                    </h5>
                </div>
            </a>
        </div>

        <div class="col-md-4 col-sm-6">
            <a href="<?= BASE_URL ?>/accounting/commissions" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-cash-stack" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                    <h6 class="mt-3 text-muted">دستمزد پرداخت‌نشده</h6>
                    <h3 class="mb-0"><?= number_format($stats['unpaid_commissions']) ?> <small class="text-muted">ت</small></h3>
                </div>
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- ============ مدیر محتوا ============ -->
<?php if ($role === 'content_manager'): ?>
    <div class="row g-4">
        <div class="col-md-4">
            <a href="<?= BASE_URL ?>/content/comments" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-chat-dots" style="font-size:2.5rem;color:<?= $stats['pending_comments'] > 0 ? '#ffc107' : BRAND_COLOR ?>;"></i>
                    <h6 class="mt-3 text-muted">نظرات در انتظار تایید</h6>
                    <h3 class="mb-0"><?= number_format($stats['pending_comments']) ?></h3>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="<?= BASE_URL ?>/content/comments" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-check-circle" style="font-size:2.5rem;color:#28a745;"></i>
                    <h6 class="mt-3 text-muted">نظرات تایید‌شده</h6>
                    <h3 class="mb-0"><?= number_format($stats['approved_comments']) ?></h3>
                </div>
            </a>
        </div>

        <div class="col-md-4">
            <a href="<?= BASE_URL ?>/content/gallery" target="_blank" class="stat-card">
                <div class="card p-4 text-center h-100">
                    <i class="bi bi-images" style="font-size:2.5rem;color:<?= BRAND_COLOR ?>;"></i>
                    <h6 class="mt-3 text-muted">تصاویر گالری</h6>
                    <h3 class="mb-0"><?= number_format($stats['total_gallery']) ?></h3>
                </div>
            </a>
        </div>
    </div>
<?php endif; ?>

<style>
    .notification-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .notif-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        border-radius: 10px;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.2s;
        border: 1px solid transparent;
    }
    .notif-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .notif-item i { font-size: 1.1rem; }
    .notif-item .bi-arrow-left { font-size: 0.85rem; opacity: 0.6; margin-right: auto; }
    .notif-warning { background: #fff3cd; color: #856404; border-color: #ffeaa7; }
    .notif-danger { background: #f8d7da; color: #721c24; border-color: #f5c6cb; }
    .notif-info { background: #d1ecf1; color: #0c5460; border-color: #bee5eb; }
    .notif-success { background: #d4edda; color: #155724; border-color: #c3e6cb; }

    .stat-card {
        text-decoration: none;
        color: inherit;
        display: block;
        transition: all 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-3px);
    }
    .stat-card:hover .card {
        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
    .stat-card .card {
        transition: all 0.2s;
    }
    .stat-card h3 { color: #333; }
</style>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/layouts/panel.php'; ?>