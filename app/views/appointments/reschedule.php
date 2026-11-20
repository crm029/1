<?php 
ob_start(); 
[$current_jy, $current_jm, $current_jd] = Helpers::toJalaliArray(date('Y-m-d'));
?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">موکول کردن نوبت <span class="badge bg-dark"><?= Helpers::e($appt['booking_code']) ?></span></h5>
        <a href="<?= BASE_URL ?>/appointments/show/<?= $appt['id'] ?>" class="btn btn-light">بازگشت</a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="alert alert-info">
        <p class="mb-1"><b>کلاینت:</b> <?= Helpers::e($appt['client_name']) ?> (<?= Helpers::e($appt['file_number']) ?>)</p>
        <p class="mb-1"><b>خدمت:</b> <?= Helpers::e($appt['service_name']) ?> (<?= $appt['duration_minutes'] ?> دقیقه)</p>
        <p class="mb-1"><b>اپراتور:</b> <?= Helpers::e($appt['operator_name'] ?? '—') ?></p>
        <p class="mb-0"><b>تاریخ فعلی:</b> <?= Helpers::toJalaliString($appt['appointment_date']) ?> ساعت <?= Helpers::e($appt['start_time']) ?></p>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/appointments/reschedule/<?= $appt['id'] ?>/submit" id="rescheduleForm">
        <?= Helpers::csrfField() ?>
        <input type="hidden" name="new_date" id="newDate" required>
        <input type="hidden" name="new_start" id="newStart" required>

        <h6 class="mb-3 mt-4" style="color:<?= BRAND_COLOR ?>;">۱. انتخاب تاریخ جدید (شمسی)</h6>

        <div class="d-flex flex-wrap gap-3 mb-3 small">
            <span><span style="display:inline-block;width:12px;height:12px;background:#28a745;border-radius:3px;"></span> قابل انتخاب</span>
            <span><span style="display:inline-block;width:12px;height:12px;background:#dc3545;border-radius:3px;"></span> تکمیل</span>
            <span><span style="display:inline-block;width:12px;height:12px;background:#e9ecef;border-radius:3px;"></span> عدم حضور / تعطیل</span>
        </div>

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="calendar-wrapper">
                    <div class="calendar-header">
                        <button type="button" class="btn btn-sm btn-light" id="calPrev">‹</button>
                        <span class="month-title" id="calMonthTitle">...</span>
                        <button type="button" class="btn btn-sm btn-light" id="calNext">›</button>
                    </div>
                    <div class="calendar-grid" id="calGrid"></div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="border rounded p-3 mb-3">
                    <small class="text-muted">تاریخ انتخاب‌شده:</small>
                    <div class="fw-bold" id="selectedDateDisplay" style="color:<?= BRAND_COLOR ?>;font-size:1.1rem;">—</div>
                </div>

                <div id="slotsBox" style="display:none;">
                    <label class="form-label">۲. انتخاب ساعت</label>
                    <div class="slot-grid" id="slotGrid"></div>
                </div>
            </div>
        </div>

        <div id="previewBox" style="display:none;" class="alert alert-success mt-4">
            <b>تاریخ جدید انتخابی:</b> <span id="previewText"></span>
        </div>

        <div class="mt-4 d-flex gap-2">
            <button class="btn btn-brand" id="submitBtn" disabled>ثبت موکول</button>
            <a href="<?= BASE_URL ?>/appointments/show/<?= $appt['id'] ?>" class="btn btn-light">انصراف</a>
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
    font-size: 1.05rem;
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
.calendar-grid .day.green { background: #28a745; color: #fff; border-color: #28a745; }
.calendar-grid .day.green:hover { transform: scale(1.08); box-shadow: 0 3px 10px rgba(40,167,69,0.4); }
.calendar-grid .day.red { background: #dc3545; color: #fff; border-color: #dc3545; cursor: not-allowed; opacity: 0.7; }
.calendar-grid .day.gray { background: #f0f0f0; color: #aaa; cursor: not-allowed; }
.calendar-grid .day.selected {
    box-shadow: 0 0 0 3px #D1B1B0, 0 4px 12px rgba(209,177,176,0.5) !important;
    transform: scale(1.1);
    font-weight: bold;
}
.calendar-grid .empty { aspect-ratio: 1; }

.slot-grid { display: flex; flex-wrap: wrap; gap: 6px; }
.slot-btn {
    padding: 6px 12px; border-radius: 6px; border: 1px solid #ddd;
    background: #fff; cursor: pointer; font-size: 0.85rem;
    transition: all 0.15s;
}
.slot-btn.available { background: #28a745; color: #fff; border-color: #28a745; }
.slot-btn.available:hover { transform: scale(1.05); }
.slot-btn.taken { background: #dc3545; color: #fff; border-color: #dc3545; cursor: not-allowed; opacity: 0.7; }
.slot-btn.selected { box-shadow: 0 0 0 3px <?= BRAND_COLOR ?>; font-weight: bold; }
</style>

<script>
(function() {
    var calYear = <?= (int)$current_jy ?>;
    var calMonth = <?= (int)$current_jm ?>;
    var allDates = {};
    var monthNames = ['', 'فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
    var selectedDate = null;
    var selectedTime = null;

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

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
        document.getElementById('calMonthTitle').textContent = monthNames[calMonth] + ' ' + calYear;

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

                var status = allDates[gregStr] || 'gray';
                el.classList.add(status);

                if (selectedDate === gregStr) el.classList.add('selected');

                if (status === 'green') {
                    el.addEventListener('click', function() {
                        document.querySelectorAll('.calendar-grid .day').forEach(function(x) {
                            x.classList.remove('selected');
                        });
                        el.classList.add('selected');
                        selectDate(gregStr, jalaliStr);
                    });
                }

                grid.appendChild(el);
            })(d);
        }
    }

    function selectDate(gregStr, jalaliStr) {
        selectedDate = gregStr;
        selectedTime = null;
        document.getElementById('newDate').value = gregStr;
        document.getElementById('newStart').value = '';
        document.getElementById('selectedDateDisplay').textContent = jalaliStr;
        document.getElementById('previewBox').style.display = 'none';
        document.getElementById('submitBtn').disabled = true;
        loadSlots(gregStr, jalaliStr);
    }

    function loadSlots(date, jalaliStr) {
        var box = document.getElementById('slotGrid');
        document.getElementById('slotsBox').style.display = 'block';
        box.innerHTML = '<span class="text-muted small">در حال بارگذاری...</span>';

        fetch('<?= BASE_URL ?>/appointments/reschedule-slots/<?= $appt['id'] ?>?date=' + date)
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
                res.slots.forEach(function(sl) {
                    var b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'slot-btn ' + (sl.available ? 'available' : 'taken');
                    b.textContent = sl.start + ' - ' + sl.end;
                    b.disabled = !sl.available;
                    if (sl.available) {
                        b.onclick = function() {
                            document.querySelectorAll('#slotGrid .slot-btn').forEach(function(x) { x.classList.remove('selected'); });
                            b.classList.add('selected');
                            selectedTime = sl.start;
                            document.getElementById('newStart').value = sl.start;
                            document.getElementById('previewText').textContent = jalaliStr + ' ساعت ' + sl.start;
                            document.getElementById('previewBox').style.display = 'block';
                            document.getElementById('submitBtn').disabled = false;
                        };
                    }
                    box.appendChild(b);
                });
            });
    }

    // بارگذاری داده‌ها از سرور
    fetch('<?= BASE_URL ?>/appointments/reschedule-dates/<?= $appt['id'] ?>')
        .then(r => r.json())
        .then(res => {
            if (res.dates) {
                res.dates.forEach(function(d) {
                    allDates[d.date] = d.status;
                });
            }
            renderCal();
        })
        .catch(function() { renderCal(); });

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
})();
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>