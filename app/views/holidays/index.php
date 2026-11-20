<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">لیست تعطیلات</h5>
        <a href="<?= BASE_URL ?>/holidays/create" class="btn btn-brand"><i class="bi bi-plus-lg"></i> تعطیلی جدید</a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="mb-4">
        <input type="text" id="liveSearch" class="form-control" placeholder="جستجو عنوان..." value="<?= Helpers::e($filters['search']) ?>">
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>تاریخ (شمسی)</th>
                    <th>عنوان</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody id="holidaysTable">
                <?php if (empty($holidays)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">تعطیلی ثبت نشده</td></tr>
                <?php else: foreach ($holidays as $h): ?>
                    <tr>
                        <td><?= $h['id'] ?></td>
                        <td><?= Helpers::toJalaliString($h['holiday_date']) ?></td>
                        <td><?= Helpers::e($h['title']) ?></td>
                        <td>
                            <?php if ($h['is_active']): ?>
                                <span class="badge bg-success">فعال</span>
                            <?php else: ?>
                                <span class="badge bg-danger">غیرفعال</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/holidays/edit/<?= $h['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="<?= BASE_URL ?>/holidays/toggle/<?= $h['id'] ?>" class="btn btn-sm btn-outline-<?= $h['is_active'] ? 'warning' : 'success' ?>" onclick="return confirm('تغییر وضعیت؟')">
                                <i class="bi bi-<?= $h['is_active'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const searchInput = document.getElementById('liveSearch');
    const table = document.getElementById('holidaysTable');
    let timer;
    searchInput.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            fetch('<?= BASE_URL ?>/holidays?search=' + encodeURIComponent(searchInput.value))
                .then(r => r.text())
                .then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const nb = doc.getElementById('holidaysTable');
                    if (nb) table.innerHTML = nb.innerHTML;
                });
        }, 300);
    });
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>