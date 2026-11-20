<?php ob_start(); ?>
<div class="card p-4">
    <h5 class="mb-4">مدیریت نظرات</h5>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row g-2 mb-4">
        <div class="col-md-5">
            <input type="text" id="liveSearch" class="form-control" placeholder="جستجو نام یا متن..." value="<?= Helpers::e($filters['search']) ?>">
        </div>
        <div class="col-md-3">
            <select id="statusFilter" class="form-select">
                <option value="">همه وضعیت‌ها</option>
                <option value="pending" <?= $filters['status'] === 'pending' ? 'selected' : '' ?>>در انتظار</option>
                <option value="approved" <?= $filters['status'] === 'approved' ? 'selected' : '' ?>>تایید شده</option>
                <option value="rejected" <?= $filters['status'] === 'rejected' ? 'selected' : '' ?>>رد شده</option>
            </select>
        </div>
    </div>

    <div id="commentsList">
        <?php if (empty($comments)): ?>
            <div class="text-center text-muted py-4">نظری یافت نشد</div>
        <?php else: foreach ($comments as $c): 
            $statusColor = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$c['status']] ?? 'secondary';
            $statusLabel = ['pending' => 'در انتظار', 'approved' => 'تایید شده', 'rejected' => 'رد شده'][$c['status']] ?? $c['status'];
        ?>
            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <b><?= Helpers::e($c['full_name']) ?></b>
                        <span class="badge bg-<?= $statusColor ?>"><?= $statusLabel ?></span>
                        <br>
                        <small class="text-muted"><?= Helpers::toJalaliString($c['created_at']) ?></small>
                    </div>
                    <div>
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <i class="bi bi-star<?= $i < (int)$c['rating'] ? '-fill' : '' ?>" style="color:#ffc107;"></i>
                        <?php endfor; ?>
                    </div>
                </div>

                <p class="mb-2"><?= nl2br(Helpers::e($c['message'])) ?></p>

                <?php if (!empty($c['reply'])): ?>
                    <div class="alert alert-light border mb-2">
                        <small><b>پاسخ شما:</b></small><br>
                        <small><?= nl2br(Helpers::e($c['reply'])) ?></small><br>
                        <small class="text-muted">— <?= Helpers::e($c['replied_by_name'] ?? '') ?> در <?= Helpers::toJalaliString($c['replied_at']) ?></small>
                    </div>
                <?php endif; ?>

                <form method="POST" action="<?= BASE_URL ?>/content/comments/reply/<?= $c['id'] ?>" class="mt-2">
                    <?= Helpers::csrfField() ?>
                    <div class="row g-2">
                        <div class="col-md-8">
                            <textarea name="reply" class="form-control form-control-sm" rows="2" placeholder="پاسخ شما..."><?= Helpers::e($c['reply'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select form-select-sm">
                                <option value="pending" <?= $c['status'] === 'pending' ? 'selected' : '' ?>>در انتظار</option>
                                <option value="approved" <?= $c['status'] === 'approved' ? 'selected' : '' ?>>تایید</option>
                                <option value="rejected" <?= $c['status'] === 'rejected' ? 'selected' : '' ?>>رد</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-sm btn-brand w-100">ذخیره</button>
                        </div>
                    </div>
                </form>

                <?php if (in_array(Auth::role(), ['super_admin','admin'])): ?>
                    <a href="<?= BASE_URL ?>/content/comments/delete/<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger mt-2" onclick="return confirm('حذف شود؟')">
                        <i class="bi bi-trash"></i> حذف
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<script>
const s = document.getElementById('liveSearch');
const st = document.getElementById('statusFilter');
const list = document.getElementById('commentsList');

function liveFilter() {
    const p = new URLSearchParams({ search: s.value, status: st.value });
    fetch('<?= BASE_URL ?>/content/comments?' + p.toString())
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nb = doc.getElementById('commentsList');
            if (nb) list.innerHTML = nb.innerHTML;
        });
}
let timer;
s.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(liveFilter, 300); });
st.addEventListener('change', liveFilter);
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>