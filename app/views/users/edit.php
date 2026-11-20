<?php ob_start(); ?>
<div class="card p-4">
    <h5 class="mb-4">ویرایش کاربر</h5>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/users/edit/<?= $user['id'] ?>/submit" enctype="multipart/form-data">
        <?= Helpers::csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">نام و نام خانوادگی *</label>
                <input type="text" name="full_name" class="form-control" required value="<?= Helpers::e($user['full_name']) ?>" pattern="[\u0600-\u06FF\s]{2,100}">
            </div>
            <div class="col-md-6">
                <label class="form-label">نام کاربری</label>
                <input type="text" name="username" class="form-control" value="<?= Helpers::e($user['username']) ?>" pattern="[a-zA-Z0-9_]{3,50}">
                <small class="text-muted">فقط حروف انگلیسی، عدد و _</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">شماره موبایل *</label>
                <input type="text" name="mobile" class="form-control" required value="<?= Helpers::e($user['mobile']) ?>" pattern="09[0-9]{9}">
            </div>
            <div class="col-md-6">
                <label class="form-label">نقش *</label>
                <select name="role" class="form-select" required onchange="toggleDoctor(this)">
                    <?php foreach ($roles as $k => $v): 
                        if ($k === 'super_admin' && Auth::role() !== 'super_admin') continue;
                    ?>
                        <option value="<?= $k ?>" <?= $user['role'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">جنسیت *</label>
                <select name="gender" class="form-select" required>
                    <option value="male" <?= $user['gender'] === 'male' ? 'selected' : '' ?>>آقا</option>
                    <option value="female" <?= $user['gender'] === 'female' ? 'selected' : '' ?>>خانم</option>
                </select>
            </div>
            <div class="col-md-6" id="doctorBox" style="display:<?= $user['role'] === 'operator' ? 'block' : 'none' ?>;">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="is_doctor" id="is_doctor" <?= $user['is_doctor'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_doctor">پزشک (دکتر)</label>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">رمز عبور جدید (اختیاری)</label>
                <input type="password" name="new_password" class="form-control" minlength="8">
                <small class="text-muted">اگر پر شود، کاربر باید در ورود بعدی رمز را عوض کند.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label">تصویر جدید (اختیاری)</label>
                <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png">
                <?php if ($user['photo']): ?>
                    <img src="<?= BASE_URL ?>/public/<?= Helpers::e($user['photo']) ?>" style="width:60px;height:60px;border-radius:50%;margin-top:10px;object-fit:cover;">
                <?php endif; ?>
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-brand">ذخیره تغییرات</button>
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