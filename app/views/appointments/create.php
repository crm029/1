<?php 
ob_start(); 
[$current_jy, $current_jm, $current_jd] = Helpers::toJalaliArray(date('Y-m-d'));
?>
<div class="card p-4">
    <h5 class="mb-4">نوبت جدید</h5>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/appointments/create/submit" id="apptForm">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="client_id" id="client_id">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">جستجوی کلاینت (نام / موبایل / شماره پرونده) *</label>
                <input type="text" id="clientSearch" class="form-control" autocomplete="off" placeholder="حداقل ۲ حرف...">
                <div id="clientResults" class="list-group mt-1" style="max-height:200px;overflow:auto;"></div>
                <div id="selectedClient" class="alert alert-success mt-2" style="display:none;"></div>
            </div>
        </div>

        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <label class="form-label">خدمت *</label>
                <select name="service_id" id="service_id" class="form-select" required>
                    <option value="">انتخاب کنید</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= $s['id'] ?>" data-duration="<?= $s['duration_minutes'] ?>"><?= Helpers::e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">اپراتور *</label>
                <select name="operator_id" id="operator_id" class="form-select" required disabled>
                    <option value="">ابتدا خدمت را انتخاب کنید</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">مبلغ بیعانه (تومان)</label>
                <input type="number" name="deposit_amount" class="form-control" value="<?= $deposit ?>" min="0">
            </div>
        </div>

        <div class="row g-3 mt-3">
            <div class="col-lg-7">
                <label class="form-label">تاریخ (شمسی) *</label>
                <div class="calendar-wrapper">
                    <div class="calendar-header">
                        <button type="button" class="btn btn-sm btn-light" id="prevBtn">‹</button>
                        <span class="month-title" id="monthTitle">...</span>
                        <button type="button" class="btn btn-sm btn-light" id="nextBtn">›</button>
                    </div>
                    <div class="calendar-grid" id="calendarGrid"></div>
                </div>
                <input type="hidden" name="appointment_date" id="appointment_date" required>
            </div>

            <div class="col-lg-5">
                <div class="border rounded p-3 mb-3">
                    <small class="text-muted">تاریخ انتخاب‌شده:</small>
                    <div class="fw-bold" id="selectedDateDisplay" style="color:<?= BRAND_COLOR ?>;font-size:1.2rem;">—</div>
                </div>

                <label class="form-label">ساعت‌های قابل رزرو *</label>
                <div id="slotsBox" class="d-flex flex-wrap gap-2">
                    <span class="text-muted">ابتدا تاریخ را انتخاب کنید</span>
                </div>
                <input type="hidden" name="start_time" id="start_time">
            </div>
        </div>

        <div class="mt-4">
            <button class="btn btn-brand">ثبت نوبت</button>
            <a href="<?= BASE_URL ?>/appointments" class="btn btn-light">انصراف</a>
        </div>
    </form>
</div>

<style>
.calendar-wrapper {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 12px;
    padding: 15px;
}
.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding: 8px 12px;
    background: #f8f5f5;
    border-radius: 10px;
}
.calendar-header .month-title {
    font-weight: bold;
    font-size: 1.1rem;
    color: #D1B1B0;
}
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
}
.calendar-grid .weekday {
    text-align: center;
    font-weight: bold;
    padding: 6px 0;
    color: #888;
    font-size: 0.85rem;
}
.calendar-grid .day {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.95rem;
    background: #fff;
    border: 1px solid #eee;
    transition: all 0.15s;
    user-select: none;
}
.calendar-grid .day:hover:not(.disabled) {
    background: #D1B1B0;
    color: #fff;
    transform: scale(1.05);
}
.calendar-grid .day.disabled {
    background: #f8f8f8;
    color: #ccc;
    cursor: not-allowed;
}
.calendar-grid .day.today {
    border: 2px solid #D1B1B0;
    font-weight: bold;
}
.calendar-grid .day.selected {
    background: #D1B1B0 !important;
    color: #fff !important;
    font-weight: bold;
    transform: scale(1.1);
    box-shadow: 0 3px 10px rgba(209,177,176,0.4);
}
.calendar-grid .empty {
    aspect-ratio: 1;
}
</style>

<script>
// ============ جستجوی Live کلاینت ============
let ctimer;
const cs = document.getElementById('clientSearch');
const cr = document.getElementById('clientResults');

cs.addEventListener('input', () => {
    clearTimeout(ctimer);
    if (cs.value.length < 2) { cr.innerHTML = ''; return; }
    ctimer = setTimeout(() => {
        fetch('<?= BASE_URL ?>/appointments/search-client?q=' + encodeURIComponent(cs.value))
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
                        document.getElementById('client_id').value = c.id;
                        document.getElementById('selectedClient').style.display = 'block';
                        document.getElementById('selectedClient').innerHTML = `کلاینت انتخاب شده: <b>${c.full_name}</b> (${c.file_number})`;
                        cs.value = '';
                        cr.innerHTML = '';
                    };
                    cr.appendChild(item);
                });
            });
    }, 300);
});

// ============ بارگذاری اپراتورها هنگام تغییر خدمت ============
document.getElementById('service_id').addEventListener('change', function() {
    const sid = this.value;
    const opSel = document.getElementById('operator_id');
    opSel.innerHTML = '<option value="">در حال بارگذاری...</option>';
    opSel.disabled = true;

    if (!sid) return;

    fetch('<?= BASE_URL ?>/appointments/service-operators/' + sid)
        .then(r => r.json())
        .then(list => {
            opSel.innerHTML = '<option value="">انتخاب کنید</option>';
            list.forEach(o => {
                const opt = document.createElement('option');
                opt.value = o.id;
                opt.textContent = o.name + (o.is_doctor ? ' (دکتر)' : '');
                opSel.appendChild(opt);
            });
            opSel.disabled = false;
            loadSlots();
        });
});

document.getElementById('operator_id').addEventListener('change', loadSlots);

// ============ تقویم شمسی ============
(function() {
    var currentYear = <?= (int)$current_jy ?>;
    var currentMonth = <?= (int)$current_jm ?>;
    var todayGreg = '<?= date('Y-m-d') ?>';
    var selectedDate = null;

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    function getMonthName(m) {
        var names = ['', 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
        return names[m] || '';
    }

    function getDaysInMonth(jy, jm) {
        if (jm <= 6) return 31;
        if (jm <= 11) return 30;
        var isLeap = ((jy + 12) % 33) % 4 === 1;
        return isLeap ? 30 : 29;
    }

    function jalaliToGregorian(jy, jm, jd) {
        jy += 1595;
        var days = -355668 + (365 * jy) + (Math.floor(jy / 33) * 8) + Math.floor(((jy % 33) + 3) / 4) + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
        var gy = 400 * Math.floor(days / 146097);
        days %= 146097;
        if (days > 36524) { gy += 100 * Math.floor(--days / 36524); days %= 36524; if (days >= 365) days++; }
        gy += 4 * Math.floor(days / 1461);
        days %= 1461;
        if (days > 365) { gy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
        var gd = days + 1;
        var sal_a = [0,31,((gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0) ? 29 : 28,31,30,31,30,31,31,30,31,30,31];
        var gm;
        for (gm = 0; gm < 13 && gd > sal_a[gm]; gm++) gd -= sal_a[gm];
        return gy + '-' + pad(gm) + '-' + pad(gd);
    }

    function jalaliFirstDayOfWeek(jy, jm) {
        var greg = jalaliToGregorian(jy, jm, 1);
        var d = new Date(greg + 'T00:00:00');
        var w = d.getDay();
        var map = {6:0, 0:1, 1:2, 2:3, 3:4, 4:5, 5:6};
        return map[w];
    }

    function toPersianDigit(n) {
        var fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        return String(n).replace(/\d/g, function(d) { return fa[d]; });
    }

    function renderCalendar() {
        document.getElementById('monthTitle').textContent = getMonthName(currentMonth) + ' ' + currentYear;

        var grid = document.getElementById('calendarGrid');
        grid.innerHTML = '';

        var weekdays = ['ش','ی','د','س','چ','پ','ج'];
        weekdays.forEach(function(w) {
            var d = document.createElement('div');
            d.className = 'weekday';
            d.textContent = w;
            grid.appendChild(d);
        });

        var firstDay = jalaliFirstDayOfWeek(currentYear, currentMonth);
        var days = getDaysInMonth(currentYear, currentMonth);

        for (var i = 0; i < firstDay; i++) {
            var e = document.createElement('div');
            e.className = 'empty';
            grid.appendChild(e);
        }

        for (var d = 1; d <= days; d++) {
            (function(day) {
                var jalaliStr = currentYear + '/' + pad(currentMonth) + '/' + pad(day);
                var gregStr = jalaliToGregorian(currentYear, currentMonth, day);

                var el = document.createElement('div');
                el.className = 'day';
                el.textContent = toPersianDigit(day);

                var isPast = gregStr < todayGreg;
                var isToday = gregStr === todayGreg;

                if (isPast) el.classList.add('disabled');
                if (isToday) el.classList.add('today');
                if (selectedDate === gregStr) el.classList.add('selected');

                if (!isPast) {
                    el.addEventListener('click', function() {
                        document.querySelectorAll('.calendar-grid .day').forEach(function(x) {
                            x.classList.remove('selected');
                        });
                        el.classList.add('selected');

                        selectedDate = gregStr;
                        document.getElementById('appointment_date').value = gregStr;
                        document.getElementById('selectedDateDisplay').textContent = jalaliStr;

                        loadSlots();
                    });
                }

                grid.appendChild(el);
            })(d);
        }
    }

    document.getElementById('prevBtn').addEventListener('click', function() {
        if (currentMonth === 1) { currentYear--; currentMonth = 12; }
        else { currentMonth--; }
        renderCalendar();
    });

    document.getElementById('nextBtn').addEventListener('click', function() {
        if (currentMonth === 12) { currentYear++; currentMonth = 1; }
        else { currentMonth++; }
        renderCalendar();
    });

    renderCalendar();
})();

// ============ بارگذاری اسلات‌ها ============
function loadSlots() {
    const sid = document.getElementById('service_id').value;
    const oid = document.getElementById('operator_id').value;
    const date = document.getElementById('appointment_date').value;
    const box = document.getElementById('slotsBox');
    document.getElementById('start_time').value = '';

    if (!sid || !oid || !date) {
        box.innerHTML = '<span class="text-muted">ابتدا اپراتور و تاریخ را انتخاب کنید</span>';
        return;
    }

    box.innerHTML = '<span class="text-muted">در حال بارگذاری...</span>';

    fetch(`<?= BASE_URL ?>/appointments/available-slots?service_id=${sid}&operator_id=${oid}&date=${date}`)
        .then(r => r.json())
        .then(res => {
            box.innerHTML = '';
            if (res.message) {
                box.innerHTML = `<span class="text-danger">${res.message}</span>`;
                return;
            }
            if (!res.slots || res.slots.length === 0) {
                box.innerHTML = '<span class="text-muted">اسلاتی موجود نیست</span>';
                return;
            }
            res.slots.forEach(sl => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm ' + (sl.available ? 'btn-outline-success' : 'btn-outline-danger');
                btn.textContent = sl.start + ' - ' + sl.end;
                btn.disabled = !sl.available;
                if (sl.available) {
                    btn.onclick = () => {
                        document.querySelectorAll('#slotsBox button').forEach(b => {
                            b.classList.remove('btn-success');
                            b.classList.add('btn-outline-success');
                        });
                        btn.classList.remove('btn-outline-success');
                        btn.classList.add('btn-success');
                        document.getElementById('start_time').value = sl.start;
                    };
                }
                box.appendChild(btn);
            });
        });
}
</script>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>