<?php ob_start(); ?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card p-4">
                    <h4 class="text-center mb-4">درخواست مشاوره</h4>

                    <?php if (!empty($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="<?= BASE_URL ?>/site/consultation/submit">
                        <?= Helpers::csrfField() ?>
                        <div class="mb-3">
                            <label class="form-label">نام و نام خانوادگی *</label>
                            <input type="text" name="full_name" class="form-control" required pattern="[\u0600-\u06FF\s]{2,100}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">شماره موبایل *</label>
                            <input type="text" name="mobile" class="form-control" required pattern="09[0-9]{9}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">متن پیام *</label>
                            <textarea name="message" class="form-control" rows="4" required></textarea>
                        </div>
                        <button class="btn btn-brand w-100">ارسال درخواست</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/public.php'; ?>