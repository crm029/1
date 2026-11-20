<?php ob_start(); ?>
<div class="card p-4">
    <h5 class="mb-4">ویرایش تعطیلی</h5>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/holidays/edit/<?= $holiday['id'] ?>/submit" id="holidayForm">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="holiday_date" id="holiday_date" value="<?= Helpers::e($holiday['holiday_date']) ?>" required>

        <div class="row g-3">
            <div class="col-md-7">
                <div class="calendar-wrapper">
                    <div class="calendar-header">
                        <button type="button" class="btn btn-sm btn-light" id="prevBtn">‹</button>
                        <span class="month-title" id="monthTitle">...</span>
                        <button type="button" class="btn btn-sm btn-light" id="nextBtn">›</button>
                    </div>
                    <div class="calendar-grid" id="calendarGrid"></div>
                    <div class="text-center text-muted small mt-2">
                        🟢 قابل انتخاب    🔴 تعطیل ثبت‌شده
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="border rounded p-3 mb-3">
                    <small class="text-muted">تاریخ انتخاب‌شده:</small>
                    <div class="fw-bold" id="selectedDisplay" style="color:<?= BRAND_COLOR ?>;font-size:1.2rem;">
                        <?= Helpers::e($initial_jalali) ?>
                    </div>
                </div>

                <label class="form-label">عنوان *</label>
                <input type="text" name="title" class="form-control" required value="<?= Helpers::e($holiday['title']) ?>">

                <div class="mt-4 d-flex gap-2">
                    <button class="btn btn-brand w-100">ذخیره تغییرات</button>
                    <a href="<?= BASE_URL ?>/holidays" class="btn btn-light">انصراف</a>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.calendar-wrapper { background:#fff; border:1px solid #eee; border-radius:12px; padding:15px; }
.calendar-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; padding:8px 12px; background:#f8f5f5; border-radius:10px; }
.calendar-header .month-title { font-weight:bold; font-size:1.1rem; color:#D1B1B0; }
.calendar-grid { display:grid; grid-template-columns:repeat(7,1fr); gap:4px; }
.calendar-grid .weekday { text-align:center; font-weight:bold; padding:6px 0; color:#888; font-size:0.85rem; }
.calendar-grid .day { aspect-ratio:1; display:flex; align-items:center; justify-content:center; border-radius:8px; cursor:pointer; font-size:0.95rem; background:#fff; border:1px solid #eee; transition:all 0.15s; user-select:none; }
.calendar-grid .day:hover:not(.disabled):not(.holiday) { background:#D1B1B0; color:#fff; transform:scale(1.05); }
.calendar-grid .day.disabled { background:#f8f8f8; color:#ccc; cursor:not-allowed; }
.calendar-grid .day.holiday { background:#ffe5e5; color:#dc3545; cursor:not-allowed; text-decoration:line-through; }
.calendar-grid .day.today { border:2px solid #D1B1B0; font-weight:bold; }
.calendar-grid .day.selected { background:#D1B1B0 !important; color:#fff !important; font-weight:bold; transform:scale(1.1); box-shadow:0 3px 10px rgba(209,177,176,0.4); }
.calendar-grid .empty { aspect-ratio:1; }
</style>

<script>
(function() {
    var current = null;
    var selected = { greg: '<?= Helpers::e($holiday['holiday_date']) ?>', jalali: '<?= Helpers::e($initial_jalali) ?>' };
    var holidays = [];
    var isEdit = true;
    var currentHolidayGreg = '<?= Helpers::e($holiday['holiday_date']) ?>';
    var initialJy = <?= (int)explode('/', $initial_jalali)[0] ?>;
    var initialJm = <?= (int)explode('/', $initial_jalali)[1] ?>;

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    function renderCalendar(data) {
        current = data;
        holidays = data.holidays;
        document.getElementById('monthTitle').textContent = data.month_name + ' ' + data.jy;

        var grid = document.getElementById('calendarGrid');
        grid.innerHTML = '';

        var weekdays = ['ش','ی','د','س','چ','پ','ج'];
        weekdays.forEach(function(w) {
            var d = document.createElement('div');
            d.className = 'weekday';
            d.textContent = w;
            grid.appendChild(d);
        });

        for (var i = 0; i < data.first_weekday; i++) {
            var e = document.createElement('div');
            e.className = 'empty';
            grid.appendChild(e);
        }

        for (var d = 1; d <= data.days; d++) {
            (function(day) {
                var jalaliStr = data.jy + '/' + pad(data.jm) + '/' + pad(day);
                var gregStr = jalaliToGregorian(data.jy, data.jm, day);

                var el = document.createElement('div');
                el.className = 'day';
                el.textContent = toPersianDigit(day);
                el.dataset.greg = gregStr;
                el.dataset.jalali = jalaliStr;

                var isHoliday = holidays.indexOf(gregStr) !== -1;
                var isToday = gregStr === data.today;
                var isSelf = (gregStr === currentHolidayGreg);

                if (isHoliday && !isSelf) el.classList.add('holiday');
                if (isToday) el.classList.add('today');
                if (selected && selected.greg === gregStr) el.classList.add('selected');

                el.addEventListener('click', function() {
                    if (el.classList.contains('holiday')) return;

                    document.querySelectorAll('.calendar-grid .day').forEach(function(x) {
                        x.classList.remove('selected');
                    });
                    el.classList.add('selected');

                    selected = { greg: gregStr, jalali: jalaliStr };
                    document.getElementById('holiday_date').value = gregStr;
                    document.getElementById('selectedDisplay').textContent = jalaliStr;
                });

                grid.appendChild(el);
            })(d);
        }
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

    function toPersianDigit(n) {
        var fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        return String(n).replace(/\d/g, function(d) { return fa[d]; });
    }

    function loadMonth(jy, jm) {
        fetch('<?= BASE_URL ?>/holidays/calendar?year=' + jy + '&month=' + jm)
            .then(r => r.json())
            .then(data => renderCalendar(data));
    }

    document.getElementById('prevBtn').addEventListener('click', function() {
        if (current) loadMonth(current.prev.year, current.prev.month);
    });
    document.getElementById('nextBtn').addEventListener('click', function() {
        if (current) loadMonth(current.next.year, current.next.month);
    });

    document.getElementById('holidayForm').addEventListener('submit', function(e) {
        if (!document.getElementById('holiday_date').value) {
            e.preventDefault();
            alert('لطفاً یک تاریخ انتخاب کنید');
        }
    });

    loadMonth(initialJy, initialJm);
})();
</script>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>