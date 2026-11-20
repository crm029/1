<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">فاکتور جدید</h5>
        <a href="<?= BASE_URL ?>/accounting/invoices" class="btn btn-light">بازگشت</a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/accounting/invoices/create/submit" id="invForm">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="client_id" id="client_id">

        <div class="mb-3">
            <label class="form-label">جستجوی کلاینت (نام / موبایل / پرونده) *</label>
            <input type="text" id="clientSearch" class="form-control" autocomplete="off" placeholder="حداقل ۲ حرف...">
            <div id="clientResults" class="list-group mt-1" style="max-height:200px;overflow:auto;"></div>
            <div id="selectedClient" class="alert alert-success mt-2" style="display:none;"></div>
        </div>

        <div id="apptsBox" style="display:none;">
            <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">انتخاب نوبت‌ها (چند تایی)</h6>
            <div id="apptsList" class="border rounded p-3 mb-3"></div>
        </div>

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">مبلغ کل</label>
                <input type="text" id="totalAmount" class="form-control bg-light" readonly value="0">
            </div>
            <div class="col-md-4">
                <label class="form-label">تخفیف (تومان)</label>
                <input type="number" name="discount_amount" id="discountAmount" class="form-control" min="0" value="0">
            </div>
            <div class="col-md-4">
                <label class="form-label">قابل پرداخت</label>
                <input type="text" id="payableAmount" class="form-control bg-light" readonly value="0">
            </div>
            <div class="col-md-12">
                <label class="form-label">توضیحات</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>
        </div>

        <div class="mt-4">
            <button class="btn btn-brand">ثبت فاکتور</button>
            <a href="<?= BASE_URL ?>/accounting/invoices" class="btn btn-light">انصراف</a>
        </div>
    </form>
</div>

<script>
let ctimer;
const cs = document.getElementById('clientSearch');
const cr = document.getElementById('clientResults');

cs.addEventListener('input', () => {
    clearTimeout(ctimer);
    if (cs.value.length < 2) { cr.innerHTML = ''; return; }
    ctimer = setTimeout(() => {
        fetch('<?= BASE_URL ?>/accounting/invoices/search-client?q=' + encodeURIComponent(cs.value))
            .then(r => r.json())
            .then(list => {
                cr.innerHTML = '';
                list.forEach(c => {
                    const item = document.createElement('a');
                    item.href = '#';
                    item.className = 'list-group-item list-group-item-action';
                    item.innerHTML = `<b>${c.full_name}</b> — پرونده: ${c.file_number} — ${c.mobile}`;
                    item.onclick = (e) => {
                        e.preventDefault();
                        selectClient(c);
                    };
                    cr.appendChild(item);
                });
            });
    }, 300);
});

function selectClient(c) {
    document.getElementById('client_id').value = c.id;
    document.getElementById('selectedClient').style.display = 'block';
    document.getElementById('selectedClient').innerHTML = `کلاینت: <b>${c.full_name}</b> (${c.file_number})`;
    cs.value = '';
    cr.innerHTML = '';
    loadAppointments(c.id);
}

function loadAppointments(client_id) {
    const box = document.getElementById('apptsBox');
    const list = document.getElementById('apptsList');
    box.style.display = 'block';
    list.innerHTML = '<span class="text-muted">در حال بارگذاری...</span>';

    fetch('<?= BASE_URL ?>/accounting/invoices/client-appointments/' + client_id)
        .then(r => r.json())
        .then(appts => {
            list.innerHTML = '';
            if (!appts || appts.length === 0) {
                list.innerHTML = '<span class="text-muted">نوبت قابل فاکتوری برای این کلاینت وجود ندارد</span>';
                return;
            }
            appts.forEach(a => {
                const label = document.createElement('label');
                label.className = 'd-block border-bottom py-2';
                label.innerHTML = `
                    <input type="checkbox" name="appointment_ids[]" value="${a.id}" data-price="${a.service_price}" onchange="calcTotals()">
                    <b>${a.service_name}</b> — کد: ${a.booking_code} — تاریخ: ${a.appointment_date} — مبلغ: ${Number(a.service_price).toLocaleString()} تومان
                `;
                list.appendChild(label);
            });
        });
}

function calcTotals() {
    let total = 0;
    document.querySelectorAll('input[name="appointment_ids[]"]:checked').forEach(cb => {
        total += parseInt(cb.dataset.price);
    });
    const disc = parseInt(document.getElementById('discountAmount').value || 0);
    const payable = Math.max(0, total - disc);
    document.getElementById('totalAmount').value = total.toLocaleString();
    document.getElementById('payableAmount').value = payable.toLocaleString();
}

document.getElementById('discountAmount').addEventListener('input', calcTotals);
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>