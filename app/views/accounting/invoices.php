<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h5 class="mb-0">فاکتورها</h5>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/accounting" class="btn btn-light">بازگشت</a>
            <a href="<?= BASE_URL ?>/accounting/invoices/create" class="btn btn-brand"><i class="bi bi-plus-lg"></i> فاکتور جدید</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row g-2 mb-4">
        <div class="col-md-4">
            <input type="text" id="liveSearch" class="form-control" placeholder="جستجو (شماره فاکتور، نام، پرونده)..." value="<?= Helpers::e($filters['search']) ?>">
        </div>
        <div class="col-md-3">
            <input type="date" id="dateFrom" class="form-control" value="<?= Helpers::e($filters['date_from']) ?>">
        </div>
        <div class="col-md-3">
            <input type="date" id="dateTo" class="form-control" value="<?= Helpers::e($filters['date_to']) ?>">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>شماره فاکتور</th>
                    <th>کلاینت</th>
                    <th>مبلغ کل</th>
                    <th>تخفیف</th>
                    <th>قابل پرداخت</th>
                    <th>ثبت‌کننده</th>
                    <th>تاریخ</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody id="invoicesTable">
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">فاکتوری یافت نشد</td></tr>
                <?php else: foreach ($invoices as $inv): ?>
                    <tr>
                        <td><span class="badge bg-dark"><?= Helpers::e($inv['invoice_number']) ?></span></td>
                        <td><?= Helpers::e($inv['client_name']) ?> <small class="text-muted">(<?= Helpers::e($inv['file_number']) ?>)</small></td>
                        <td><?= number_format($inv['total_amount']) ?></td>
                        <td><?= number_format($inv['discount_amount']) ?></td>
                        <td class="fw-bold"><?= number_format($inv['payable_amount']) ?> تومان</td>
                        <td><?= Helpers::e($inv['creator_name'] ?? '—') ?></td>
                        <td><?= Helpers::toJalaliString($inv['created_at']) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/accounting/invoices/show/<?= $inv['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            <?php if (in_array(Auth::role(), ['super_admin','admin'])): ?>
                                <a href="<?= BASE_URL ?>/accounting/invoices/delete/<?= $inv['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف شود؟')"><i class="bi bi-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const s = document.getElementById('liveSearch');
const df = document.getElementById('dateFrom');
const dt = document.getElementById('dateTo');
const table = document.getElementById('invoicesTable');

function liveFilter() {
    const p = new URLSearchParams({ search: s.value, date_from: df.value, date_to: dt.value });
    fetch('<?= BASE_URL ?>/accounting/invoices?' + p.toString())
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nb = doc.getElementById('invoicesTable');
            if (nb) table.innerHTML = nb.innerHTML;
        });
}
let timer;
s.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(liveFilter, 300); });
[df, dt].forEach(el => el.addEventListener('change', liveFilter));
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>