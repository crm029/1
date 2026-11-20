<?php ob_start(); ?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card p-4">
                    <h4 class="mb-2">انتخاب زمان</h4>
                    <p class="text-muted small mb-4">گام ۳ از ۴ — برای هر خدمت، اپراتور، تاریخ و ساعت را مشخص کنید</p>

                    <?php if (!empty($_SESSION['booking_error'])): ?>
                        <div class="alert alert-danger"><?= Helpers::e($_SESSION['booking_error']); unset($_SESSION['booking_error']); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="<?= BASE_URL ?>/booking/schedule/submit" id="scheduleForm">
                        <?= Helpers::csrfField() ?>

                        <?php foreach ($services as $s): ?>
                            <div class="border rounded p-3 mb-4">
                                <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">
                                    <?= Helpers::e($s['name']) ?> 
                                    <small class="text-muted">(<?= $s['duration'] ?> دقیقه)</small>
                                    <?= $s['is_doctor'] ? '<span class="badge bg-info">دکتر</span>' : '' ?>
                                </h6>

                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">انتخاب اپراتور</label>
                                        <select name="operator_<?= $s['id'] ?>" id="op_<?= $s['id'] ?>" class="form-select" data-service="<?= $s['id'] ?>">
                                            <option value="">در حال بارگذاری...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">تاریخ</label>
                                        <input type="text" id="date_<?= $s['id'] ?>" class="form-control bg-light" readonly placeholder="ابتدا اپراتور را انتخاب کنید">
                                        <input type="hidden" name="date_<?= $s['id'] ?>" id="date_hidden_<?= $s['id'] ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">ساعت</label>
                                        <input type="text" id="time_<?= $s['id'] ?>" class="form-control bg-light" readonly placeholder="ابتدا تاریخ را انتخاب کنید">
                                        <input type="hidden" name="time_<?= $s['id'] ?>" id="time_hidden_<?= $s['id'] ?>">
                                    </div>
                                </div>

                                <!-- تقویم گرافیکی -->
                                <div id="calendar_<?= $s['id'] ?>" class="mt-3" style="display:none;">
                                    <div class="d-flex justify-content-between mb-2 small text-muted">
                                        <span><span style="display:inline-block;width:12px;height:12px;background:#28a745;border-radius:2px;"></span> قابل انتخاب</span>
                                        <span><span style="display:inline-block;width:12px;height:12px;background:#dc3545;border-radius:2px;"></span> تکمیل</span>
                                        <span><span style="display:inline-block;width:12px;height:12px;background:#e9ecef;border-radius:2px;"></span> عدم حضور</span>
                                    </div>
                                    <div class="cal-grid" id="calGrid_<?= $s['id'] ?>"></div>
                                </div>

                                <!-- اسلات‌های ساعت -->
                                <div id="slots_<?= $s['id'] ?>" class="mt-3" style="display:none;">
                                    <label class="form-label">ساعت‌های قابل رزرو</label>
                                    <div class="slot-grid" id="slotGrid_<?= $s['id'] ?>"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= BASE_URL ?>/booking/services" class="btn btn-light">بازگشت</a>
                            <button class="btn btn-brand">پیش‌نمایش و ادامه</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
    .cal-day {
        aspect-ratio: 1; display: flex; align-items: center; justify-content: center;
        border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: bold;
        transition: transform 0.1s;
    }
    .cal-day:hover { transform: scale(1.08); }
    .cal-day.gray { background: #e9ecef; color: #999; cursor: not-allowed; }
    .cal-day.red { background: #dc3545; color: #fff; cursor: not-allowed; }
    .cal-day.green { background: #28a745; color: #fff; }
    .cal-day.selected { border: 3px solid <?= BRAND_COLOR ?>; }
    .slot-grid { display: flex; flex-wrap: wrap; gap: 6px; }
    .slot-btn {
        padding: 6px 12px; border-radius: 6px; border: 1px solid #ddd;
        background: #fff; cursor: pointer; font-size: 0.85rem;
    }
    .slot-btn.available { background: #28a745; color: #fff; border-color: #28a745; }
    .slot-btn.taken { background: #dc3545; color: #fff; border-color: #dc3545; cursor: not-allowed; }
    .slot-btn.selected { border: 3px solid <?= BRAND_COLOR ?>; }
</style>

<script>
// بارگذاری اپراتورها برای هر خدمت
document.querySelectorAll('select[data-service]').forEach(sel => {
    const sid = sel.dataset.service;
    fetch('<?= BASE_URL ?>/booking/operators/' + sid)
        .then(r => r.json())
        .then(res => {
            const list = res.operators || [];
            const isDoctor = res.is_doctor_service;
            sel.innerHTML = '<option value="">' + (isDoctor ? 'انتخاب دکتر' : 'انتخاب اپراتور') + '</option>';
            sel.innerHTML += '<option value="0">رندوم (ارجاع به منشی)</option>';
            list.forEach(o => {
                const opt = document.createElement('option');
                opt.value = o.id;
                opt.textContent = o.name + (o.is_doctor ? ' (دکتر)' : '');
                sel.appendChild(opt);
            });
        });

    sel.addEventListener('change', () => {
        const sid = sel.dataset.service;
        const opId = sel.value;
        resetDates(sid);
        resetSlots(sid);
        if (opId === '') return;
        if (opId === '0') {
            // رندوم: پیام
            document.getElementById('calGrid_' + sid).innerHTML = '<div class="text-muted small">در حالت رندوم، منشی زمان‌بندی را انجام می‌دهد و به شما اطلاع می‌دهد.</div>';
            document.getElementById('calendar_' + sid).style.display = 'block';
            return;
        }
        loadDates(sid, opId);
    });
});

function resetDates(sid) {
    document.getElementById('date_' + sid).value = '';
    document.getElementById('date_hidden_' + sid).value = '';
    document.getElementById('calendar_' + sid).style.display = 'none';
    document.getElementById('calGrid_' + sid).innerHTML = '';
}
function resetSlots(sid) {
    document.getElementById('time_' + sid).value = '';
    document.getElementById('time_hidden_' + sid).value = '';
    document.getElementById('slots_' + sid).style.display = 'none';
    document.getElementById('slotGrid_' + sid).innerHTML = '';
}

function loadDates(sid, opId) {
    const grid = document.getElementById('calGrid_' + sid);
    document.getElementById('calendar_' + sid).style.display = 'block';
    grid.innerHTML = '<div class="text-muted small">در حال بارگذاری...</div>';
    fetch(`<?= BASE_URL ?>/booking/dates?operator_id=${opId}&service_id=${sid}`)
        .then(r => r.json())
        .then(res => {
            grid.innerHTML = '';
            ['ش','ی','د','س','چ','پ','ج'].forEach(w => {
                const d = document.createElement('div');
                d.className = 'text-center small fw-bold text-muted';
                d.textContent = w;
                grid.appendChild(d);
            });
            if (!res.dates || res.dates.length === 0) {
                grid.innerHTML += '<div class="text-muted small">تاریخی موجود نیست</div>';
                return;
            }

            // اضافه کردن جای خالی ابتدای ماه بر اساس روز هفته اول
            const firstWeekday = res.dates[0].weekday;
            const startOffset = (firstWeekday + 1) % 7; // شنبه = 0
            for (let i = 0; i < startOffset; i++) {
                const blank = document.createElement('div');
                grid.appendChild(blank);
            }

            // اگه ماه عوض شد، اسم ماه رو نشون بده
            const monthNames = ['', 'فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
            let lastMonth = 0;

            res.dates.forEach(d => {
                if (d.jalali_month !== lastMonth && lastMonth !== 0) {
                    // مرز ماه جدید
                    const sep = document.createElement('div');
                    sep.style.gridColumn = '1 / -1';
                    sep.className = 'text-center small fw-bold mt-2 mb-1';
                    sep.style.color = '<?= BRAND_COLOR ?>';
                    sep.textContent = monthNames[d.jalali_month] + ' ' + d.jalali_year;
                    grid.appendChild(sep);
                } else if (lastMonth === 0) {
                    const sep = document.createElement('div');
                    sep.style.gridColumn = '1 / -1';
                    sep.className = 'text-center small fw-bold mb-1';
                    sep.style.color = '<?= BRAND_COLOR ?>';
                    sep.textContent = monthNames[d.jalali_month] + ' ' + d.jalali_year;
                    grid.appendChild(sep);
                }
                lastMonth = d.jalali_month;

                const el = document.createElement('div');
                el.className = 'cal-day ' + d.status;
                el.textContent = d.jalali_day;
                if (d.status === 'green') {
                    el.onclick = () => selectDate(sid, opId, d.date, el);
                }
                grid.appendChild(el);
            });
        });
}

function selectDate(sid, opId, date, el) {
    document.querySelectorAll('#calGrid_' + sid + ' .cal-day').forEach(x => x.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('date_' + sid).value = date;
    document.getElementById('date_hidden_' + sid).value = date;
    resetSlots(sid);
    loadSlots(sid, opId, date);
}

function loadSlots(sid, opId, date) {
    const box = document.getElementById('slotGrid_' + sid);
    document.getElementById('slots_' + sid).style.display = 'block';
    box.innerHTML = '<span class="text-muted small">در حال بارگذاری...</span>';
    fetch(`<?= BASE_URL ?>/booking/slots?operator_id=${opId}&service_id=${sid}&date=${date}`)
        .then(r => r.json())
        .then(res => {
            box.innerHTML = '';
            if (res.message) {
                box.innerHTML = '<span class="text-danger small">' + res.message + '</span>';
                return;
            }
            if (!res.slots || res.slots.length === 0) {
                box.innerHTML = '<span class="text-muted small">ساعتی موجود نیست</span>';
                return;
            }
            res.slots.forEach(sl => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'slot-btn ' + (sl.available ? 'available' : 'taken');
                b.textContent = sl.start + ' - ' + sl.end;
                b.disabled = !sl.available;
                if (sl.available) {
                    b.onclick = () => {
                        document.querySelectorAll('#slotGrid_' + sid + ' .slot-btn').forEach(x => x.classList.remove('selected'));
                        b.classList.add('selected');
                        document.getElementById('time_' + sid).value = sl.start;
                        document.getElementById('time_hidden_' + sid).value = sl.start;
                    };
                }
                box.appendChild(b);
            });
        });
}
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/public.php'; ?>