<?php 
ob_start(); 
[$current_jy, $current_jm, $current_jd] = Helpers::toJalaliArray(date('Y-m-d'));
$jalali_date = Helpers::toJalaliString($date);
[$sel_jy, $sel_jm, $sel_jd] = Helpers::toJalaliArray($date);
?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h5 class="mb-0">صندوق روزانه</h5>
        <div class="input-group" style="max-width: 220px;">
            <input type="text" id="dateDisplay" class="form-control" readonly style="cursor:pointer;" value="<?= Helpers::e($jalali_date) ?>">
            <button type="button" class="btn btn-brand" id="openCalBtn"><i class="bi bi-calendar"></i></button>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <?php if ($register['is_closed']): ?>
        <div class="alert alert-info">
            <i class="bi bi-lock"></i> این صندوق در تاریخ <?= Helpers::toJalaliString($register['closed_at']) ?> بسته شده است.
        </div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="border rounded p-3 h-100">
                <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">دریافتی‌های روز</h6>
                <p><b>نقدی:</b> <?= number_format($stats['cash_income']) ?> تومان</p>
                <p><b>کارتخوان:</b> <?= number_format($stats['pos_income']) ?> تومان</p>
                <p><b>کارت‌به‌کارت:</b> <?= number_format($stats['card_to_card_income']) ?> تومان</p>
                <p><b>از این مبلغ، بیعانه:</b> <?= number_format($stats['deposit_income']) ?> تومان</p>
                <hr>
                <p><b>جمع هزینه‌ها:</b> <?= number_format($stats['total_expense']) ?> تومان</p>
                <p class="text-success"><b>خالص نقدی (دریافتی نقدی - هزینه):</b> <?= number_format($stats['net_cash']) ?> تومان</p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="border rounded p-3 h-100">
                <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">بستن / ذخیره صندوق</h6>
                <form method="POST" action="<?= BASE_URL ?>/accounting/cash-register/save">
                    <?= Helpers::csrfField() ?>
                    <input type="hidden" name="date" value="<?= Helpers::e($date) ?>">
                    <div class="mb-3">
                        <label class="form-label">موجودی ابتدای روز (تومان)</label>
                        <input type="number" name="opening_balance" id="opening" class="form-control" value="<?= (int)$register['opening_balance'] ?>" min="0" <?= $register['is_closed'] ? 'disabled' : '' ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">موجودی پایان روز (تومان)</label>
                        <input type="number" name="closing_balance" id="closing" class="form-control" value="<?= (int)$register['closing_balance'] ?>" min="0" <?= $register['is_closed'] ? 'disabled' : '' ?>>
                    </div>
                    <div class="alert alert-warning small" id="diffBox" style="display:none;">
                        <b>مغایرت:</b> <span id="diffValue"></span> تومان<br>
                        <label class="form-label mt-2">دلیل مغایرت (اجباری):</label>
                        <textarea name="difference_reason" class="form-control" rows="2" <?= $register['is_closed'] ? 'disabled' : '' ?>><?= Helpers::e($register['difference_reason']) ?></textarea>
                    </div>
                    <?php if (!$register['is_closed']): ?>
                        <div class="d-flex gap-2">
                            <button type="submit" name="action" value="save" class="btn btn-light">ذخیره موقت</button>
                            <button type="submit" name="action" value="close" class="btn btn-brand">بستن صندوق</button>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <hr class="my-4">
    <h6 class="mb-3">آخرین صندوق‌ها</h6>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>تاریخ</th>
                    <th>ابتدای روز</th>
                    <th>پایان روز</th>
                    <th>مغایرت</th>
                    <th>وضعیت</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $r): ?>
                    <tr>
                        <td><?= Helpers::toJalaliString($r['register_date']) ?></td>
                        <td><?= number_format($r['opening_balance']) ?></td>
                        <td><?= number_format($r['closing_balance'] ?? 0) ?></td>
                        <td>
                            <?php if ((int)$r['difference_amount'] === 0): ?>
                                <span class="text-success">مطابق</span>
                            <?php else: ?>
                                <span class="text-danger"><?= number_format($r['difference_amount']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($r['is_closed']): ?>
                                <span class="badge bg-success">بسته شده</span>
                            <?php else: ?>
                                <span class="badge bg-warning">باز</span>
                            <?php endif; ?>
                        </td>
                        <td><a href="?date=<?= $r['register_date'] ?>" class="btn btn-sm btn-light">مشاهده</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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

<script>
const opening = document.getElementById('opening');
const closing = document.getElementById('closing');
const netCash = <?= (int)$stats['net_cash'] ?>;
const diffBox = document.getElementById('diffBox');
const diffValue = document.getElementById('diffValue');

function calcDiff() {
    const o = parseInt(opening.value || 0);
    const c = parseInt(closing.value || 0);
    const expected = o + netCash;
    const diff = c - expected;
    if (diff === 0) {
        diffBox.style.display = 'none';
    } else {
        diffBox.style.display = 'block';
        diffValue.textContent = diff.toLocaleString();
    }
}
if (opening && closing) {
    opening.addEventListener('input', calcDiff);
    closing.addEventListener('input', calcDiff);
    calcDiff();
}

// ============ تقویم شمسی ============
let calYear = <?= (int)$sel_jy ?>;
let calMonth = <?= (int)$sel_jm ?>;
let calSelected = '<?= Helpers::e($date) ?>';
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
                // رفتن به همون تاریخ
                window.location.href = '?date=' + gregStr;
            });

            grid.appendChild(el);
        })(d);
    }
}

document.getElementById('openCalBtn').addEventListener('click', function() {
    renderCal();
    new bootstrap.Modal(document.getElementById('calendarModal')).show();
});

document.getElementById('dateDisplay').addEventListener('click', function() {
    renderCal();
    new bootstrap.Modal(document.getElementById('calendarModal')).show();
});

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
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>