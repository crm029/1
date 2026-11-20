<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">لیست دسته‌بندی خدمات</h5>
        <a href="<?= BASE_URL ?>/service-categories/create" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> دسته‌بندی جدید
        </a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <input type="text" id="liveSearch" class="form-control" placeholder="جستجو نام دسته..." value="<?= Helpers::e($filters['search']) ?>">
        </div>
        <div class="col-md-4">
            <select id="activeFilter" class="form-select">
                <option value="">همه</option>
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
                    <th>آیکون</th>
                    <th>رنگ</th>
                    <th>نام دسته</th>
                    <th>تعداد خدمات</th>
                    <th>ترتیب</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody id="categoriesTable">
                <?php if (empty($categories)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">دسته‌بندی یافت نشد</td></tr>
                <?php else: foreach ($categories as $c): ?>
                    <tr>
                        <td><?= $c['id'] ?></td>
                        <td>
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;background:<?= Helpers::e($c['color']) ?>20;color:<?= Helpers::e($c['color']) ?>;">
                                <i class="<?= Helpers::e($c['icon']) ?>"></i>
                            </span>
                        </td>
                        <td><span style="display:inline-block;width:25px;height:25px;border-radius:50%;background:<?= Helpers::e($c['color']) ?>;"></span></td>
                        <td>
                            <strong><?= Helpers::e($c['name']) ?></strong>
                            <?php if (!empty($c['description'])): ?>
                                <br><small class="text-muted"><?= Helpers::e($c['description']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-info"><?= $c['services_count'] ?> خدمت</span>
                        </td>
                        <td><?= $c['sort_order'] ?></td>
                        <td>
                            <?php if ($c['is_active']): ?>
                                <span class="badge bg-success">فعال</span>
                            <?php else: ?>
                                <span class="badge bg-danger">غیرفعال</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/service-categories/edit/<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="<?= BASE_URL ?>/service-categories/toggle/<?= $c['id'] ?>" class="btn btn-sm btn-outline-<?= $c['is_active'] ? 'warning' : 'success' ?>" onclick="return confirm('وضعیت این دسته تغییر کند؟')">
                                <i class="bi bi-<?= $c['is_active'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                            </a>
                            <?php if ($c['services_count'] == 0): ?>
                                <form method="POST" action="<?= BASE_URL ?>/service-categories/delete/<?= $c['id'] ?>" style="display:inline;" onsubmit="return confirm('این دسته حذف شود؟')">
                                    <?= Helpers::csrfField() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="این دسته دارای خدمت است و قابل حذف نیست">
                                    <i class="bi bi-trash"></i>
                                </button>
                            <?php endif; ?>
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
    const table = document.getElementById('categoriesTable');

    function liveFilter() {
        const q = searchInput.value.toLowerCase();
        const a = activeFilter.value;
        fetch('<?= BASE_URL ?>/service-categories?search=' + encodeURIComponent(q) + '&is_active=' + a)
            .then(res => res.text())
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const newBody = doc.getElementById('categoriesTable');
                if (newBody) table.innerHTML = newBody.innerHTML;
            });
    }

    let timer;
    searchInput.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(liveFilter, 300);
    });
    activeFilter.addEventListener('change', liveFilter);
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>