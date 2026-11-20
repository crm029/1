<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h5 class="mb-0">برنامه حضور</h5>
        <?php if (!$is_self && !empty($operators)): ?>
            <form method="GET" class="d-flex gap-2">
                <input type="hidden" name="jy" value="<?= $year ?>">
                <input type="hidden" name="jm" value="<?= $month ?>">
                <select name="operator_id" class="form-select" onchange="this.form.submit()">
                    <?php foreach ($operators as $op): ?>
                        <option value="<?= $op['id'] ?>" <?= $op['id'] == $operator_id ? 'selected' : '' ?>>
                            <?= Helpers::e($op['full_name']) ?> <?= $op['is_doctor'] ? '(دکتر)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
    </div>

    <?php if (!$operator_id): ?>
        <div class="alert alert-warning">هیچ اپراتوری ثبت نشده است.</div>
    <?php else: ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <?php 
            $prevM = $month - 1; $prevY = $year;
            if ($prevM < 1) { $prevM = 12; $prevY--; }
            $nextM = $month + 1; $nextY = $year;
            if ($nextM > 12) { $nextM = 1; $nextY++; }
            $baseParams = $_GET;
        ?>
        <a href="?<?= http_build_query(array_merge($baseParams, ['jy' => $prevY, 'jm' => $prevM])) ?>" class="btn btn-light"><i class="bi bi-chevron-right"></i></a>
        <h6 class="mb-0"><?= $monthName ?> <?= $year ?></h6>
        <a href="?<?= http_build_query(array_merge($baseParams, ['jy' => $nextY, 'jm' => $nextM])) ?>" class="btn btn-light"><i class="bi bi-chevron-left"></i></a>
    </div>

<div class="d-flex flex-wrap gap-3 mb-3 small">
    <span><span style="display:inline-block;width:15px;height:15px;background:#28a745;border-radius:3px;"></span> حاضر</span>
    <span><span style="display:inline-block;width:15px;height:15px;background:#dc3545;border-radius:3px;"></span> غایب</span>
    <span><span style="display:inline-block;width:15px;height:15px;background:#e9ecef;border-radius:3px;"></span> تعیین نشده</span>
    <span><span style="display:inline-block;width:15px;height:15px;background:#8e1a1a;border-radius:3px;"></span> 🔴 تعطیلی رسمی</span>
</div>

    <div class="calendar-grid">
        <?php 
        $weekDays = ['شنبه','یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه'];
        foreach ($weekDays as $wd): ?>
            <div class="text-center fw-bold py-2 bg-light"><?= $wd ?></div>
        <?php endforeach; ?>

        <?php for ($i = 0; $i < $startOffset; $i++): ?>
            <div></div>
        <?php endfor; ?>

<?php foreach ($days as $day): 
    $s = $day['schedule'];
    $status = $s['status'] ?? '';
    $isHoliday = !empty($day['is_holiday']);
    $holidayTitle = $day['holiday_title'] ?? '';

    if ($isHoliday) {
        $color = '#8e1a1a';
        $textColor = '#fff';
    } else {
        $color = '#e9ecef';
        $textColor = '#333';
        if ($status === 'present') { $color = '#28a745'; $textColor = '#fff'; }
        if ($status === 'absent') { $color = '#dc3545'; $textColor = '#fff'; }
    }
    $isToday = $day['gregorian'] === $today;
?>
    <div class="cal-day" 
         style="background:<?= $color ?>;color:<?= $textColor ?>;<?= $isToday ? 'border:3px solid ' . BRAND_COLOR . ';' : '' ?>"
         data-date="<?= $day['gregorian'] ?>"
         data-status="<?= $status ?>"
         title="<?= $isHoliday ? Helpers::e('تعطیل : ' . $holidayTitle) : '' ?>"
         onclick="<?php if ($isHoliday): ?>alert('🔴 تعطیل به علت <?= Helpers::e($holidayTitle) ?>')<?php else: ?>openDay('<?= $day['gregorian'] ?>', '<?= $day['jalali_day'] ?>', '<?= $status ?>', '<?= $s['start_time'] ?? '' ?>', '<?= $s['end_time'] ?? '' ?>', '<?= Helpers::e($s['note'] ?? '') ?>')<?php endif; ?>">
        <?= $day['jalali_day'] ?>
        <?php if ($isHoliday): ?><br><small style="font-size:0.7rem;">🔴</small><?php endif; ?>
    </div>
<?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>

<!-- Modal -->
<div class="modal fade" id="dayModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تنظیم روز <span id="modalDate"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="mDate">
                <div class="mb-3">
                    <label class="form-label">وضعیت</label>
                    <select id="mStatus" class="form-select">
                        <option value="present">حاضر</option>
                        <option value="absent">غایب</option>
                        <option value="clear">حذف (تعیین نشده)</option>
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">ساعت شروع</label>
                        <input type="time" id="mStart" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label">ساعت پایان</label>
                        <input type="time" id="mEnd" class="form-control">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="form-label">یادداشت</label>
                    <input type="text" id="mNote" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                <button type="button" class="btn btn-brand" onclick="saveDay()">ذخیره</button>
            </div>
        </div>
    </div>
</div>

<style>
    .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 6px;
    }
    .cal-day {
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        transition: transform 0.15s;
    }
    .cal-day:hover { transform: scale(1.05); }
</style>

<script>
const operatorId = <?= (int)$operator_id ?>;

function openDay(date, jDay, status, start, end, note) {
    document.getElementById('modalDate').textContent = jDay;
    document.getElementById('mDate').value = date;
    document.getElementById('mStatus').value = status || 'present';
    document.getElementById('mStart').value = start || '';
    document.getElementById('mEnd').value = end || '';
    document.getElementById('mNote').value = note || '';
    new bootstrap.Modal(document.getElementById('dayModal')).show();
}

function saveDay() {
    const fd = new FormData();
    fd.append('csrf_token', '<?= Helpers::csrfToken() ?>');
    fd.append('operator_id', operatorId);
    fd.append('date', document.getElementById('mDate').value);
    fd.append('status', document.getElementById('mStatus').value);
    fd.append('start_time', document.getElementById('mStart').value);
    fd.append('end_time', document.getElementById('mEnd').value);
    fd.append('note', document.getElementById('mNote').value);

    fetch('<?= BASE_URL ?>/schedules/save', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('dayModal')).hide();
                location.reload();
            } else {
                alert(res.error || 'خطا');
            }
        });
}
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>