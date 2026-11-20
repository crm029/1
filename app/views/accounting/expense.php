<?php ob_start(); ?>
<div class="card p-4">
    <h5 class="mb-4">ثبت هزینه</h5>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/accounting/expense/submit">
        <?= Helpers::csrfField() ?>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">مبلغ (تومان) *</label>
                <input type="number" name="amount" class="form-control" min="1" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">روش پرداخت *</label>
                <select name="payment_method" class="form-select" required>
                    <option value="cash">نقدی</option>
                    <option value="pos">کارتخوان</option>
                    <option value="card_to_card">کارت‌به‌کارت</option>
                </select>
            </div>
            <div class="col-md-12">
                <label class="form-label">توضیحات *</label>
                <textarea name="description" class="form-control" rows="2" required placeholder="مثلاً: خرید مواد مصرفی لیزر"></textarea>
            </div>
        </div>
        <div class="mt-4">
            <button class="btn btn-brand">ثبت هزینه</button>
            <a href="<?= BASE_URL ?>/accounting" class="btn btn-light">انصراف</a>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>