<?php ob_start(); ?>
<div class="row g-4">
    <div class="col-md-5">
        <div class="card p-4">
            <h5 class="mb-3">جستجوی پرونده کلاینت</h5>
            <div class="input-group">
                <input type="text" id="fileSearch" class="form-control" placeholder="شماره پرونده ۶ رقمی" maxlength="6" pattern="[0-9]{6}">
                <button class="btn btn-brand" onclick="searchClient()">جستجو</button>
            </div>
            <div id="clientInfo" class="mt-3"></div>
        </div>
    </div>

    <div class="col-md-7">
        <div class="card p-4">
            <h5 class="mb-3">نوبت‌های امروز من (<?= Helpers::toJalaliString($today) ?>)</h5>

            <?php if (empty($appointments)): ?>
                <p class="text-muted text-center py-4">امروز نوبتی ندارید.</p>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($appointments as $a): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1"><?= Helpers::e($a['client_name']) ?> <small class="text-muted">(<?= Helpers::e($a['file_number']) ?>)</small></h6>
                                    <p class="mb-1 small">
                                        <b>خدمت:</b> <?= Helpers::e($a['service_name']) ?> (<?= $a['duration_minutes'] ?> دقیقه)<br>
                                        <b>ساعت:</b> <?= Helpers::e($a['start_time']) ?> - <?= Helpers::e($a['end_time']) ?><br>
                                        <b>موبایل:</b> <?= Helpers::e($a['client_mobile']) ?>
                                    </p>
                                </div>
                                <button class="btn btn-sm btn-brand" onclick="openCloseModal(<?= $a['id'] ?>, '<?= Helpers::e($a['client_name']) ?>')">
                                    <i class="bi bi-check2-circle"></i> بستن نوبت
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal بستن نوبت -->
<div class="modal fade" id="closeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">بستن نوبت <span id="closeClientName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="closeApptId">
                <label class="form-label">توضیحات خدمات ارائه‌شده / نکات جلسه بعد *</label>
                <textarea id="closeNote" class="form-control" rows="4" placeholder="اجباری..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                <button type="button" class="btn btn-brand" onclick="submitClose()">تایید و بستن</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function searchClient() {
    const fn = document.getElementById('fileSearch').value.trim();
    const box = document.getElementById('clientInfo');
    box.innerHTML = '<span class="text-muted">در حال جستجو...</span>';

    fetch('<?= BASE_URL ?>/operator/search-client?file_number=' + fn)
        .then(r => r.json())
        .then(res => {
            if (res.error) {
                box.innerHTML = `<div class="alert alert-danger">${res.error}</div>`;
                return;
            }
            const c = res.client;
            let html = `<div class="alert alert-success">
                <b>${c.full_name}</b><br>
                پرونده: ${c.file_number} — موبایل: ${c.mobile} — جنسیت: ${c.gender === 'male' ? 'آقا' : 'خانم'}
            </div>`;
            if (res.history.length === 0) {
                html += '<p class="text-muted">سابقه‌ای برای خدمات شما ثبت نشده.</p>';
            } else {
                html += '<h6>تاریخچه خدمات شما با این کلاینت:</h6><ul class="list-group">';
                res.history.forEach(h => {
                    html += `<li class="list-group-item small">
                        <b>${h.service_name}</b> — ${h.appointment_date} (${h.start_time}) — ${h.status}
                        ${h.operator_note ? '<br><span class="text-muted">' + h.operator_note + '</span>' : ''}
                    </li>`;
                });
                html += '</ul>';
            }
            box.innerHTML = html;
        });
}

function openCloseModal(id, name) {
    document.getElementById('closeApptId').value = id;
    document.getElementById('closeClientName').textContent = name;
    document.getElementById('closeNote').value = '';
    new bootstrap.Modal(document.getElementById('closeModal')).show();
}

function submitClose() {
    const fd = new FormData();
    fd.append('csrf_token', '<?= Helpers::csrfToken() ?>');
    fd.append('appointment_id', document.getElementById('closeApptId').value);
    fd.append('operator_note', document.getElementById('closeNote').value);

    fetch('<?= BASE_URL ?>/operator/close', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('closeModal')).hide();
                location.reload();
            } else {
                alert(res.error || 'خطا');
            }
        });
}
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>