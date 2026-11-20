<?php ob_start(); ?>
<div class="card p-4 mb-3 d-print-none">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">فاکتور <span class="badge bg-dark"><?= Helpers::e($invoice['invoice_number']) ?></span></h5>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/accounting/invoices" class="btn btn-light">بازگشت</a>
            <button onclick="window.print()" class="btn btn-brand"><i class="bi bi-printer"></i> پرینت</button>
        </div>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success d-print-none"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>

<div class="card p-5" id="printArea">
    <!-- سربرگ -->
    <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <?php if (!empty($settings['clinic_logo'])): ?>
                <img src="<?= BASE_URL ?>/public/<?= Helpers::e($settings['clinic_logo']) ?>" style="max-height:70px;max-width:120px;">
            <?php else: ?>
                <div style="width:60px;height:60px;background:<?= BRAND_COLOR ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;font-size:1.5rem;">
                    <?= mb_substr($settings['clinic_name'] ?? 'N', 0, 1) ?>
                </div>
            <?php endif; ?>
            <div>
                <h4 style="color:<?= BRAND_COLOR ?>;margin:0;"><?= Helpers::e($settings['clinic_name'] ?? 'کلینیک') ?></h4>
                <p class="mb-1 small"><?= Helpers::e($settings['clinic_address'] ?? '') ?></p>
                <p class="mb-0 small">تلفن: <?= Helpers::e($settings['clinic_phone'] ?? '') ?></p>
            </div>
        </div>
        <div class="text-end">
            <h5 class="mb-1">فاکتور فروش خدمات</h5>
            <p class="mb-1 small"><b>شماره:</b> <?= Helpers::e($invoice['invoice_number']) ?></p>
            <p class="mb-0 small"><b>تاریخ:</b> <?= Helpers::toJalaliString($invoice['created_at']) ?></p>
        </div>
    </div>

    <!-- اطلاعات کلاینت -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h6 class="mb-2" style="color:<?= BRAND_COLOR ?>;">اطلاعات کلاینت</h6>
            <p class="mb-1 small"><b>نام:</b> <?= Helpers::e($invoice['client_name']) ?></p>
            <p class="mb-1 small"><b>شماره پرونده:</b> <?= Helpers::e($invoice['file_number']) ?></p>
            <p class="mb-1 small"><b>موبایل:</b> <?= Helpers::e($invoice['client_mobile']) ?></p>
            <p class="mb-0 small"><b>آدرس:</b> <?= Helpers::e($invoice['address']) ?></p>
        </div>
    </div>

    <!-- جدول خدمات -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered">
            <thead style="background:<?= BRAND_COLOR ?>;color:#fff;">
                <tr>
                    <th style="width:50px;">ردیف</th>
                    <th>شرح خدمت</th>
                    <th style="width:200px;">مبلغ (تومان)</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($items as $it): ?>
                    <tr>
                        <td class="text-center"><?= $i++ ?></td>
                        <td><?= Helpers::e($it['service_name']) ?></td>
                        <td class="text-end"><?= number_format($it['service_price']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-end">جمع کل:</th>
                    <th class="text-end"><?= number_format($invoice['total_amount']) ?></th>
                </tr>
                <?php if ($invoice['discount_amount'] > 0): ?>
                    <tr>
                        <th colspan="2" class="text-end">تخفیف:</th>
                        <th class="text-end">- <?= number_format($invoice['discount_amount']) ?></th>
                    </tr>
                <?php endif; ?>
                <tr style="background:#f8f9fa;">
                    <th colspan="2" class="text-end">مبلغ قابل پرداخت:</th>
                    <th class="text-end" style="color:<?= BRAND_COLOR ?>;"><?= number_format($invoice['payable_amount']) ?> تومان</th>
                </tr>
            </tfoot>
        </table>
    </div>

    <?php if (!empty($invoice['notes'])): ?>
        <div class="mb-4">
            <h6 class="small text-muted">توضیحات:</h6>
            <p class="small"><?= nl2br(Helpers::e($invoice['notes'])) ?></p>
        </div>
    <?php endif; ?>

    <!-- امضا -->
    <div class="row mt-5">
        <div class="col-md-6 text-center">
            <p class="small mb-5">مهر کلینیک</p>
        </div>
        <div class="col-md-6 text-center">
            <p class="small mb-5">امضاء مراجغه کننده</p>
        </div>
    </div>

    <hr>
    <p class="small text-muted text-center mb-0">
        این فاکتور توسط <?= Helpers::e($invoice['creator_name'] ?? 'سیستم') ?> صادر شده است.
    </p>
</div>

<style>
@media print {
    @page { margin: 1cm; }
    .sidebar, .topbar, .d-print-none { display: none !important; }
    .main-content { margin-right: 0 !important; padding: 0 !important; }
    .alert { display: none !important; }
    .card { box-shadow: none !important; border: none !important; }
    #printArea { padding: 0 !important; }
    body { background: #fff !important; }
}
</style>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>