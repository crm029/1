<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h5 class="mb-0">مدیریت بکاپ‌ها</h5>
        <div class="d-flex gap-2">
            <form method="POST" action="<?= BASE_URL ?>/backups/create" onsubmit="return confirm('بکاپ دیتابیس ایجاد شود؟')">
                <?= Helpers::csrfField() ?>
                <button class="btn btn-brand"><i class="bi bi-database"></i> بکاپ دیتابیس</button>
            </form>
            <form method="POST" action="<?= BASE_URL ?>/backups/create-full" onsubmit="return confirm('بکاپ کامل پروژه (شامل فایل‌ها) ایجاد شود؟ این عملیات ممکنه چند دقیقه طول بکشه.')">
                <?= Helpers::csrfField() ?>
                <button class="btn btn-outline-primary"><i class="bi bi-file-zip"></i> بکاپ کامل پروژه</button>
            </form>
        </div>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="alert alert-info small">
        <i class="bi bi-info-circle"></i>
        بکاپ‌های خودکار روزانه توسط Cron ایجاد می‌شوند و بکاپ‌های خودکار قدیمی‌تر از <b>۳۰ روز</b> به‌صورت خودکار حذف می‌شوند.
        <br>
        <small>آدرس Cron: <code><?= BASE_URL ?>/cron/backup?secret=<?= Helpers::e((require __DIR__ . '/../../../config/config.php')['app']['cron_secret']) ?></code></small>
    </div>

    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>نام فایل</th>
                    <th>حجم</th>
                    <th>نوع</th>
                    <th>وضعیت</th>
                    <th>تاریخ</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($backups)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">بکاپی وجود ندارد</td></tr>
                <?php else: foreach ($backups as $b): 
                    $sizeMB = $b['file_size'] > 0 ? number_format($b['file_size'] / 1024 / 1024, 2) . ' MB' : '—';
                ?>
                    <tr>
                        <td><?= $b['id'] ?></td>
                        <td><small style="font-family:monospace;"><?= Helpers::e($b['filename']) ?></small></td>
                        <td><?= $sizeMB ?></td>
                        <td>
                            <?php if ($b['type'] === 'auto'): ?>
                                <span class="badge bg-info">خودکار</span>
                            <?php else: ?>
                                <span class="badge bg-primary">دستی</span>
                            <?php endif; ?>
                            <?php if (!empty($b['backup_type'])): ?>
                                <?php if ($b['backup_type'] === 'full'): ?>
                                    <span class="badge bg-dark">کامل</span>
                                <?php elseif ($b['backup_type'] === 'files'): ?>
                                    <span class="badge bg-secondary">فایل‌ها</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">دیتابیس</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($b['status'] === 'success'): ?>
                                <span class="badge bg-success">موفق</span>
                            <?php else: ?>
                                <span class="badge bg-danger">ناموفق</span>
                            <?php endif; ?>
                        </td>
                        <td><small><?= Helpers::toJalaliString($b['created_at']) ?> — <?= date('H:i', strtotime($b['created_at'])) ?></small></td>
                        <td>
                            <a href="<?= BASE_URL ?>/backups/download/<?= $b['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i></a>
                            <a href="<?= BASE_URL ?>/backups/delete/<?= $b['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('این بکاپ حذف شود؟')"><i class="bi bi-trash"></i></a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>