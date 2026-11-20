<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">مدیریت متن پیام‌ها</h5>
        <a href="<?= BASE_URL ?>/notification-templates/create" class="btn btn-brand">
            <i class="bi bi-plus-lg"></i> قالب جدید
        </a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="alert alert-info small">
        <i class="bi bi-info-circle"></i>
        این متن‌ها در ارسال نوتیفیکیشن از طریق ربات بله استفاده می‌شوند. تغییرات بلافاصله اعمال می‌شود.
    </div>

    <div class="row g-3">
        <?php if (empty($templates)): ?>
            <div class="col-12 text-center text-muted py-4">هیچ قالبی یافت نشد</div>
        <?php else: foreach ($templates as $t): ?>
            <div class="col-md-6">
                <div class="border rounded p-3 h-100">
                    <div class="mb-2">
                        <h6 class="mb-1">
                            <?= Helpers::e($t['title']) ?>
                            <?php if ($t['is_active']): ?>
                                <span class="badge bg-success">فعال</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">غیرفعال</span>
                            <?php endif; ?>
                        </h6>
                        <code class="small"><?= Helpers::e($t['code']) ?></code>
                    </div>

                    <?php if (!empty($t['description'])): ?>
                        <p class="small text-muted mb-2"><?= Helpers::e($t['description']) ?></p>
                    <?php endif; ?>

                    <div class="bg-light rounded p-2 mb-2" style="max-height:120px;overflow:auto;">
                        <pre class="small mb-0" style="white-space:pre-wrap;font-family:Tahoma;"><?= Helpers::e($t['text']) ?></pre>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted"><b>Placeholderها:</b></small><br>
                        <?php foreach (($placeholders[$t['code']] ?? []) as $ph): ?>
                            <code class="small me-1">{<?= $ph ?>}</code>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="<?= BASE_URL ?>/notification-templates/edit/<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> ویرایش
                        </a>
                        <form method="POST" action="<?= BASE_URL ?>/notification-templates/delete/<?= $t['id'] ?>" style="display:inline;" onsubmit="return confirm('این قالب حذف شود؟');">
                            <?= Helpers::csrfField() ?>
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> حذف</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>