<?php ob_start(); ?>
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">گالری نمونه کارها</h2>

        <?php if (empty($gallery)): ?>
            <p class="text-center text-muted">به‌زودی نمونه کارها در این بخش نمایش داده می‌شود.</p>
        <?php else: ?>
            <div class="row g-4 mb-5">
                <?php foreach ($gallery as $g): ?>
                    <div class="col-md-4">
                        <div class="card card-service h-100">
                            <div class="row g-0">
                                <div class="col-6 position-relative">
                                    <img src="<?= BASE_URL ?>/public/<?= Helpers::e($g['before_image']) ?>" style="width:100%;height:200px;object-fit:cover;border-top-right-radius:15px;">
                                    <span class="badge bg-dark position-absolute" style="top:8px;right:8px;">قبل</span>
                                </div>
                                <div class="col-6 position-relative">
                                    <img src="<?= BASE_URL ?>/public/<?= Helpers::e($g['after_image']) ?>" style="width:100%;height:200px;object-fit:cover;border-top-left-radius:15px;">
                                    <span class="badge position-absolute" style="top:8px;left:8px;background:<?= BRAND_COLOR ?>;">بعد</span>
                                </div>
                            </div>
                            <?php if (!empty($g['title']) || !empty($g['service_name'])): ?>
                                <div class="p-3 text-center">
                                    <h6 class="mb-1"><?= Helpers::e($g['title'] ?? '') ?></h6>
                                    <small class="text-muted"><?= Helpers::e($g['service_name'] ?? '') ?></small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($comments)): ?>
            <hr class="my-5">
            <h3 class="text-center mb-4">نظرات مراجعین</h3>
            <div class="row g-3">
                <?php foreach ($comments as $c): ?>
                    <div class="col-md-6">
                        <div class="card p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <b><?= Helpers::e($c['full_name']) ?></b>
                                <div>
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <i class="bi bi-star<?= $i < (int)$c['rating'] ? '-fill' : '' ?>" style="color:#ffc107;font-size:0.85rem;"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="mb-2 small"><?= nl2br(Helpers::e($c['message'])) ?></p>
                            <?php if (!empty($c['reply'])): ?>
                                <div class="bg-light p-2 rounded small">
                                    <b>پاسخ کلینیک:</b><br>
                                    <?= nl2br(Helpers::e($c['reply'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/public.php'; ?>