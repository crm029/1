<?php 
ob_start(); 
[$current_jy, $current_jm, $current_jd] = Helpers::toJalaliArray(date('Y-m-d'));
?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h5 class="mb-0">تراکنش‌ها</h5>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/accounting/cash-register" class="btn btn-outline-primary"><i class="bi bi-safe"></i> صندوق روزانه</a>
            <a href="<?= BASE_URL ?>/accounting/expense" class="btn btn-brand"><i class="bi bi-plus-lg"></i> ثبت هزینه</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="row g-2 mb-4">
        <div class="col-md-3">
            <input type="text" id="liveSearch" class="form-control" placeholder="جستجو توضیحات، کلاینت، کد نوبت..." value="<?= Helpers::e($filters['search']) ?>">
        </div>
        <div class="col-md-2">
            <select id="typeFilter" class="form-select">
                <option value="">همه انواع</option>
                <?php foreach ($types as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $filters['type'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <div class="input-group">
                <input type="text" id="dateFromDisplay" class="form-control" placeholder="از تاریخ..." readonly style="cursor:pointer;" value="<?= !empty($filters['date_from']) ? Helpers::toJalaliString($filters['date_from']) : '' ?>">
                <button type="button" class="btn btn-outline-secondary" data-clear="dateFrom" title="پاک کردن"><i class="bi bi-x"></i></button>
            </div>
            <input type="hidden" id="dateFrom" value="<?= Helpers::e($filters['date_from']) ?>">
        </div>
        <div class="col-md-3">
            <div class="input-group">
                <input type="text" id="dateToDisplay" class="form-control" placeholder="تا تاریخ..." readonly style="cursor:pointer;" value="<?= !empty($filters['date_to']) ? Helpers::toJalaliString($filters['date_to']) : '' ?>">
                <button type="button" class="btn btn-outline-secondary" data-clear="dateTo" title="پاک کردن"><i class="bi bi-x"></i></button>
            </div>
            <input type="hidden" id="dateTo" value="<?= Helpers::e($filters['date_to']) ?>">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>نوع</th>
                    <th>مبلغ</th>
                    <th>روش پرداخت</th>
                    <th>توضیحات</th>
                    <th>کلاینت / کد نوبت</th>
                    <th>ثبت‌کننده</th>
                    <th>تاریخ</th>
                </tr>
            </thead>
            <tbody id="trTable">
                <?php if (empty($transactions)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">تراکنشی یافت نشد</td></tr>
                <?php else: foreach ($transactions as $t): 
                    $methodMap = ['cash'=>'نقدی','pos'=>'کارتخوان','card_to_card'=>'کارت‌به‌کارت','online'=>'آنلاین'];
                    $typeColor = in_array($t['type'], ['deposit','payment']) ? 'success' : 'danger';
                ?>
                    <tr>
                        <td><?= $t['id'] ?></td>
                        <td><span class="badge bg-<?= $typeColor ?>"><?= $types[$t['type']] ?? $t['type'] ?></span></td>
                        <td><?= number_format($t['amount']) ?> تومان</td>
                        <td><?= $methodMap[$t['payment_method']] ?? '—' ?></td>
                        <td><?= Helpers::e($t['description']) ?></td>
                        <td>
                            <?php if ($t['client_name']): ?>
                                <?= Helpers::e($t['client_name']) ?>
                                <?php if ($t['booking_code']): ?>
                                    <br><small class="text-muted"><?= Helpers::e($t['booking_code']) ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td><?= Helpers::e($t['creator_name'] ?? '—') ?></td>
                        <td><?= Helpers::toJalaliString($t['created_at']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal تقویم شمسی -->
<div class="modal fade" id="calendarModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">انتخاب تاریخ</h6>
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
const s = document.getElementById('liveSearch');
const t = document.getElementById('typeFilter');
const df = document.getElementById('dateFrom');
const dt = document.getElementById('dateTo');
const dfDisplay = document.getElementById('dateFromDisplay');
const dtDisplay = document.getElementById('dateToDisplay');
const table = document.getElementById('trTable');

function liveFilter() {
    const p = new URLSearchParams({ search: s.value, type: t.value, date_from: df.value, date_to: dt.value });
    fetch('<?= BASE_URL ?>/accounting?' + p.toString())
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const nb = doc.getElementById('trTable');
            if (nb) table.innerHTML = nb.innerHTML;
        });
}
let timer;
s.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(liveFilter, 300); });
[t].forEach(el => el.addEventListener('change', liveFilter));

// ============ تقویم شمسی مشترک ============
let calYear = <?= (int)$current_jy ?>;
let calMonth = <?= (int)$current_jm ?>;
let calSelected = null;
let calTarget = null;
const todayGreg = '<?= date('Y-m-d') ?>';

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
            if (calSelected === gregStr) el.classList.add('selected');

            el.addEventListener('click', function() {
                calSelected = gregStr;
                
                if (calTarget === 'dateFrom') {
                    df.value = gregStr;
                    dfDisplay.value = jalaliStr;
                } else {
                    dt.value = gregStr;
                    dtDisplay.value = jalaliStr;
                }

                var modalEl = document.getElementById('calendarModal');
                bootstrap.Modal.getInstance(modalEl).hide();
                liveFilter();
            });

            grid.appendChild(el);
        })(d);
    }
}

function openCalendar(target) {
    calTarget = target;
    calSelected = (target === 'dateFrom') ? df.value : dt.value;
    var displayVal = (target === 'dateFrom') ? dfDisplay.value : dtDisplay.value;
    
    if (displayVal) {
        var parts = displayVal.split('/');
        if (parts.length === 3) {
            calYear = parseInt(parts[0]);
            calMonth = parseInt(parts[1]);
        }
    }
    
    renderCal();
    new bootstrap.Modal(document.getElementById('calendarModal')).show();
}

dfDisplay.addEventListener('click', () => openCalendar('dateFrom'));
dtDisplay.addEventListener('click', () => openCalendar('dateTo'));

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

// پاک کردن
document.querySelectorAll('[data-clear]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var target = btn.dataset.clear;
        if (target === 'dateFrom') {
            df.value = '';
            dfDisplay.value = '';
        } else {
            dt.value = '';
            dtDisplay.value = '';
        }
        liveFilter();
    });
});
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>