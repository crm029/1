<?php 
ob_start(); 
[$current_jy, $current_jm, $current_jd] = Helpers::toJalaliArray(date('Y-m-d'));
$months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
?>
<div class="card p-4">
    <h5 class="mb-4">کد تخفیف جدید</h5>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/discounts/create/submit">
        <?= Helpers::csrfField() ?>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">کد تخفیف *</label>
                <input type="text" name="code" class="form-control text-uppercase" required pattern="[A-Za-z0-9_-]{3,30}" placeholder="مثلاً OFF20" style="font-family:monospace;letter-spacing:2px;">
                <small class="text-muted">حروف انگلیسی، عدد، _ و -</small>
            </div>
            <div class="col-md-4">
                <label class="form-label">خدمت مرتبط</label>
                <select name="service_id" class="form-select">
                    <option value="">همه خدمات</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= Helpers::e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">نوع *</label>
                <select name="discount_type" class="form-select" required>
                    <option value="percent">درصدی</option>
                    <option value="fixed">مبلغی (تومان)</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">مقدار *</label>
                <input type="number" name="discount_value" class="form-control" min="1" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">حداکثر استفاده</label>
                <input type="number" name="max_uses" class="form-control" min="1" placeholder="خالی = بی‌نهایت">
            </div>
            <div class="col-md-4">
                <label class="form-label">تاریخ شروع (شمسی)</label>
                <input type="text" id="startDateDisplay" class="form-control" readonly style="cursor:pointer;" placeholder="انتخاب تاریخ...">
                <input type="hidden" name="start_year" id="startYear">
                <input type="hidden" name="start_month" id="startMonth">
                <input type="hidden" name="start_day" id="startDay">
            </div>
            <div class="col-md-4">
                <label class="form-label">تاریخ پایان (شمسی)</label>
                <input type="text" id="endDateDisplay" class="form-control" readonly style="cursor:pointer;" placeholder="انتخاب تاریخ...">
                <input type="hidden" name="end_year" id="endYear">
                <input type="hidden" name="end_month" id="endMonth">
                <input type="hidden" name="end_day" id="endDay">
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-brand">ذخیره</button>
            <a href="<?= BASE_URL ?>/discounts" class="btn btn-light">انصراف</a>
        </div>
    </form>
</div>

<!-- Modal تقویم -->
<div class="modal fade" id="calendarModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="calTitle">انتخاب تاریخ</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="calendar-wrapper">
                    <div class="calendar-header">
                        <button type="button" class="btn btn-sm btn-light" id="calPrev">‹</button>
                        <span class="month-title" id="calMonthTitle">...</span>
                        <button type="button" class="btn btn-sm btn-light" id="calNext">›</button>
                    </div>
                    <div class="calendar-grid" id="calGrid"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" id="calClear">پاک کردن</button>
            </div>
        </div>
    </div>
</div>

<style>
.calendar-wrapper { background:#fff; padding:5px; }
.calendar-header {
    display:flex; justify-content:space-between; align-items:center;
    margin-bottom:15px; padding:8px 12px;
    background:#f8f5f5; border-radius:10px;
}
.calendar-header .month-title { font-weight:bold; font-size:1.05rem; color:#D1B1B0; }
.calendar-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; }
.calendar-grid .weekday {
    text-align:center; font-weight:bold; padding:6px 0;
    color:#888; font-size:0.85rem;
}
.calendar-grid .day {
    aspect-ratio:1; display:flex; align-items:center; justify-content:center;
    border-radius:8px; cursor:pointer; font-size:0.95rem;
    background:#fff; border:1px solid #eee; transition:all 0.15s; user-select:none;
}
.calendar-grid .day:hover { background:#D1B1B0; color:#fff; transform:scale(1.05); }
.calendar-grid .day.today { border:2px solid #D1B1B0; font-weight:bold; }
.calendar-grid .day.selected {
    background:#D1B1B0 !important; color:#fff !important; font-weight:bold;
    transform:scale(1.1); box-shadow:0 3px 10px rgba(209,177,176,0.4);
}
.calendar-grid .empty { aspect-ratio:1; }
</style>

<script>
const monthNames = <?= json_encode($months, JSON_UNESCAPED_UNICODE) ?>;
let calYear = <?= (int)$current_jy ?>;
let calMonth = <?= (int)$current_jm ?>;
let calTarget = null;
let selectedDate = null;
const todayGreg = '<?= date('Y-m-d') ?>';

function pad(n) { return n < 10 ? '0' + n : '' + n; }
function getMonthName(m) { return monthNames[m - 1] || ''; }

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

function renderCal() {
    document.getElementById('calMonthTitle').textContent = getMonthName(calMonth) + ' ' + calYear;

    var grid = document.getElementById('calGrid');
    grid.innerHTML = '';

    var weekdays = ['ش','ی','د','س','چ','پ','ج'];
    weekdays.forEach(function(w) {
        var d = document.createElement('div');
        d.className = 'weekday';
        d.textContent = w;
        grid.appendChild(d);
    });

    var firstDay = jalaliFirstDayOfWeek(calYear, calMonth);
    var days = getDaysInMonth(calYear, calMonth);

    for (var i = 0; i < firstDay; i++) {
        var e = document.createElement('div');
        e.className = 'empty';
        grid.appendChild(e);
    }

    for (var d = 1; d <= days; d++) {
        (function(day) {
            var gregStr = jalaliToGregorian(calYear, calMonth, day);
            var jalaliStr = calYear + '/' + pad(calMonth) + '/' + pad(day);

            var el = document.createElement('div');
            el.className = 'day';
            el.textContent = toPersianDigit(day);

            if (gregStr === todayGreg) el.classList.add('today');
            if (selectedDate === gregStr) el.classList.add('selected');

            el.addEventListener('click', function() {
                selectedDate = gregStr;

                if (calTarget === 'start') {
                    document.getElementById('startYear').value = calYear;
                    document.getElementById('startMonth').value = calMonth;
                    document.getElementById('startDay').value = day;
                    document.getElementById('startDateDisplay').value = jalaliStr;
                } else {
                    document.getElementById('endYear').value = calYear;
                    document.getElementById('endMonth').value = calMonth;
                    document.getElementById('endDay').value = day;
                    document.getElementById('endDateDisplay').value = jalaliStr;
                }

                bootstrap.Modal.getInstance(document.getElementById('calendarModal')).hide();
            });

            grid.appendChild(el);
        })(d);
    }
}

function openCal(target) {
    calTarget = target;
    document.getElementById('calTitle').textContent = (target === 'start' ? 'انتخاب تاریخ شروع' : 'انتخاب تاریخ پایان');

    // مقدار فعلی
    var y = document.getElementById(target === 'start' ? 'startYear' : 'endYear').value;
    var m = document.getElementById(target === 'start' ? 'startMonth' : 'endMonth').value;
    var d = document.getElementById(target === 'start' ? 'startDay' : 'endDay').value;

    if (y && m && d) {
        calYear = parseInt(y);
        calMonth = parseInt(m);
        selectedDate = jalaliToGregorian(calYear, calMonth, parseInt(d));
    } else {
        calYear = <?= (int)$current_jy ?>;
        calMonth = <?= (int)$current_jm ?>;
        selectedDate = null;
    }

    renderCal();
    new bootstrap.Modal(document.getElementById('calendarModal')).show();
}

document.getElementById('startDateDisplay').addEventListener('click', function() { openCal('start'); });
document.getElementById('endDateDisplay').addEventListener('click', function() { openCal('end'); });

document.getElementById('calPrev').addEventListener('click', function() {
    if (calMonth === 1) { calYear--; calMonth = 12; }
    else { calMonth--; }
    renderCal();
});

document.getElementById('calNext').addEventListener('click', function() {
    if (calMonth === 12) { calYear++; calMonth = 1; }
    else { calMonth++; }
    renderCal();
});

document.getElementById('calClear').addEventListener('click', function() {
    if (calTarget === 'start') {
        document.getElementById('startYear').value = '';
        document.getElementById('startMonth').value = '';
        document.getElementById('startDay').value = '';
        document.getElementById('startDateDisplay').value = '';
    } else {
        document.getElementById('endYear').value = '';
        document.getElementById('endMonth').value = '';
        document.getElementById('endDay').value = '';
        document.getElementById('endDateDisplay').value = '';
    }
    bootstrap.Modal.getInstance(document.getElementById('calendarModal')).hide();
});
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>