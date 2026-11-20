<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h5 class="mb-0">کدهای تخفیف</h5>
        <a href="<?= BASE_URL ?>/discounts/create" class="btn btn-brand"><i class="bi bi-plus-lg"></i> کد جدید</a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row g-2 mb-4">
        <div class="col-md-5">
            <input type="text" id="liveSearch" class="form-control" placeholder="جستجو کد..." value="<?= Helpers::e($filters['search']) ?>">
        </div>
        <div class="col-md-3">
            <select id="activeFilter" class="form-select">
                <option value="">همه</option>
                <option value="1" <?= $filters['is_active'] === '1' ? 'selected' : '' ?>>فعال</option>
                <option value="0" <?= $filters['is_active'] === '0' ? 'selected' : '' ?>>غیرفعال</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>کد</th>
                    <th>خدمت</th>
                    <th>نوع</th>
                    <th>مقدار</th>
                    <th>استفاده</th>
                    <th>بازه</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody id="codesTable">
                <?php if (empty($codes)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">کدی یافت نشد</td></tr>
                <?php else: foreach ($codes as $c): ?>
                    <tr>
                        <td><span class="badge bg-dark" style="font-family:monospace;letter-spacing:1px;"><?= Helpers::e($c['code']) ?></span></td>
                        <td><?= Helpers::e($c['service_name'] ?? 'همه خدمات') ?></td>
                        <td><?= $c['discount_type'] === 'percent' ? 'درصدی' : 'مبلغی' ?></td>
                        <td>
                            <?php if ($c['discount_type'] === 'percent'): ?>
                                <?= (int)$c['discount_value'] ?>٪
                            <?php else: ?>
                                <?= number_format($c['discount_value']) ?> تومان
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= (int)$c['used_count'] ?>
                            <?= $c['max_uses'] ? ' / ' . (int)$c['max_uses'] : '' ?>
                        </td>
                        <td><small><?= $c['start_date'] ? Helpers::toJalaliString($c['start_date']) : '—' ?> تا <?= $c['end_date'] ? Helpers::toJalaliString($c['end_date']) : '—' ?></small></td>
                        <td>
                            <?php if ($c['is_active']): ?>
                                <span class="badge bg-success">فعال</span>
                            <?php else: ?>
                                <span class="badge bg-danger">غیرفعال</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/discounts/edit/<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="<?= BASE_URL ?>/discounts/toggle/<?= $c['id'] ?>" class="btn btn-sm btn-outline-<?= $c['is_active'] ? 'warning' : 'success' ?>" onclick="return confirm('تغییر وضعیت؟')">
                                <i class="bi bi-<?= $c['is_active'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                            </a>
                            <a href="<?= BASE_URL ?>/discounts/delete/<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف شود؟')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const s = document.getElementById('liveSearch');
const a = document.getElementById('activeFilter');
const table = document.getElementById('codesTable');

function liveFilter() {
    const p = new URLSearchParams({ search: s.value, is_active: a.value });
    fetch('<?= BASE_URL ?>/discounts?' + p.toString())
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nb = doc.getElementById('codesTable');
            if (nb) table.innerHTML = nb.innerHTML;
        });
}
let timer;
s.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(liveFilter, 300); });
a.addEventListener('change', liveFilter);
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>