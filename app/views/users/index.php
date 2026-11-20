<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">لیست کاربران</h5>
        <a href="<?= BASE_URL ?>/users/create" class="btn btn-brand"><i class="bi bi-plus-lg"></i> کاربر جدید</a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <input type="text" id="liveSearch" class="form-control" placeholder="جستجو (نام، موبایل، نام کاربری)..." value="<?= Helpers::e($filters['search']) ?>">
        </div>
        <div class="col-md-4">
            <select id="roleFilter" class="form-select">
                <option value="">همه نقش‌ها</option>
                <?php foreach ($roles as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $filters['role'] === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>تصویر</th>
                    <th>نام</th>
                    <th>موبایل</th>
                    <th>نقش</th>
                    <th>جنسیت</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody id="usersTable">
                <?php if (empty($users)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">کاربری یافت نشد</td></tr>
                <?php else: foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td>
                            <?php if ($u['photo']): ?>
                                <img src="<?= Helpers::e(Helpers::privateMediaUrl($u['photo'])) ?>" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">
                            <?php else: ?>
                                <div style="width:40px;height:40px;border-radius:50%;background:#eee;display:flex;align-items:center;justify-content:center;">
                                    <i class="bi bi-person"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?= Helpers::e($u['full_name']) ?> <?= $u['is_doctor'] ? '<span class="badge bg-info">دکتر</span>' : '' ?></td>
                        <td><?= Helpers::e($u['mobile']) ?></td>
                        <td><span class="badge bg-secondary"><?= $roles[$u['role']] ?? $u['role'] ?></span></td>
                        <td><?= $u['gender'] === 'male' ? 'آقا' : 'خانم' ?></td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="badge bg-success">فعال</span>
                            <?php else: ?>
                                <span class="badge bg-danger">غیرفعال</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/users/edit/<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <a href="<?= BASE_URL ?>/users/toggle/<?= $u['id'] ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('تغییر وضعیت؟')"><i class="bi bi-power"></i></a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    const searchInput = document.getElementById('liveSearch');
    const roleFilter = document.getElementById('roleFilter');
    const table = document.getElementById('usersTable');

    function liveFilter() {
        const q = searchInput.value.toLowerCase();
        const r = roleFilter.value;
        fetch('<?= BASE_URL ?>/users?search=' + encodeURIComponent(q) + '&role=' + r)
            .then(res => res.text())
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const newBody = doc.getElementById('usersTable');
                if (newBody) table.innerHTML = newBody.innerHTML;
            });
    }

    let timer;
    searchInput.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(liveFilter, 300);
    });
    roleFilter.addEventListener('change', liveFilter);
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>