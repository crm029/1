<?php 
ob_start(); 
[$current_jy, $current_jm, $current_jd] = Helpers::toJalaliArray(date('Y-m-d'));
?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">لیست نوبت‌ها</h5>
        <a href="<?= BASE_URL ?>/appointments/create" class="btn btn-brand"><i class="bi bi-plus-lg"></i> نوبت جدید</a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="row g-2 mb-4">
        <div class="col-md-3">
            <input type="text" id="liveSearch" class="form-control" placeholder="جستجو (نام، موبایل، پرونده، کد نوبت)..." value="<?= Helpers::e($filters['search']) ?>">
        </div>
        <div class="col-md-2">
            <select id="statusFilter" class="form-select">
                <option value="">همه وضعیت‌ها</option>
                <?php foreach ($statuses as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $filters['status'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select id="operatorFilter" class="form-select">
                <option value="">همه اپراتورها</option>
                <?php foreach ($operators as $op): ?>
                    <option value="<?= $op['id'] ?>" <?= $filters['operator_id'] == $op['id'] ? 'selected' : '' ?>><?= Helpers::e($op['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select id="serviceFilter" class="form-select">
                <option value="">همه خدمات</option>
                <?php foreach ($services as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $filters['service_id'] == $s['id'] ? 'selected' : '' ?>><?= Helpers::e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <div class="input-group">
                <input type="text" id="dateFromDisplay" class="form-control" placeholder="از تاریخ..." readonly style="cursor:pointer;" value="<?= !empty($filters['date_from']) ? Helpers::toJalaliString($filters['date_from']) : '' ?>">
                <button type="button" class="btn btn-outline-secondary" id="clearDateBtn" title="پاک کردن"><i class="bi bi-x"></i></button>
            </div>
            <input type="hidden" id="dateFrom" value="<?= Helpers::e($filters['date_from']) ?>">
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>کد نوبت</th>
                    <th>کلاینت</th>
                    <th>خدمت</th>
                    <th>اپراتور</th>
                    <th>تاریخ</th>
                    <th>ساعت</th>
                    <th>وضعیت</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody id="appointmentsTable">
                <?php if (empty($appointments)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">نوبتی یافت نشد</td></tr>
                <?php else: foreach ($appointments as $a): ?>
                    <tr>
                        <td><span class="badge bg-dark"><?= Helpers::e($a['booking_code']) ?></span></td>
                        <td><?= Helpers::e($a['client_name']) ?> <small class="text-muted">(<?= Helpers::e($a['file_number']) ?>)</small></td>
                        <td><?= Helpers::e($a['service_name']) ?></td>
                        <td><?= Helpers::e($a['operator_name'] ?? '—') ?></td>
                        <td><?= Helpers::toJalaliString($a['appointment_date']) ?></td>
                        <td><?= Helpers::e($a['start_time']) ?></td>
                        <td>
                            <?php 
                            $badgeColors = [
                                'pending_receipt' => 'warning',
                                'confirmed' => 'success',
                                'suspended' => 'secondary',
                                'cancelled' => 'danger',
                                'awaiting_service' => 'info',
                                'paid' => 'primary',
                                'done' => 'dark',
                                'no_show' => 'danger',
                                'cancel_requested' => 'warning',
                            ];
                            $bc = $badgeColors[$a['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $bc ?>"><?= $statuses[$a['status']] ?? $a['status'] ?></span>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/appointments/show/<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                        </td>
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
.calendar-header .month-title {
    font-weight:bold; font-size:1.05rem; color:#D1B1B0;
}
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
    const st = document.getElementById('statusFilter');
    const op = document.getElementById('operatorFilter');
    const sv = document.getElementById('serviceFilter');
    const df = document.getElementById('dateFrom');
    const dfDisplay = document.getElementById('dateFromDisplay');
    const table = document.getElementById('appointmentsTable');

    function liveFilter() {
        const params = new URLSearchParams({
            search: s.value,
            status: st.value,
            operator_id: op.value,
            service_id: sv.value,
            date_from: df.value,
        });
        fetch('<?= BASE_URL ?>/appointments?' + params.toString())
            .then(r => r.text())
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const nb = doc.getElementById('appointmentsTable');
                if (nb) table.innerHTML = nb.innerHTML;
            });
    }

    let timer;
    s.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(liveFilter, 300); });
    [st, op, sv].forEach(el => el.addEventListener('change', liveFilter));

    // ============ تقویم شمسی (مودال) ============
    let calYear = <?= (int)$current_jy ?>;
    let calMonth = <?= (int)$current_jm ?>;
    let calSelected = df.value;
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
                    df.value = gregStr;
                    dfDisplay.value = jalaliStr;

                    var modalEl = document.getElementById('calendarModal');
                    var modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();

                    liveFilter();
                });

                grid.appendChild(el);
            })(d);
        }
    }

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

    dfDisplay.addEventListener('click', function() {
        // اگه تاریخ انتخاب شده بود، به همون ماه برو
        if (calSelected) {
            var parts = dfDisplay.value.split('/');
            if (parts.length === 3) {
                calYear = parseInt(parts[0]);
                calMonth = parseInt(parts[1]);
            }
        }
        renderCal();
        new bootstrap.Modal(document.getElementById('calendarModal')).show();
    });

    document.getElementById('clearDateBtn').addEventListener('click', function() {
        df.value = '';
        dfDisplay.value = '';
        calSelected = null;
        liveFilter();
    });
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>