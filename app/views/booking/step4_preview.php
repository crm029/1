<?php ob_start(); ?>
<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="card p-4">
                    <h4 class="mb-2">پیش‌نمایش رزرو</h4>
                    <p class="text-muted small mb-4">گام ۴ از ۴ — بررسی نهایی و پرداخت بیعانه</p>

                    <?php if (!empty($_SESSION['booking_error'])): ?>
                        <div class="alert alert-danger"><?= Helpers::e($_SESSION['booking_error']); unset($_SESSION['booking_error']); ?></div>
                    <?php endif; ?>

                    <div class="alert alert-info">
                        <b>مشتری گرامی، <?= Helpers::e($clientName) ?></b><br>
                        <small>لطفاً اطلاعات زیر را بررسی کنید و در صورت تایید، بیعانه را کارت‌به‌کارت کرده و رسید را بارگذاری نمایید.</small>
                    </div>

                    <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">جزئیات نوبت‌ها</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>خدمت</th>
                                    <th>تاریخ</th>
                                    <th>ساعت</th>
                                    <th>مدت</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $r): ?>
                                    <tr>
                                        <td><?= Helpers::e($r['service_name']) ?></td>
                                        <td><?= Helpers::toJalaliString($r['date']) ?></td>
                                        <td><?= Helpers::e($r['start']) ?> - <?= Helpers::e($r['end']) ?></td>
                                        <td><?= $r['duration'] ?> دقیقه</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="border rounded p-3 mb-4">
                        <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">کد تخفیف</h6>
                        <div id="discountBox">
                            <?php if (!empty($discount)): ?>
                                <div class="alert alert-success mb-0">
                                    <b>کد فعال:</b> <span style="font-family:monospace;"><?= Helpers::e($discount['code']) ?></span>
                                    — تخفیف: <b><?= number_format($discount['amount']) ?> تومان</b>
                                    <button type="button" class="btn btn-sm btn-outline-danger float-start" onclick="removeDiscount()">حذف</button>
                                </div>
                            <?php else: ?>
                                <div class="input-group">
                                    <input type="text" id="discountCode" class="form-control text-uppercase" placeholder="کد تخفیف را وارد کنید" style="font-family:monospace;letter-spacing:2px;">
                                    <button class="btn btn-brand" type="button" onclick="applyDiscount()">اعمال</button>
                                </div>
                                <div id="discountMsg" class="small mt-2"></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($discount) && $discountAmount > 0): ?>
                        <div class="alert alert-success text-center">
                            <i class="bi bi-check-circle"></i>
                            <?php if (($discount['type'] ?? 'fixed') === 'percent'): ?>
                                <b><?= (int)$discount['value'] ?>٪ تخفیف</b> روی مبلغ نهایی شما اعمال شد.
                            <?php else: ?>
                                <b><?= number_format($discountAmount) ?> تومان تخفیف</b> روی مبلغ نهایی شما اعمال شد.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="alert alert-warning text-center">
                        <h5 class="mb-1">مبلغ بیعانه قابل پرداخت (کارت‌به‌کارت):</h5>
                        <h3 class="mb-0" style="color:<?= BRAND_COLOR ?>;"><?= number_format($finalDeposit) ?> تومان</h3>
                        <small>به ازای هر خدمت <?= number_format((int)($settings['deposit_amount'] ?? 200000)) ?> تومان</small>
                    </div>

                    <div class="border rounded p-3 mb-4">
                        <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">اطلاعات کارت</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><b>شماره کارت:</b></p>
                                <p class="mb-3" style="letter-spacing:2px;font-size:1.1rem;font-family:monospace;"><?= Helpers::e($settings['card_number'] ?? '—') ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><b>به نام:</b></p>
                                <p class="mb-3"><?= Helpers::e($settings['card_holder'] ?? '—') ?></p>
                            </div>
                        </div>
                        <p class="text-muted small mb-0">پس از واریز، تصویر رسید را در قسمت زیر بارگذاری کنید.</p>
                    </div>

                    <form method="POST" action="<?= BASE_URL ?>/booking/submit" enctype="multipart/form-data">
                        <?= Helpers::csrfField() ?>
                        <div class="mb-3">
                            <label class="form-label">آپلود تصویر رسید *</label>
                            <input type="file" name="receipt" class="form-control" accept="image/jpeg,image/png" required>
                            <small class="text-muted">فرمت jpg یا png، حداکثر ۳ مگابایت</small>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="accept_terms" id="accept_terms" required>
                            <label class="form-check-label" for="accept_terms">
                                قوانین و سیاست‌های کلینیک را مطالعه کرده و می‌پذیرم.
                                <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">مشاهده قوانین</a>
                            </label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/booking/schedule" class="btn btn-light">بازگشت</a>
                            <button class="btn btn-brand">تایید و ثبت رزرو</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal قوانین -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">قوانین و سیاست‌های کلینیک</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul class="small">
                    <li>لغو نوبت تا ۲۴ ساعت قبل بدون جریمه و با عودت کامل بیعانه امکان‌پذیر است.</li>
                    <li>لغو کمتر از ۲۴ ساعت قبل مشمول کسر ۳۰٪ از بیعانه می‌شود.</li>
                    <li>در صورت عدم حضور بدون اطلاع‌رسانی قبلی (No-Show)، کل بیعانه سوخت می‌شود.</li>
                    <li>تغییر تاریخ تا یک روز قبل از نوبت بدون جریمه امکان‌پذیر است.</li>
                    <li>پس از تایید رسید توسط منشی، نوبت شما قطعی خواهد شد و کد پیگیری برایتان پیامک می‌شود.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function applyDiscount() {
    const code = document.getElementById('discountCode').value.trim();
    const msg = document.getElementById('discountMsg');
    if (!code) {
        msg.innerHTML = '<span class="text-danger">کد را وارد کنید</span>';
        return;
    }
    msg.innerHTML = '<span class="text-muted">در حال بررسی...</span>';

    const fd = new FormData();
    fd.append('csrf_token', '<?= Helpers::csrfToken() ?>');
    fd.append('code', code);

    fetch('<?= BASE_URL ?>/booking/validate-discount', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.error) {
                msg.innerHTML = '<span class="text-danger">' + res.error + '</span>';
                return;
            }
            location.reload();
        });
}

function removeDiscount() {
    const fd = new FormData();
    fd.append('csrf_token', '<?= Helpers::csrfToken() ?>');
    fetch('<?= BASE_URL ?>/booking/remove-discount', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(() => location.reload());
}
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/public.php'; ?>