<?php ob_start(); ?>
<div class="card p-4">
    <h5 class="mb-4">ثبت حقوق جدید</h5>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/accounting/salaries/create/submit">
        <?= Helpers::csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">کارمند *</label>
                <select name="user_id" class="form-select" required>
                    <option value="">انتخاب کنید</option>
                    <?php foreach ($staff as $s): 
                        $roles = ['secretary' => 'منشی', 'accountant' => 'حسابدار', 'operator' => 'اپراتور'];
                    ?>
                        <option value="<?= $s['id'] ?>">
                            <?= Helpers::e($s['full_name']) ?> (<?= $roles[$s['role']] ?? $s['role'] ?><?= $s['is_doctor'] ? ' - دکتر' : '' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">مبلغ (تومان) *</label>
                <input type="number" name="amount" class="form-control" min="1" required>
            </div>
                        <div class="col-md-6">
                <label class="form-label">از تاریخ (شمسی) *</label>
                <div class="d-flex gap-1">
                    <select name="ps_year" class="form-select" required>
                        <?php for ($y = 1400; $y <= 1410; $y++): ?>
                            <option value="<?= $y ?>" <?= $y == 1405 ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="ps_month" class="form-select" required>
                        <?php $months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند']; 
                        foreach ($months as $i => $m): ?>
                            <option value="<?= $i + 1 ?>"><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="ps_day" class="form-select" required>
                        <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">تا تاریخ (شمسی) *</label>
                <div class="d-flex gap-1">
                    <select name="pe_year" class="form-select" required>
                        <?php for ($y = 1400; $y <= 1410; $y++): ?>
                            <option value="<?= $y ?>" <?= $y == 1405 ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="pe_month" class="form-select" required>
                        <?php foreach ($months as $i => $m): ?>
                            <option value="<?= $i + 1 ?>"><?= $m ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="pe_day" class="form-select" required>
                        <?php for ($d = 1; $d <= 31; $d++): ?>
                            <option value="<?= $d ?>"><?= $d ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="col-md-12">
                <label class="form-label">توضیحات</label>
                <textarea name="description" class="form-control" rows="2" placeholder="مثلاً حقوق مهر ماه"></textarea>
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-brand">ذخیره</button>
            <a href="<?= BASE_URL ?>/accounting/salaries" class="btn btn-light">انصراف</a>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>