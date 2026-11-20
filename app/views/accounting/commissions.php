<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h5 class="mb-0">دستمزد اپراتورها</h5>
        <a href="<?= BASE_URL ?>/accounting" class="btn btn-light">بازگشت به حسابداری</a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <!-- کارت‌های خلاصه -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card p-3 text-center">
                <h6 class="text-muted mb-2">جمع کل دستمزد</h6>
                <h4 style="color:<?= BRAND_COLOR ?>;"><?= number_format($totalEarned) ?> تومان</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 text-center">
                <h6 class="text-muted mb-2">پرداخت‌شده</h6>
                <h4 class="text-success"><?= number_format($totalPaid) ?> تومان</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 text-center">
                <h6 class="text-muted mb-2">پرداخت‌نشده</h6>
                <h4 class="text-danger"><?= number_format($totalUnpaid) ?> تومان</h4>
            </div>
        </div>
    </div>

    <!-- فیلترها -->
    <div class="row g-2 mb-4">
        <div class="col-md-3">
            <select id="operatorFilter" class="form-select">
                <option value="">همه اپراتورها</option>
                <?php foreach ($operators as $op): ?>
                    <option value="<?= $op['id'] ?>" <?= $filters['operator_id'] == $op['id'] ? 'selected' : '' ?>>
                        <?= Helpers::e($op['full_name']) ?> <?= $op['is_doctor'] ? '(دکتر)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select id="paidFilter" class="form-select">
                <option value="">همه</option>
                <option value="0" <?= $filters['paid'] === '0' ? 'selected' : '' ?>>فقط پرداخت‌نشده</option>
                <option value="1" <?= $filters['paid'] === '1' ? 'selected' : '' ?>>فقط پرداخت‌شده</option>
            </select>
        </div>
        <div class="col-md-3">
            <input type="date" id="dateFrom" class="form-control" value="<?= Helpers::e($filters['date_from']) ?>">
        </div>
        <div class="col-md-3">
            <input type="date" id="dateTo" class="form-control" value="<?= Helpers::e($filters['date_to']) ?>">
        </div>
    </div>

    <!-- دکمه پرداخت گروهی -->
    <?php if ($filters['operator_id'] && $totalUnpaid > 0): ?>
        <form method="POST" action="<?= BASE_URL ?>/accounting/commissions/pay-all/<?= (int)$filters['operator_id'] ?>" class="mb-3" onsubmit="return confirm('همه دستمزدهای پرداخت‌نشده این اپراتور پرداخت شود؟')">
            <?= Helpers::csrfField() ?>
            <button class="btn btn-success">
                <i class="bi bi-cash-stack"></i> پرداخت گروهی همه دستمزدهای پرداخت‌نشده
            </button>
        </form>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>کد نوبت</th>
                    <th>تاریخ خدمت</th>
                    <th>کلاینت</th>
                    <th>خدمت</th>
                    <th>اپراتور</th>
                    <th>مبلغ خدمت</th>
                    <th>دستمزد</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">رکوردی یافت نشد</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><span class="badge bg-dark"><?= Helpers::e($r['booking_code']) ?></span></td>
                        <td><?= Helpers::toJalaliString($r['appointment_date']) ?></td>
                        <td><?= Helpers::e($r['client_name']) ?> <small class="text-muted">(<?= Helpers::e($r['file_number']) ?>)</small></td>
                        <td><?= Helpers::e($r['service_name']) ?></td>
                        <td><?= Helpers::e($r['operator_name'] ?? '—') ?> <?= $r['is_doctor'] ? '<span class="badge bg-info">دکتر</span>' : '' ?></td>
                        <td><?= number_format($r['service_price']) ?></td>
                        <td class="fw-bold"><?= number_format($r['operator_commission']) ?> تومان</td>
                        <td>
                            <?php if ($r['commission_paid']): ?>
                                <span class="badge bg-success">پرداخت شده</span>
                                <br><small class="text-muted"><?= Helpers::toJalaliString($r['commission_paid_at']) ?></small>
                            <?php else: ?>
                                <span class="badge bg-warning">پرداخت‌نشده</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$r['commission_paid']): ?>
                                <form method="POST" action="<?= BASE_URL ?>/accounting/commissions/pay/<?= $r['id'] ?>" onsubmit="return confirm('پرداخت شود؟')">
                                    <?= Helpers::csrfField() ?>
                                    <button class="btn btn-sm btn-success"><i class="bi bi-check2"></i> پرداخت</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const op = document.getElementById('operatorFilter');
const pd = document.getElementById('paidFilter');
const df = document.getElementById('dateFrom');
const dt = document.getElementById('dateTo');

function applyFilter() {
    const p = new URLSearchParams({
        operator_id: op.value,
        paid: pd.value,
        date_from: df.value,
        date_to: dt.value,
    });
    window.location = '<?= BASE_URL ?>/accounting/commissions?' + p.toString();
}
[op, pd, df, dt].forEach(el => el.addEventListener('change', applyFilter));
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>