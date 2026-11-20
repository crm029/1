<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">قفل دوره مالی</h5>
        <a href="<?= BASE_URL ?>/accounting" class="btn btn-light">بازگشت</a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="alert alert-info small">
        <i class="bi bi-info-circle"></i>
        با قفل کردن یک ماه مالی، دیگه نمی‌تونی تراکنش‌ها، صندوق و دستمزدهای اون ماه رو ویرایش کنی.
        فقط سوپر ادمین می‌تونه قفل رو برداره.
    </div>

    <?php if (in_array(Auth::role(), ['super_admin', 'admin'])): ?>
        <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">قفل کردن ماه جدید</h6>
        <form method="POST" action="<?= BASE_URL ?>/accounting/locks/lock" class="row g-3 mb-4" onsubmit="return confirm('آیا از قفل کردن این دوره مطمئن هستید؟')">
            <?= Helpers::csrfField() ?>
            <div class="col-md-3">
                <label class="form-label">سال (شمسی)</label>
                <select name="year" class="form-select" required>
                    <?php for ($y = 1404; $y <= 1410; $y++): ?>
                        <option value="<?= $y ?>" <?= $y == 1405 ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">ماه</label>
                <select name="month" class="form-select" required>
                    <?php $months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
                    foreach ($months as $i => $m): ?>
                        <option value="<?= $i + 1 ?>"><?= $m ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">توضیحات (اختیاری)</label>
                <input type="text" name="notes" class="form-control">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-brand w-100"><i class="bi bi-lock"></i> قفل</button>
            </div>
        </form>
    <?php endif; ?>

    <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">دوره‌های قفل‌شده</h6>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>سال</th>
                    <th>ماه</th>
                    <th>قفل‌کننده</th>
                    <th>تاریخ قفل</th>
                    <th>توضیحات</th>
                    <?php if (Auth::role() === 'super_admin'): ?>
                        <th>عملیات</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($locks)): ?>
                    <tr><td colspan="<?= Auth::role() === 'super_admin' ? 6 : 5 ?>" class="text-center text-muted py-4">هیچ دوره‌ای قفل نشده</td></tr>
                <?php else: 
                    $months = ['', 'فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
                    foreach ($locks as $l): ?>
                    <tr>
                        <td><?= $l['lock_year'] ?></td>
                        <td><?= $months[$l['lock_month']] ?></td>
                        <td><?= Helpers::e($l['locked_by_name'] ?? '—') ?></td>
                        <td><?= Helpers::toJalaliString($l['locked_at']) ?></td>
                        <td><small class="text-muted"><?= Helpers::e($l['notes'] ?? '—') ?></small></td>
                        <?php if (Auth::role() === 'super_admin'): ?>
                            <td>
                                <a href="<?= BASE_URL ?>/accounting/locks/unlock/<?= $l['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('قفل این دوره برداشته شود؟')"><i class="bi bi-unlock"></i></a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>