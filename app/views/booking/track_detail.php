<?php ob_start(); ?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <div class="card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="mb-0">پیگیری نوبت</h4>
                        <a href="<?= BASE_URL ?>/booking/track/logout" class="btn btn-sm btn-light">خروج</a>
                    </div>

                    <?php if (!empty($_SESSION['track_error'])): ?>
                        <div class="alert alert-danger"><?= Helpers::e($_SESSION['track_error']); unset($_SESSION['track_error']); ?></div>
                    <?php endif; ?>

                    <?php 
                    $statusColors = [
                        'pending_receipt' => 'warning',
                        'confirmed' => 'success',
                        'suspended' => 'secondary',
                        'cancel_requested' => 'warning',
                        'cancelled' => 'danger',
                        'awaiting_service' => 'info',
                        'paid' => 'primary',
                        'done' => 'dark',
                        'no_show' => 'danger',
                    ];
                    $statuses = [
                        'pending_receipt' => 'در انتظار تایید رسید',
                        'confirmed' => 'قطعی',
                        'suspended' => 'تعلیق',
                        'cancel_requested' => 'درخواست لغو (در انتظار تایید منشی)',
                        'cancelled' => 'لغو شده',
                        'awaiting_service' => 'در انتظار اجرا',
                        'paid' => 'تسویه شده',
                        'done' => 'انجام شده',
                        'no_show' => 'غیبت',
                    ];
                    ?>

                    <div class="border rounded p-3 mb-3">
                        <p><b>کد نوبت:</b> <span class="badge bg-dark"><?= Helpers::e($appt['booking_code']) ?></span></p>
                        <p><b>وضعیت:</b> <span class="badge bg-<?= $statusColors[$appt['status']] ?? 'secondary' ?>"><?= $statuses[$appt['status']] ?? $appt['status'] ?></span></p>
                        <p><b>خدمت:</b> <?= Helpers::e($appt['service_name']) ?></p>
                        <p><b>اپراتور:</b> <?= Helpers::e($appt['operator_name'] ?? 'در حال تخصیص') ?></p>
                        <p><b>تاریخ:</b> <?= Helpers::toJalaliString($appt['appointment_date']) ?></p>
                        <p><b>ساعت:</b> <?= Helpers::e($appt['start_time']) ?> - <?= Helpers::e($appt['end_time']) ?></p>
                    </div>

                    <?php if ($appt['status'] === 'cancel_requested'): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-hourglass-split"></i>
                            درخواست لغو شما ثبت شده و در انتظار بررسی منشی است. به‌زودی با شما تماس گرفته می‌شود.
                        </div>
                    <?php endif; ?>

                    <?php if ($canCancel && $appt['status'] !== 'cancel_requested'): ?>
                        <hr class="my-4">
                        <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">درخواست لغو نوبت</h6>

                        <?php if ($isLessThan24): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i>
                                زمان باقی‌مانده تا نوبت کمتر از ۲۴ ساعت است.
                                در صورت تایید لغو توسط منشی، <b><?= number_format($penalty) ?> تومان</b> (۳۰٪ بیعانه) کسر می‌شود.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle"></i>
                                لغو نوبت در این زمان بدون جریمه است و کل بیعانه (<b><?= number_format($appt['deposit_amount']) ?> تومان</b>) به شما برگشت داده می‌شود.
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?= BASE_URL ?>/booking/track/cancel">
                            <?= Helpers::csrfField() ?>
                            <div class="mb-3">
                                <label class="form-label">دلیل لغو *</label>
                                <textarea name="reason" id="reasonField" class="form-control" rows="3" required oninput="toggleCardFields(this)"></textarea>
                            </div>

                            <div id="cardFields" style="display:none;">
                                <div class="alert alert-info small">
                                    <i class="bi bi-info-circle"></i>
                                    توجه: شماره کارت باید <b>همان کارتی باشد که با آن پرداخت انجام داده‌اید</b>، در غیر این‌صورت واریز مبلغ با تاخیر یا عدم واریز مواجه می‌شود.
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">شماره کارت (۱۶ رقم) *</label>
                                    <input type="text" name="card_number" id="cardNumber" class="form-control" maxlength="16" pattern="[0-9]{16}" placeholder="1234567890123456" style="font-family:monospace;letter-spacing:2px;">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">نام صاحب کارت *</label>
                                    <input type="text" name="card_holder" id="cardHolder" class="form-control" placeholder="فقط حروف فارسی">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-danger" onclick="return confirm('آیا از ثبت درخواست لغو مطمئن هستید؟')">ثبت درخواست لغو</button>
                        </form>

                        <p class="small text-muted mt-3">
                            درخواست شما پس از بررسی منشی تایید یا رد می‌شود. در صورت تایید، مبلغ به همان کارت اعلامی واریز می‌شود.
                        </p>
                    <?php elseif (!$canCancel): ?>
                        <div class="alert alert-secondary">این نوبت در وضعیت فعلی قابل لغو نیست.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function toggleCardFields(el) {
    var box = document.getElementById('cardFields');
    var cn = document.getElementById('cardNumber');
    var ch = document.getElementById('cardHolder');
    if (el.value.trim().length > 0) {
        box.style.display = 'block';
        cn.required = true;
        ch.required = true;
    } else {
        box.style.display = 'none';
        cn.required = false;
        ch.required = false;
    }
}
</script>

<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/public.php'; ?>