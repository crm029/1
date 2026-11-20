<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h5 class="mb-0">حقوق پرسنل</h5>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/accounting" class="btn btn-light">بازگشت</a>
            <a href="<?= BASE_URL ?>/accounting/salaries/create" class="btn btn-brand"><i class="bi bi-plus-lg"></i> حقوق جدید</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card p-3 text-center">
                <h6 class="text-muted mb-2">پرداخت‌شده</h6>
                <h4 class="text-success"><?= number_format($totalPaid) ?> تومان</h4>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3 text-center">
                <h6 class="text-muted mb-2">پرداخت‌نشده</h6>
                <h4 class="text-danger"><?= number_format($totalUnpaid) ?> تومان</h4>
            </div>
        </div>
    </div>

    <div class="row g-2 mb-4">
        <div class="col-md-4">
            <select id="staffFilter" class="form-select">
                <option value="">همه کارمندان</option>
                <?php foreach ($staff as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $filters['user_id'] == $s['id'] ? 'selected' : '' ?>>
                        <?= Helpers::e($s['full_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select id="paidFilter" class="form-select">
                <option value="">همه</option>
                <option value="0" <?= $filters['paid'] === '0' ? 'selected' : '' ?>>پرداخت‌نشده</option>
                <option value="1" <?= $filters['paid'] === '1' ? 'selected' : '' ?>>پرداخت‌شده</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>کارمند</th>
                    <th>دوره</th>
                    <th>مبلغ</th>
                    <th>توضیحات</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">رکوردی یافت نشد</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td><?= Helpers::e($r['full_name']) ?></td>
                        <td><?= Helpers::toJalaliString($r['period_start']) ?> تا <?= Helpers::toJalaliString($r['period_end']) ?></td>
                        <td class="fw-bold"><?= number_format($r['amount']) ?> تومان</td>
                        <td><small class="text-muted"><?= Helpers::e($r['description'] ?? '—') ?></small></td>
                        <td>
                            <?php if ($r['is_paid']): ?>
                                <span class="badge bg-success">پرداخت شده</span>
                                <br><small class="text-muted"><?= Helpers::toJalaliString($r['paid_at']) ?></small>
                            <?php else: ?>
                                <span class="badge bg-warning">پرداخت‌نشده</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$r['is_paid']): ?>
                                <form method="POST" action="<?= BASE_URL ?>/accounting/salaries/pay/<?= $r['id'] ?>" style="display:inline;" onsubmit="return confirm('پرداخت شود؟')">
                                    <?= Helpers::csrfField() ?>
                                    <button class="btn btn-sm btn-success"><i class="bi bi-check2"></i></button>
                                </form>
                                <a href="<?= BASE_URL ?>/accounting/salaries/delete/<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف شود؟')"><i class="bi bi-trash"></i></a>
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
const st = document.getElementById('staffFilter');
const pd = document.getElementById('paidFilter');
function applyFilter() {
    const p = new URLSearchParams({ user_id: st.value, paid: pd.value });
    window.location = '<?= BASE_URL ?>/accounting/salaries?' + p.toString();
}
[st, pd].forEach(el => el.addEventListener('change', applyFilter));
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>