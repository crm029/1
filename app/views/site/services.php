<?php ob_start(); ?>
<section class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">خدمات کلینیک</h2>

        <?php if (!empty($categories)): ?>
            <div class="category-tabs mb-5">
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

        <div class="row g-4" id="servicesGrid">
            <?php if (empty($services)): ?>
                <div class="col-12 text-center text-muted">خدمتی برای نمایش وجود ندارد.</div>
            <?php else: foreach ($services as $s): ?>
                <div class="col-md-4 service-item" data-category="<?= (int)$s['category_id'] ?>">
                    <div class="card card-service p-4 h-100">
                        <div style="width:50px;height:50px;border-radius:50%;background:<?= Helpers::e($s['color']) ?>;margin-bottom:15px;"></div>
                        <h5><?= Helpers::e($s['name']) ?></h5>
                        <p class="text-muted small flex-grow-1"><?= nl2br(Helpers::e($s['description'] ?? '')) ?></p>
                        <p class="small text-muted mb-2"><i class="bi bi-clock"></i> مدت: <?= $s['duration_minutes'] ?> دقیقه</p>
                        <?php if ($s['show_price_public']): ?>
                            <p class="fw-bold mb-0" style="color:<?= BRAND_COLOR ?>;"><?= number_format($s['price']) ?> تومان</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <div class="text-center mt-5">
            <a href="<?= BASE_URL ?>/booking" class="btn btn-brand btn-lg px-5">رزرو نوبت</a>
        </div>
    </div>
</section>

<style>
    .category-tabs {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f0e8e8;
    }
    .cat-tab {
        background: #fff;
        border: 1.5px solid #e8dcdc;
        color: #666;
        padding: 10px 22px;
        border-radius: 30px;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .cat-tab:hover {
        border-color: var(--cat-color, <?= BRAND_COLOR ?>);
        color: var(--cat-color, <?= BRAND_COLOR ?>);
        transform: translateY(-2px);
    }
    .cat-tab.active {
        background: var(--cat-color, <?= BRAND_COLOR ?>);
        border-color: var(--cat-color, <?= BRAND_COLOR ?>);
        color: #fff;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
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