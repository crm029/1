<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">لیست کلاینت‌ها</h5>
        <a href="<?= BASE_URL ?>/clients/create" class="btn btn-brand"><i class="bi bi-plus-lg"></i> پرونده جدید</a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <input type="text" id="liveSearch" class="form-control" placeholder="جستجو (نام، موبایل، شماره پرونده)..." value="<?= Helpers::e($filters['search']) ?>">
        </div>
        <div class="col-md-4">
            <select id="genderFilter" class="form-select">
                <option value="">همه جنسیت‌ها</option>
                <option value="male" <?= $filters['gender'] === 'male' ? 'selected' : '' ?>>آقا</option>
                <option value="female" <?= $filters['gender'] === 'female' ? 'selected' : '' ?>>خانم</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>شماره پرونده</th>
                    <th>نام</th>
                    <th>موبایل</th>
                    <th>جنسیت</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody id="clientsTable">
                <?php if (empty($clients)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">کلاینتی یافت نشد</td></tr>
                <?php else: foreach ($clients as $c): ?>
                    <tr>
                        <td><span class="badge bg-dark"><?= Helpers::e($c['file_number']) ?></span></td>
                        <td><?= Helpers::e($c['full_name']) ?></td>
                        <td><?= Helpers::e($c['mobile']) ?></td>
                        <td><?= $c['gender'] === 'male' ? 'آقا' : 'خانم' ?></td>
                        <td>
                            <?php if ($c['is_active']): ?>
                                <span class="badge bg-success">فعال</span>
                            <?php else: ?>
                                <span class="badge bg-danger">غیرفعال</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/clients/edit/<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const searchInput = document.getElementById('liveSearch');
    const genderFilter = document.getElementById('genderFilter');
    const table = document.getElementById('clientsTable');

    function liveFilter() {
        const q = searchInput.value.toLowerCase();
        const g = genderFilter.value;
        fetch('<?= BASE_URL ?>/clients?search=' + encodeURIComponent(q) + '&gender=' + g)
            .then(res => res.text())
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const newBody = doc.getElementById('clientsTable');
                if (newBody) table.innerHTML = newBody.innerHTML;
            });
    }

    let timer;
    searchInput.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(liveFilter, 300);
    });
    genderFilter.addEventListener('change', liveFilter);
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>