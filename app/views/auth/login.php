<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ورود | نایریکا</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css">
    <style>
        body { background: linear-gradient(135deg, #f5e6e6, <?= BRAND_COLOR ?>); min-height: 100vh; display: flex; align-items: center; }
        .login-card { border: none; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        .btn-brand { background: <?= BRAND_COLOR ?>; border-color: <?= BRAND_COLOR ?>; color: #fff; }
        .btn-brand:hover { background: #b89393; border-color: #b89393; color: #fff; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card login-card p-4">
                <?php 
                require_once __DIR__ . '/../../../app/models/Setting.php';
                $__logo = (new Setting())->get('clinic_logo', '');
                $__name = (new Setting())->get('clinic_name', 'نایریکا');
                ?>
                <?php if ($__logo): ?>
                    <div class="text-center mb-3">
                        <img src="<?= BASE_URL ?>/public/<?= Helpers::e($__logo) ?>" style="max-height:90px;max-width:100%;">
                    </div>
                <?php endif; ?>
                <h3 class="text-center mb-4" style="color:<?= BRAND_COLOR ?>;">
                    <?= Helpers::e($__name) ?>
                </h3>
                <h3 class="text-center mb-4" style="color:<?= BRAND_COLOR ?>;">ورود به سیستم</h3>
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                <form method="POST" action="<?= BASE_URL ?>/login/submit">
                    <?= Helpers::csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">نام کاربری</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رمز عبور</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-brand w-100">ورود</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>