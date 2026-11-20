<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">درخواست‌های مشاوره</h5>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row g-2 mb-4">
        <div class="col-md-6">
            <input type="text" id="liveSearch" class="form-control" placeholder="جستجو نام، موبایل، متن..." value="<?= Helpers::e($filters['search']) ?>">
        </div>
        <div class="col-md-3">
            <select id="statusFilter" class="form-select">
                <option value="">همه وضعیت‌ها</option>
                <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>در انتظار</option>
                <option value="answered" <?= $filters['status'] === 'answered' ? 'selected' : '' ?>>پاسخ داده شده</option>
                <option value="closed" <?= $filters['status'] === 'closed' ? 'selected' : '' ?>>بسته شده</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>نام</th>
                    <th>موبایل</th>
                    <th>متن درخواست</th>
                    <th>تاریخ</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody id="reqTable">
                <?php if (empty($requests)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">درخواستی یافت نشد</td></tr>
                <?php else: foreach ($requests as $r): 
                    $badges = [
                        'pending' => 'warning',
                        'answered' => 'info',
                        'closed' => 'success',
                    ];
                    $labels = [
                        'pending' => 'در انتظار',
                        'answered' => 'پاسخ داده شده',
                        'closed' => 'بسته شده',
                    ];
                ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= Helpers::e($r['full_name']) ?></td>
                        <td>
                            <a href="tel:<?= Helpers::e($r['mobile']) ?>" style="font-family:monospace;">
                                <?= Helpers::e($r['mobile']) ?>
                            </a>
                        </td>
                        <td style="max-width:300px;">
                            <small><?= Helpers::e(mb_substr($r['message'], 0, 80)) ?><?= mb_strlen($r['message']) > 80 ? '...' : '' ?></small>
                        </td>
                        <td><small><?= Helpers::toJalaliString($r['created_at']) ?></small></td>
                        <td>
                            <span class="badge bg-<?= $badges[$r['status']] ?? 'secondary' ?>">
                                <?= $labels[$r['status']] ?? $r['status'] ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/consultations/show/<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const s = document.getElementById('liveSearch');
const st = document.getElementById('statusFilter');
const table = document.getElementById('reqTable');

function liveFilter() {
    const p = new URLSearchParams({ search: s.value, status: st.value });
    fetch('<?= BASE_URL ?>/consultations?' + p.toString())
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nb = doc.getElementById('reqTable');
            if (nb) table.innerHTML = nb.innerHTML;
        });
}
let timer;
s.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(liveFilter, 300); });
st.addEventListener('change', liveFilter);
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>