<?php 
ob_start(); 
[$current_jy, $current_jm, $current_jd] = Helpers::toJalaliArray(date('Y-m-d'));
$default_from = date('Y-m-d', strtotime('-30 days'));
$default_to = date('Y-m-d');
$default_from_jalali = Helpers::toJalaliString($default_from);
$default_to_jalali = Helpers::toJalaliString($default_to);
?>
<div class="card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <h5 class="mb-0">گزارش‌های مالی</h5>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <div class="input-group input-group-sm" style="width: 170px;">
                <input type="text" id="dateFromDisplay" class="form-control" readonly style="cursor:pointer;" value="<?= Helpers::e($default_from_jalali) ?>">
                <button type="button" class="btn btn-outline-secondary" data-open-cal="dateFrom"><i class="bi bi-calendar"></i></button>
            </div>
            <span class="text-muted small">تا</span>
            <div class="input-group input-group-sm" style="width: 170px;">
                <input type="text" id="dateToDisplay" class="form-control" readonly style="cursor:pointer;" value="<?= Helpers::e($default_to_jalali) ?>">
                <button type="button" class="btn btn-outline-secondary" data-open-cal="dateTo"><i class="bi bi-calendar"></i></button>
            </div>
            <input type="hidden" id="dateFrom" value="<?= Helpers::e($default_from) ?>">
            <input type="hidden" id="dateTo" value="<?= Helpers::e($default_to) ?>">
            <button onclick="loadAll()" class="btn btn-sm btn-brand">به‌روزرسانی</button>
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button onclick="setRange(7)" class="btn btn-sm btn-outline-secondary">۷ روز اخیر</button>
        <button onclick="setRange(30)" class="btn btn-sm btn-outline-secondary">۳۰ روز اخیر</button>
        <button onclick="setRange(90)" class="btn btn-sm btn-outline-secondary">۳ ماه اخیر</button>
        <button onclick="setRange(365)" class="btn btn-sm btn-outline-secondary">یک سال اخیر</button>
    </div>
</div>

<!-- کارت‌های خلاصه -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card p-4 text-center">
            <i class="bi bi-arrow-down-circle text-success" style="font-size:2rem;"></i>
            <h6 class="text-muted mt-2 mb-1">جمع درآمد</h6>
            <h4 class="text-success mb-0" id="totalIncome">0 تومان</h4>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 text-center">
            <i class="bi bi-arrow-up-circle text-danger" style="font-size:2rem;"></i>
            <h6 class="text-muted mt-2 mb-1">جمع هزینه</h6>
            <h4 class="text-danger mb-0" id="totalExpense">0 تومان</h4>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-4 text-center">
            <i class="bi bi-graph-up-arrow" style="font-size:2rem;color:<?= BRAND_COLOR ?>;"></i>
            <h6 class="text-muted mt-2 mb-1">سود خالص</h6>
            <h4 class="mb-0" style="color:<?= BRAND_COLOR ?>;" id="totalProfit">0 تومان</h4>
        </div>
    </div>
</div>

<!-- نمودار درآمد/هزینه -->
<div class="card p-4 mb-4">
    <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">روند درآمد، هزینه و سود</h6>
    <canvas id="revenueChart" height="90"></canvas>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card p-4 h-100">
            <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">پر درآمدترین خدمات</h6>
            <canvas id="servicesChart" height="200"></canvas>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card p-4 h-100">
            <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">دستمزد اپراتورها</h6>
            <canvas id="operatorsChart" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Modal تقویم -->
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
let revenueChart, servicesChart, operatorsChart;

// ============ تقویم شمسی ============
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
                    document.getElementById('dateFrom').value = gregStr;
                    document.getElementById('dateFromDisplay').value = jalaliStr;
                } else {
                    document.getElementById('dateTo').value = gregStr;
                    document.getElementById('dateToDisplay').value = jalaliStr;
                }

                bootstrap.Modal.getInstance(document.getElementById('calendarModal')).hide();
                loadAll();
            });

            grid.appendChild(el);
        })(d);
    }
}

function openCal(target) {
    calTarget = target;
    calSelected = (target === 'dateFrom') 
        ? document.getElementById('dateFrom').value 
        : document.getElementById('dateTo').value;

    var displayVal = (target === 'dateFrom') 
        ? document.getElementById('dateFromDisplay').value 
        : document.getElementById('dateToDisplay').value;

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

document.querySelectorAll('[data-open-cal]').forEach(function(btn) {
    btn.addEventListener('click', function() {
        openCal(btn.dataset.openCal);
    });
});

document.getElementById('dateFromDisplay').addEventListener('click', function() { openCal('dateFrom'); });
document.getElementById('dateToDisplay').addEventListener('click', function() { openCal('dateTo'); });

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

// ============ محدوده‌های سریع ============
function setRange(days) {
    var to = new Date();
    var from = new Date();
    from.setDate(from.getDate() - days);

    var fromStr = from.toISOString().split('T')[0];
    var toStr = to.toISOString().split('T')[0];

    document.getElementById('dateFrom').value = fromStr;
    document.getElementById('dateTo').value = toStr;

    // تبدیل به شمسی برای نمایش (از طریق fetch به سرور یا محاسبه محلی)
    // راه ساده‌تر: از یه endpoint استفاده کنیم که میلادی به شمسی تبدیل کنه
    fetch('<?= BASE_URL ?>/reports/convert-date?date=' + fromStr)
        .then(r => r.json())
        .then(res => { document.getElementById('dateFromDisplay').value = res.jalali; });

    fetch('<?= BASE_URL ?>/reports/convert-date?date=' + toStr)
        .then(r => r.json())
        .then(res => { document.getElementById('dateToDisplay').value = res.jalali; });

    loadAll();
}

// ============ بارگذاری داده‌ها ============
function loadAll() {
    var from = document.getElementById('dateFrom').value;
    var to = document.getElementById('dateTo').value;

    // درآمد
    fetch(`<?= BASE_URL ?>/reports/revenue-data?from=${from}&to=${to}`)
        .then(r => r.json())
        .then(res => {
            document.getElementById('totalIncome').textContent = res.totalIncome.toLocaleString() + ' تومان';
            document.getElementById('totalExpense').textContent = res.totalExpense.toLocaleString() + ' تومان';
            document.getElementById('totalProfit').textContent = res.totalProfit.toLocaleString() + ' تومان';

            if (revenueChart) revenueChart.destroy();
            revenueChart = new Chart(document.getElementById('revenueChart'), {
                type: 'line',
                data: {
                    labels: res.labels,
                    datasets: [
                        { label: 'درآمد', data: res.income, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,0.1)', tension: 0.3, fill: true },
                        { label: 'هزینه', data: res.expense, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.1)', tension: 0.3, fill: true },
                        { label: 'سود', data: res.profit, borderColor: '<?= BRAND_COLOR ?>', backgroundColor: 'rgba(209,177,176,0.2)', tension: 0.3, fill: true },
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom', labels: { font: { family: 'Tahoma' } } } },
                    scales: { y: { ticks: { callback: v => v.toLocaleString() } } }
                }
            });
        });

    // خدمات
    fetch(`<?= BASE_URL ?>/reports/services-data?from=${from}&to=${to}`)
        .then(r => r.json())
        .then(res => {
            if (servicesChart) servicesChart.destroy();
            servicesChart = new Chart(document.getElementById('servicesChart'), {
                type: 'doughnut',
                data: {
                    labels: res.labels,
                    datasets: [{
                        data: res.values,
                        backgroundColor: ['#D1B1B0','#b89393','#8e6f6e','#f5e6e6','#28a745','#17a2b8','#ffc107','#6f42c1','#fd7e14','#20c997'],
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom', labels: { font: { family: 'Tahoma', size: 11 } } } }
                }
            });
        });

    // اپراتورها
    fetch(`<?= BASE_URL ?>/reports/operators-data?from=${from}&to=${to}`)
        .then(r => r.json())
        .then(res => {
            if (operatorsChart) operatorsChart.destroy();
            operatorsChart = new Chart(document.getElementById('operatorsChart'), {
                type: 'bar',
                data: {
                    labels: res.labels,
                    datasets: [
                        { label: 'درآمد', data: res.revenues, backgroundColor: '#D1B1B0' },
                        { label: 'دستمزد', data: res.commissions, backgroundColor: '#28a745' },
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'bottom', labels: { font: { family: 'Tahoma' } } } },
                    scales: { y: { ticks: { callback: v => v.toLocaleString() } } }
                }
            });
        });
}

// لود اولیه
loadAll();
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>