<?php ob_start(); ?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card p-4">
                    <h4 class="mb-2">سلام <?= Helpers::e($clientName) ?> عزیز</h4>
                    <p class="text-muted small mb-4">گام ۲ از ۴ — انتخاب خدمات (می‌توانید چند خدمت انتخاب کنید)</p>

                    <?php if (!empty($_SESSION['booking_error'])): ?>
                        <div class="alert alert-danger"><?= Helpers::e($_SESSION['booking_error']); unset($_SESSION['booking_error']); ?></div>
                    <?php endif; ?>

                    <!-- تب‌های دسته‌بندی -->
                    <?php if (!empty($categories)): ?>
                        <div class="category-tabs mb-4">
                            <button type="button" class="cat-tab active" data-cat="all">
                                <i class="bi bi-grid"></i> همه
                            </button>
                            <?php foreach ($categories as $c): ?>
                                <button type="button" class="cat-tab" data-cat="<?= $c['id'] ?>"
                                        style="--cat-color: <?= Helpers::e($c['color']) ?>;">
                                    <i class="<?= Helpers::e($c['icon']) ?>"></i>
                                    <?= Helpers::e($c['name']) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= BASE_URL ?>/booking/services/submit">
                        <?= Helpers::csrfField() ?>
                        <div class="row g-3" id="servicesGrid">
                            <?php if (empty($services)): ?>
                                <div class="col-12 text-center text-muted py-5">خدمتی برای نمایش وجود ندارد.</div>
                            <?php else: foreach ($services as $s): ?>
                                <div class="col-md-6 service-item" data-category="<?= (int)$s['category_id'] ?>">
                                    <label class="service-card d-block h-100">
                                        <input type="checkbox" name="service_ids[]" value="<?= $s['id'] ?>" class="service-checkbox">
                                        <div class="card p-3 h-100" style="cursor:pointer;">
                                            <div class="d-flex align-items-center">
                                                <div style="width:35px;height:35px;border-radius:50%;background:<?= Helpers::e($s['color']) ?>;margin-left:12px;"></div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0"><?= Helpers::e($s['name']) ?></h6>
                                                    <small class="text-muted"><?= $s['duration_minutes'] ?> دقیقه</small>
                                                </div>
                                                <i class="bi bi-check-circle-fill check-icon"></i>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="<?= BASE_URL ?>/booking" class="btn btn-light">بازگشت</a>
                            <button class="btn btn-brand">ادامه</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .service-card { position: relative; display: block; }
    .service-checkbox { position: absolute; opacity: 0; pointer-events: none; }
    .service-checkbox:checked + .card {
        border: 2px solid <?= BRAND_COLOR ?>;
        background: #fdf6f6;
    }
    .service-checkbox:checked + .card .check-icon {
        color: <?= BRAND_COLOR ?>;
        opacity: 1;
    }
    .check-icon {
        opacity: 0;
        font-size: 1.3rem;
        transition: all 0.2s;
    }
    .category-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding-bottom: 12px;
        border-bottom: 2px solid #f0e8e8;
    }
    .cat-tab {
        background: #fff;
        border: 1.5px solid #e8dcdc;
        color: #666;
        padding: 8px 18px;
        border-radius: 25px;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .cat-tab:hover {
        border-color: var(--cat-color, <?= BRAND_COLOR ?>);
        color: var(--cat-color, <?= BRAND_COLOR ?>);
    }
    .cat-tab.active {
        background: var(--cat-color, <?= BRAND_COLOR ?>);
        border-color: var(--cat-color, <?= BRAND_COLOR ?>);
        color: #fff;
        font-weight: 600;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }
    .service-item.hidden {
        display: none;
    }
</style>

<script>
    document.querySelectorAll('.cat-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            const cat = tab.dataset.cat;
            document.querySelectorAll('.service-item').forEach(item => {
                if (cat === 'all' || item.dataset.category === cat) {
                    item.classList.remove('hidden');
                } else {
                    item.classList.add('hidden');
                }
            });
        });
    });
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/public.php'; ?>