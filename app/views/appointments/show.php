<?php ob_start(); ?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">نوبت <span class="badge bg-dark"><?= Helpers::e($appt['booking_code']) ?></span></h5>
        <a href="<?= BASE_URL ?>/appointments" class="btn btn-light">بازگشت</a>
    </div>

    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= Helpers::e($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="border rounded p-3 h-100">
                <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">اطلاعات کلاینت</h6>
                <p><b>نام:</b> <?= Helpers::e($appt['client_name']) ?></p>
                <p><b>پرونده:</b> <?= Helpers::e($appt['file_number']) ?></p>
                <p><b>موبایل:</b> <?= Helpers::e($appt['client_mobile']) ?></p>
                <p><b>جنسیت:</b> <?= $appt['client_gender'] === 'male' ? 'آقا' : 'خانم' ?></p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="border rounded p-3 h-100">
                <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">اطلاعات نوبت</h6>
                <p><b>خدمت:</b> <?= Helpers::e($appt['service_name']) ?> (<?= $appt['duration_minutes'] ?> دقیقه)</p>
                <p><b>اپراتور:</b> <?= Helpers::e($appt['operator_name'] ?? '—') ?> <?= $appt['is_doctor'] ? '(دکتر)' : '' ?></p>
                <p><b>تاریخ:</b> <?= Helpers::toJalaliString($appt['appointment_date']) ?></p>
                <p><b>ساعت:</b> <?= Helpers::e($appt['start_time']) ?> - <?= Helpers::e($appt['end_time']) ?></p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="border rounded p-3 h-100">
                <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">اطلاعات مالی</h6>
                <p><b>قیمت خدمت:</b> <?= number_format($appt['service_price']) ?> تومان</p>
                <p><b>بیعانه:</b> <?= number_format($appt['deposit_amount']) ?> تومان</p>
                <p><b>باقی‌مانده برای تسویه:</b> <span class="text-danger fw-bold"><?= number_format($appt['service_price'] - $appt['deposit_amount']) ?> تومان</span></p>
                <?php if ($appt['paid_amount'] > 0): ?>
                    <p><b>پرداخت شده:</b> <?= number_format($appt['paid_amount']) ?> تومان</p>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-md-6">
            <div class="border rounded p-3 h-100">
                <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">وضعیت</h6>
                <?php 
                $statusColors = [
                    'pending_receipt' => 'warning',
                    'confirmed' => 'success',
                    'suspended' => 'secondary',
                    'cancelled' => 'danger',
                    'awaiting_service' => 'info',
                    'paid' => 'primary',
                    'done' => 'dark',
                    'no_show' => 'danger',
                ];
                ?>
                <p><b>وضعیت فعلی:</b> <span class="badge bg-<?= $statusColors[$appt['status']] ?? 'secondary' ?>"><?= $statuses[$appt['status']] ?? $appt['status'] ?></span></p>
                <?php if (!empty($appt['cancel_reason'])): ?>
                    <p><b>دلیل لغو/تعلیق:</b> <?= Helpers::e($appt['cancel_reason']) ?></p>
                <?php endif; ?>
                <?php if (!empty($appt['operator_note'])): ?>
                    <p><b>توضیحات اپراتور:</b></p>
                    <p class="text-muted"><?= nl2br(Helpers::e($appt['operator_note'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
	
	    <?php if ($appt['status'] === 'cancel_requested'): ?>
        <hr class="my-4">
        <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">درخواست لغو کلاینت</h6>
        <div class="alert alert-warning">
            <p><b>دلیل لغو:</b> <?= Helpers::e($appt['cancel_reason']) ?></p>
            <p><b>شماره کارت اعلامی:</b> <span style="font-family:monospace;letter-spacing:2px;"><?= Helpers::e($appt['cancel_card_number'] ?? '—') ?></span></p>
			<p><b>نام صاحب کارت:</b> <?= Helpers::e($appt['cancel_card_holder'] ?? '—') ?></p>
            <p class="mb-0"><b>زمان ثبت:</b> <?= Helpers::toJalaliString($appt['cancel_requested_at']) ?></p>
        </div>

        <?php if (in_array(Auth::role(), ['super_admin', 'admin', 'secretary'])): ?>
            <div class="d-flex flex-wrap gap-2 mb-4">
                <form method="POST" action="<?= BASE_URL ?>/appointments/approve-cancel/<?= $appt['id'] ?>">
                    <?= Helpers::csrfField() ?>
                    <button class="btn btn-danger"><i class="bi bi-x-circle"></i> تایید لغو</button>
                </form>
                <a href="<?= BASE_URL ?>/appointments/reschedule/<?= $appt['id'] ?>" class="btn btn-info">
                    <i class="bi bi-calendar-week"></i> موکول به تاریخ جدید
                </a>
                <form method="POST" action="<?= BASE_URL ?>/appointments/reject-cancel/<?= $appt['id'] ?>">
                    <?= Helpers::csrfField() ?>
                    <button class="btn btn-success"><i class="bi bi-check2"></i> رد درخواست (نوبت برقرار می‌ماند)</button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>
	
    <?php if ($appt['status'] === 'pending_receipt'): ?>
        <hr class="my-4">
        <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">رسید آپلود شده توسط کلاینت</h6>
        <div class="border rounded p-3 mb-3 text-center">
            <?php if (!empty($appt['receipt_image'])): ?>
                <img src="<?= Helpers::e(Helpers::privateMediaUrl($appt['receipt_image'])) ?>" style="max-width:400px;border-radius:10px;cursor:pointer;" data-bs-toggle="modal" data-bs-target="#receiptModal">
                <p class="small text-muted mt-2 mb-0">برای بزرگ‌نمایی کلیک کنید</p>
            <?php else: ?>
                <p class="text-muted mb-0">رسیدی آپلود نشده است</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <hr class="my-4">

    <!-- اکشن‌های وضعیت -->
    <?php if (in_array(Auth::role(), ['super_admin', 'admin', 'secretary'])): ?>

        <?php if ($appt['status'] === 'pending_receipt'): ?>
            <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">اکشن‌های تایید رسید</h6>
            <div class="d-flex flex-wrap gap-2 mb-4">
                <form method="POST" action="<?= BASE_URL ?>/appointments/confirm-receipt/<?= $appt['id'] ?>">
                    <?= Helpers::csrfField() ?>
                    <button class="btn btn-success"><i class="bi bi-check2"></i> تایید رسید و قطعی کردن</button>
                </form>
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cancelModal" onclick="setAction('suspended')">تعلیق</button>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal" onclick="setAction('cancelled')">لغو</button>
            </div>
        <?php endif; ?>

        <?php if ($appt['status'] === 'confirmed'): ?>
            <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">اکشن‌ها</h6>
            <div class="d-flex flex-wrap gap-2 mb-4">
                <form method="POST" action="<?= BASE_URL ?>/appointments/awaiting/<?= $appt['id'] ?>">
                    <?= Helpers::csrfField() ?>
                    <button class="btn btn-brand"><i class="bi bi-person-check"></i> در انتظار اجرا (ورود کلاینت)</button>
                </form>
                <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#cancelModal" onclick="setAction('suspended')">تعلیق</button>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#cancelModal" onclick="setAction('cancelled')">لغو</button>
            </div>
        <?php endif; ?>

        <?php if ($appt['status'] === 'awaiting_service'): 
            $remaining = $appt['service_price'] - $appt['deposit_amount'];
        ?>
            <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">تسویه حساب</h6>
            <form method="POST" action="<?= BASE_URL ?>/appointments/settle/<?= $appt['id'] ?>" class="row g-3 mb-4">
                <?= Helpers::csrfField() ?>
                <div class="col-md-4">
                    <label class="form-label">مبلغ دریافتی (تومان) *</label>
                    <input type="number" name="paid_amount" class="form-control" value="<?= $remaining ?>" min="0" required>
                    <small class="text-muted">باقی‌مانده: <?= number_format($remaining) ?> تومان</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">روش پرداخت *</label>
                    <select name="payment_method" class="form-select" required>
                        <option value="cash">نقدی</option>
                        <option value="pos">کارتخوان</option>
                        <option value="card_to_card">کارت‌به‌کارت</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-brand w-100"><i class="bi bi-cash-coin"></i> تایید تسویه</button>
                </div>
            </form>
        <?php endif; ?>

        <?php if ($appt['status'] === 'paid'): ?>
            <div class="alert alert-info">
                <i class="bi bi-clock"></i> منتظر بستن نوبت توسط اپراتور. (اپراتور باید توضیحات خدمات را ثبت کند)
            </div>
        <?php endif; ?>

        <?php if (!empty($service_operators)): ?>
            <hr class="my-4">
            <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">تغییر اپراتور</h6>
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#operatorModal">
                <i class="bi bi-person-swap"></i> تغییر اپراتور نوبت
            </button>
        <?php endif; ?>

    <?php endif; ?>

    <?php if (Auth::role() === 'operator' && $appt['operator_id'] == Auth::id() && in_array($appt['status'], ['awaiting_service','paid','confirmed'])): ?>
        <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">بستن نوبت</h6>
        <form method="POST" action="<?= BASE_URL ?>/appointments/status/<?= $appt['id'] ?>">
            <?= Helpers::csrfField() ?>
            <input type="hidden" name="status" value="done">
            <div class="mb-3">
                <textarea name="operator_note" class="form-control" rows="3" placeholder="توضیحات خدمات ارائه‌شده / نکات جلسه بعد (اجباری)" required></textarea>
            </div>
            <button class="btn btn-brand"><i class="bi bi-check2-circle"></i> بستن نوبت</button>
        </form>
    <?php endif; ?>

</div>

<!-- Modal لغو/تعلیق -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="cancelForm">
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="action" id="cancelAction">
                <div class="modal-header">
                    <h5 class="modal-title">ثبت دلیل</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">دلیل *</label>
                    <textarea name="reason" class="form-control" rows="3" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                    <button class="btn btn-brand">ثبت</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal نمایش بزرگ رسید -->
<?php if (!empty($appt['receipt_image'])): ?>
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">رسید پرداخت</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img src="<?= Helpers::e(Helpers::privateMediaUrl($appt['receipt_image'])) ?>" style="max-width:100%;border-radius:10px;">
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function setAction(action) {
    document.getElementById('cancelAction').value = action;
    document.getElementById('cancelForm').action = '<?= BASE_URL ?>/appointments/suspend-cancel/<?= $appt['id'] ?>';
}

<!-- Modal تغییر اپراتور -->
<?php if (!empty($service_operators)): ?>
<div class="modal fade" id="operatorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/appointments/change-operator/<?= $appt['id'] ?>">
                <?= Helpers::csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title">تغییر اپراتور نوبت</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        <b>اپراتور فعلی:</b> <?= Helpers::e($appt['operator_name'] ?? '—') ?>
                    </p>
                    <div class="mb-3">
                        <label class="form-label">اپراتور جدید *</label>
                        <select name="new_operator_id" class="form-select" required>
                            <option value="">-- انتخاب اپراتور --</option>
                            <?php foreach ($service_operators as $op): ?>
                                <option value="<?= $op['id'] ?>">
                                    <?= Helpers::e($op['full_name']) ?>
                                    <?= $op['is_doctor'] ? '(دکتر)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">علت تغییر *</label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="مثلاً: عدم حضور اپراتور قبلی"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">انصراف</button>
                    <button class="btn btn-brand">تغییر اپراتور</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>