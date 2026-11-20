<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">لیست خدمات</h5>
        <a href="<?= BASE_URL ?>/services/create" class="btn btn-brand"><i class="bi bi-plus-lg"></i> خدمت جدید</a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <input type="text" id="liveSearch" class="form-control" placeholder="جستجو نام خدمت..." value="<?= Helpers::e($filters['search']) ?>">
        </div>
        <div class="col-md-3">
            <select id="categoryFilter" class="form-select">
                <option value="">همه دسته‌ها</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($filters['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                        <?= Helpers::e($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select id="activeFilter" class="form-select">
                <option value="">همه وضعیت‌ها</option>
                <option value="1" <?= $filters['is_active'] === '1' ? 'selected' : '' ?>>فقط فعال</option>
                <option value="0" <?= $filters['is_active'] === '0' ? 'selected' : '' ?>>فقط غیرفعال</option>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>رنگ</th>
                    <th>دسته‌بندی</th>
                    <th>نام خدمت</th>
                    <th>مدت (دقیقه)</th>
                    <th>قیمت</th>
                    <th>نمایش عمومی</th>
                    <th>جنسیت</th>
                    <th>نوع</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody id="servicesTable">
                <?php if (empty($services)): ?>
                    <tr><td colspan="11" class="text-center text-muted py-4">خدمتی یافت نشد</td></tr>
                <?php else: foreach ($services as $s): ?>
                    <tr>
                        <td><?= $s['id'] ?></td>
                        <td><span style="display:inline-block;width:25px;height:25px;border-radius:50%;background:<?= Helpers::e($s['color']) ?>;"></span></td>
                        <td>
                            <span class="badge" style="background:<?= Helpers::e($s['category_color'] ?? '#D1B1B0') ?>;">
                                <i class="<?= Helpers::e($s['category_icon'] ?? 'fa-solid fa-spa') ?>"></i>
                                <?= Helpers::e($s['category_name'] ?? 'بدون دسته') ?>
                            </span>
                        </td>
                        <td><?= Helpers::e($s['name']) ?></td>
                        <td><?= $s['duration_minutes'] ?></td>
                        <td><?= number_format($s['price']) ?> تومان</td>
                        <td><?= $s['show_price_public'] ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>' ?></td>
                        <td>
                            <?php if ($s['gender_restriction'] === 'none'): ?>
                                <span class="badge bg-secondary">آزاد</span>
                            <?php elseif ($s['gender_restriction'] === 'male'): ?>
                                <span class="badge bg-primary">آقا</span>
                            <?php else: ?>
                                <span class="badge bg-danger">خانم</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $s['is_doctor_service'] ? '<span class="badge bg-info">دکتر</span>' : '<span class="badge bg-light text-dark">اپراتور</span>' ?>
                            <?php if ($s['require_same_gender_operator']): ?>
                                <span class="badge bg-warning text-dark" title="اپراتور هم‌جنس الزامی">هم‌جنس</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($s['is_active']): ?>
                                <span class="badge bg-success">فعال</span>
                            <?php else: ?>
                                <span class="badge bg-danger">غیرفعال</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/services/edit/<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="<?= BASE_URL ?>/services/toggle/<?= $s['id'] ?>" class="btn btn-sm btn-outline-<?= $s['is_active'] ? 'warning' : 'success' ?>" onclick="return confirm('وضعیت این خدمت تغییر کند؟')">
                                <i class="bi bi-<?= $s['is_active'] ? 'toggle-on' : 'toggle-off' ?>"></i>
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
    const activeFilter = document.getElementById('activeFilter');
    const categoryFilter = document.getElementById('categoryFilter');
    const table = document.getElementById('servicesTable');

    function liveFilter() {
        const q = searchInput.value.toLowerCase();
        const a = activeFilter.value;
        const c = categoryFilter.value;
        fetch('<?= BASE_URL ?>/services?search=' + encodeURIComponent(q) + '&is_active=' + a + '&category_id=' + c)
            .then(res => res.text())
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const newBody = doc.getElementById('servicesTable');
                if (newBody) table.innerHTML = newBody.innerHTML;
            });
    }

    let timer;
    searchInput.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(liveFilter, 300);
    });
    activeFilter.addEventListener('change', liveFilter);
    categoryFilter.addEventListener('change', liveFilter);
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>