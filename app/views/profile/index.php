<?php ob_start(); 

$roles = [
    'super_admin' => 'سوپر ادمین',
    'admin' => 'مدیر کل',
    'secretary' => 'منشی',
    'operator' => 'اپراتور',
    'accountant' => 'حسابدار',
    'content_manager' => 'مدیر محتوا',
];
?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- ستون راست: عکس + اطلاعات شخصی -->
    <div class="col-md-4">
        <div class="card p-4 text-center">
            <div class="profile-photo-wrapper mb-3">
                <?php if (!empty($user['photo'])): ?>
                    <img src="<?= BASE_URL ?>/public/<?= Helpers::e($user['photo']) ?>?v=<?= time() ?>" alt="عکس پروفایل" class="profile-photo">
                <?php elseif (!empty($clinic_logo)): ?>
                    <img src="<?= BASE_URL ?>/public/<?= Helpers::e($clinic_logo) ?>" alt="لوگو" class="profile-photo" style="object-fit:contain;background:#fff;padding:15px;">
                <?php else: ?>
                    <div class="profile-photo-placeholder">
                        <i class="bi bi-person-circle"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <h5 class="mb-1"><?= Helpers::e($user['full_name']) ?></h5>
            <p class="text-muted small mb-2"><?= Helpers::e($user['username']) ?></p>
            <span class="badge" style="background:<?= BRAND_COLOR ?>;">
                <?= $roles[$user['role']] ?? $user['role'] ?>
            </span>
            
            <?php if ($user['is_doctor']): ?>
                <span class="badge bg-info mt-1">دکتر</span>
            <?php endif; ?>
            
            <hr class="my-3">
            
            <form method="POST" action="<?= BASE_URL ?>/profile/upload-photo" enctype="multipart/form-data" id="photoForm">
                <?= Helpers::csrfField() ?>
                <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png" style="display:none;">
                <button type="button" class="btn btn-sm btn-brand w-100 mb-2" onclick="document.getElementById('photoInput').click()">
                    <i class="bi bi-camera"></i> 
                    <?= !empty($user['photo']) ? 'تغییر عکس' : 'آپلود عکس' ?>
                </button>
            </form>
            
            <?php if (!empty($user['photo'])): ?>
                <form method="POST" action="<?= BASE_URL ?>/profile/delete-photo" onsubmit="return confirm('عکس پروفایل حذف شود؟')">
                    <?= Helpers::csrfField() ?>
                    <button class="btn btn-sm btn-outline-danger w-100">
                        <i class="bi bi-trash"></i> حذف عکس
                    </button>
                </form>
            <?php endif; ?>
            
            <hr class="my-3">
            
            <div class="text-start small">
                <p class="mb-2"><i class="bi bi-person"></i> <b>جنسیت:</b> <?= $user['gender'] === 'male' ? 'آقا' : 'خانم' ?></p>
                <p class="mb-0"><i class="bi bi-calendar"></i> <b>عضویت:</b> <?= Helpers::toJalaliString($user['created_at']) ?></p>
            </div>
        </div>
    </div>
    
    <!-- ستون چپ -->
    <div class="col-md-8">
        <!-- اطلاعات ورود -->
        <div class="card p-4 mb-4">
            <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">
                <i class="bi bi-shield-check"></i> اطلاعات ورود
            </h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border rounded p-3">
                        <small class="text-muted d-block">نام کاربری</small>
                        <b><?= Helpers::e($user['username']) ?></b>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3">
                        <small class="text-muted d-block">آخرین ورود</small>
                        <b><?= $user['last_login'] ? Helpers::toJalaliString($user['last_login']) . ' — ' . date('H:i', strtotime($user['last_login'])) : '—' ?></b>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- تغییر رمز -->
        <div class="card p-4 mb-4">
            <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">
                <i class="bi bi-key"></i> تغییر رمز عبور
            </h6>
            
            <div class="alert alert-info small">
                <b>قوانین رمز جدید:</b>
                <ul class="mb-0 mt-1">
                    <li>حداقل ۸ کاراکتر</li>
                    <li>شامل حرف بزرگ انگلیسی (A-Z)</li>
                    <li>شامل حرف کوچک انگلیسی (a-z)</li>
                    <li>شامل عدد (0-9)</li>
                </ul>
            </div>
            
            <form method="POST" action="<?= BASE_URL ?>/profile/change-password">
                <?= Helpers::csrfField() ?>
                
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">رمز فعلی *</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">رمز جدید *</label>
                        <input type="password" name="new_password" class="form-control" required minlength="8">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">تکرار رمز جدید *</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?= Helpers::e($captcha) ?> *</label>
                        <input type="text" name="captcha" class="form-control" required>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button class="btn btn-brand">
                        <i class="bi bi-check-lg"></i> تغییر رمز
                    </button>
                    <small class="text-muted ms-2">بعد از تغییر، باید با رمز جدید وارد شوید.</small>
                </div>
            </form>
        </div>
        
        <!-- لاگ‌ها -->
        <div class="card p-4">
            <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">
                <i class="bi bi-clock-history"></i> فعالیت‌های اخیر من
            </h6>
            
            <?php if (empty($logs)): ?>
                <p class="text-muted small mb-0">فعالیتی ثبت نشده است.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>زمان</th>
                                <th>اقدام</th>
                                <th>توضیحات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><small><?= Helpers::toJalaliString($log['created_at']) ?> — <?= date('H:i', strtotime($log['created_at'])) ?></small></td>
                                    <td><span class="badge bg-light text-dark"><?= Helpers::e(Helpers::translateAction($log['action'])) ?></span></td>
                                    <td><small><?= Helpers::e($log['description']) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.profile-photo-wrapper { display: flex; justify-content: center; margin-bottom: 15px; }
.profile-photo {
    width: 150px; height: 150px;
    border-radius: 50%; object-fit: cover;
    border: 4px solid <?= BRAND_COLOR ?>;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
.profile-photo-placeholder {
    width: 150px; height: 150px;
    border-radius: 50%;
    background: #f0e8e8;
    display: flex; align-items: center; justify-content: center;
    border: 4px solid <?= BRAND_COLOR ?>;
    color: <?= BRAND_COLOR ?>;
    font-size: 5rem;
}
</style>

<script>
document.getElementById('photoInput').addEventListener('change', function() {
    if (this.files.length > 0) {
        document.getElementById('photoForm').submit();
    }
});
</script>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>