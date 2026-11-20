<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h5 class="mb-0">مدیریت گالری</h5>
        <a href="<?= BASE_URL ?>/content/gallery/create" class="btn btn-brand"><i class="bi bi-plus-lg"></i> تصویر جدید</a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row g-2 mb-4">
        <div class="col-md-4">
            <input type="text" id="liveSearch" class="form-control" placeholder="جستجو عنوان..." value="<?= Helpers::e($filters['search']) ?>">
        </div>
        <div class="col-md-3">
            <select id="activeFilter" class="form-select">
                <option value="">همه</option>
                <option value="1" <?= $filters['is_active'] === '1' ? 'selected' : '' ?>>فعال</option>
                <option value="0" <?= $filters['is_active'] === '0' ? 'selected' : '' ?>>غیرفعال</option>
            </select>
        </div>
    </div>

    <div class="row g-3" id="galleryGrid">
        <?php if (empty($items)): ?>
            <div class="col-12 text-center text-muted py-4">تصویری یافت نشد</div>
        <?php else: foreach ($items as $g): ?>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="row g-0">
                        <div class="col-6">
                            <img src="<?= BASE_URL ?>/public/<?= Helpers::e($g['before_image']) ?>" style="width:100%;height:150px;object-fit:cover;border-top-right-radius:15px;">
                            <div class="text-center small bg-light py-1">قبل</div>
                        </div>
                        <div class="col-6">
                            <img src="<?= BASE_URL ?>/public/<?= Helpers::e($g['after_image']) ?>" style="width:100%;height:150px;object-fit:cover;border-top-left-radius:15px;">
                            <div class="text-center small bg-light py-1">بعد</div>
                        </div>
                    </div>
                    <div class="p-3">
                        <h6 class="mb-1"><?= Helpers::e($g['title'] ?? 'بدون عنوان') ?></h6>
                        <p class="small text-muted mb-2"><?= Helpers::e($g['service_name'] ?? '—') ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <?php if ($g['is_active']): ?>
                                <span class="badge bg-success">فعال</span>
                            <?php else: ?>
                                <span class="badge bg-danger">غیرفعال</span>
                            <?php endif; ?>
                            <div>
                                <a href="<?= BASE_URL ?>/content/gallery/edit/<?= $g['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                <a href="<?= BASE_URL ?>/content/gallery/toggle/<?= $g['id'] ?>" class="btn btn-sm btn-outline-<?= $g['is_active'] ? 'warning' : 'success' ?>" onclick="return confirm('تغییر وضعیت؟')">
                                    <i class="bi bi-<?= $g['is_active'] ? 'toggle-on' : 'toggle-off' ?>"></i>
                                </a>
                                <?php if (in_array(Auth::role(), ['super_admin','admin'])): ?>
                                    <a href="<?= BASE_URL ?>/content/gallery/delete/<?= $g['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('حذف شود؟')"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<script>
const s = document.getElementById('liveSearch');
const a = document.getElementById('activeFilter');
const grid = document.getElementById('galleryGrid');

function liveFilter() {
    const p = new URLSearchParams({ search: s.value, is_active: a.value });
    fetch('<?= BASE_URL ?>/content/gallery?' + p.toString())
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nb = doc.getElementById('galleryGrid');
            if (nb) grid.innerHTML = nb.innerHTML;
        });
}
let timer;
s.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(liveFilter, 300); });
a.addEventListener('change', liveFilter);
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>