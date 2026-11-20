<?php 
ob_start(); 
$commissionMap = [];
foreach ($commissions as $c) {
    $commissionMap[$c['operator_id']] = $c;
}
?>
<div class="card p-4">
    <h5 class="mb-4">ویرایش خدمت</h5>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/services/edit/<?= $service['id'] ?>/submit">
        <?= Helpers::csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">دسته‌بندی *</label>
                <select name="category_id" class="form-select" required>
                    <option value="">-- انتخاب دسته --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($service['category_id'] == $c['id']) ? 'selected' : '' ?>>
                            <?= Helpers::e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">نام خدمت *</label>
                <input type="text" name="name" class="form-control" required value="<?= Helpers::e($service['name']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">مدت زمان (دقیقه) *</label>
                <input type="number" name="duration_minutes" class="form-control" min="1" required value="<?= $service['duration_minutes'] ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">رنگ خدمت</label>
                <input type="color" name="color" class="form-control form-control-color" value="<?= Helpers::e($service['color']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">قیمت (تومان) *</label>
                <input type="number" name="price" class="form-control" min="0" required value="<?= $service['price'] ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">جنسیت مجاز *</label>
                <select name="gender_restriction" class="form-select" required>
                    <option value="none" <?= $service['gender_restriction'] === 'none' ? 'selected' : '' ?>>آزاد (آقا و خانم)</option>
                    <option value="male" <?= $service['gender_restriction'] === 'male' ? 'selected' : '' ?>>فقط آقا</option>
                    <option value="female" <?= $service['gender_restriction'] === 'female' ? 'selected' : '' ?>>فقط خانم</option>
                </select>
            </div>
            <div class="col-md-6">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="show_price_public" id="show_price_public" <?= $service['show_price_public'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="show_price_public">نمایش قیمت در سایت عمومی</label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check mt-4">
                    <input class="form-check-input" type="checkbox" name="is_doctor_service" id="is_doctor_service" <?= $service['is_doctor_service'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_doctor_service">خدمات دکتر (نه اپراتور)</label>
                </div>
            </div>
			<div class="col-md-6">
				<div class="form-check mt-4">
					<input class="form-check-input" type="checkbox" name="require_same_gender_operator" id="require_same_gender_operator" <?= $service['require_same_gender_operator'] ? 'checked' : '' ?>>
					<label class="form-check-label" for="require_same_gender_operator">اجبار هم‌جنس بودن اپراتور با کلاینت</label>
				</div>
				<small class="text-muted">اگر تیک نخورد، اپراتور آقا می‌تواند برای کلاینت خانم هم خدمت بدهد.</small>
			</div>
            <div class="col-md-12">
                <label class="form-label">توضیحات</label>
                <textarea name="description" class="form-control" rows="3"><?= Helpers::e($service['description']) ?></textarea>
            </div>
        </div>

        <hr class="my-4">
        <h6 class="mb-3">دستمزد اپراتورها برای این خدمت (فقط یکی از دو ستون را پر کنید)</h6>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>اپراتور / دکتر</th>
                        <th>درصد (%)</th>
                        <th>مبلغ ثابت (تومان)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($operators)): ?>
                        <tr><td colspan="3" class="text-center text-muted">اپراتوری ثبت نشده است</td></tr>
                    <?php else: foreach ($operators as $op): 
                        $c = $commissionMap[$op['id']] ?? null;
                    ?>
                        <tr>
                            <td><?= Helpers::e($op['full_name']) ?> <?= $op['is_doctor'] ? '<span class="badge bg-info">دکتر</span>' : '' ?></td>
                            <td><input type="number" name="commission_percent_<?= $op['id'] ?>" class="form-control" min="0" max="100" step="0.01" value="<?= $c['commission_percent'] ?? '' ?>"></td>
                            <td><input type="number" name="commission_fixed_<?= $op['id'] ?>" class="form-control" min="0" value="<?= $c['fixed_amount'] ?? '' ?>"></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            <button class="btn btn-brand">ذخیره تغییرات</button>
            <a href="<?= BASE_URL ?>/services" class="btn btn-light">انصراف</a>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>