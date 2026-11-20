<?php ob_start(); ?>
<div class="card p-4">
    <h5 class="mb-4">کاربر جدید</h5>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/users/create/submit" enctype="multipart/form-data">
        <?= Helpers::csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">نام و نام خانوادگی *</label>
                <input type="text" name="full_name" class="form-control" required pattern="[\u0600-\u06FF\s]{2,100}" title="فقط حروف فارسی">
            </div>
            <div class="col-md-6">
                <label class="form-label">شماره موبایل *</label>
                <input type="text" name="mobile" class="form-control" required pattern="09[0-9]{9}" title="۱۱ رقم، شروع با ۰۹">
                <small class="text-muted">نام کاربری و رمز اولیه همین شماره خواهد بود.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">نقش *</label>
                <select name="role" class="form-select" required onchange="toggleDoctor(this)">
                    <option value="">انتخاب کنید</option>
                    <?php foreach ($roles as $k => $v): 
                        if ($k === 'super_admin' && Auth::role() !== 'super_admin') continue;
                    ?>
                        <option value="<?= $k ?>"><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">جنسیت *</label>
                <select name="gender" class="form-select" required>
                    <option value="">انتخاب کنید</option>
                    <option value="male">آقا</option>
                    <option value="female">خانم</option>
                </select>
            </div>
            <div class="col-md-6" id="doctorBox" style="display:none;">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="is_doctor" id="is_doctor">
                    <label class="form-check-label" for="is_doctor">پزشک (دکتر)</label>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">تصویر پرسنلی</label>
                <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png">
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-brand">ذخیره</button>
            <a href="<?= BASE_URL ?>/users" class="btn btn-light">انصراف</a>
        </div>
    </form>
</div>

<script>
    function toggleDoctor(sel) {
        document.getElementById('doctorBox').style.display = sel.value === 'operator' ? 'block' : 'none';
    }
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>