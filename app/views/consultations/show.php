<?php ob_start(); 

// متن‌های آماده برای توضیحات تماس
$quick_texts = [
    'تماس گرفته شد و مشاوره کامل انجام شد.',
    'تماس گرفته شد ولی کلاینت پاسخ نداد.',
    'تماس گرفته شد ولی شماره اشغال بود.',
    'مشاوره تلفنی انجام شد و کلاینت برای نوبت هماهنگ شد.',
    'مشاوره تلفنی انجام شد و کلاینت درخواست مشاوره حضوری داشت.',
    'کلاینت درخواست قیمت داشت و اطلاعات ارسال شد.',
    'کلاینت فعلاً منصرف شد.',
    'شماره اشتباه است / کلاینت یافت نشد.',
];
?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">جزئیات درخواست مشاوره #<?= $request['id'] ?></h5>
        <a href="<?= BASE_URL ?>/consultations" class="btn btn-light">بازگشت</a>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= Helpers::e($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="row g-3">
        <div class="col-md-6">
            <div class="border rounded p-3 h-100">
                <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">اطلاعات کلاینت</h6>
                <p><b>نام:</b> <?= Helpers::e($request['full_name']) ?></p>
                <p>
                    <b>موبایل:</b> 
                    <a href="tel:<?= Helpers::e($request['mobile']) ?>" class="btn btn-sm btn-outline-success" style="font-family:monospace;">
                        <i class="bi bi-telephone-fill"></i> <?= Helpers::e($request['mobile']) ?>
                    </a>
                </p>
                <p class="mb-0"><b>تاریخ درخواست:</b> <?= Helpers::toJalaliString($request['created_at']) ?></p>
            </div>
        </div>

        <div class="col-md-6">
            <div class="border rounded p-3 h-100">
                <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">وضعیت</h6>
                <?php
                $badges = ['pending' => 'warning', 'answered' => 'info', 'closed' => 'success'];
                $labels = ['pending' => 'در انتظار', 'answered' => 'پاسخ داده شده', 'closed' => 'بسته شده'];
                ?>
                <p class="mb-0">
                    <span class="badge bg-<?= $badges[$request['status']] ?? 'secondary' ?>" style="font-size:0.9rem;">
                        <?= $labels[$request['status']] ?? $request['status'] ?>
                    </span>
                </p>
                <?php if (!empty($request['answered_at'])): ?>
                    <p class="mt-2 mb-0 small text-muted">
                        آخرین بروزرسانی: <?= Helpers::toJalaliString($request['answered_at']) ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <hr class="my-4">

    <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">متن درخواست کلاینت</h6>
    <div class="alert alert-light border">
        <?= nl2br(Helpers::e($request['message'])) ?>
    </div>

    <?php if (!empty($request['answer'])): ?>
        <hr class="my-4">
        <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">توضیحات/نتیجه تماس</h6>
        <div class="alert alert-info border">
            <?= nl2br(Helpers::e($request['answer'])) ?>
        </div>
    <?php endif; ?>

    <?php if ($request['status'] !== 'closed'): ?>
        <hr class="my-4">
        <h6 class="mb-3" style="color:<?= BRAND_COLOR ?>;">ثبت نتیجه تماس و بستن درخواست</h6>

        <!-- متن‌های آماده -->
        <div class="mb-3">
            <label class="form-label small text-muted">
                <i class="bi bi-lightning-charge-fill"></i> متن‌های آماده (کلیک کنید تا اضافه شود):
            </label>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($quick_texts as $i => $qt): ?>
                    <button type="button" class="quick-text-btn" data-text="<?= Helpers::e($qt) ?>">
                        <i class="bi bi-plus-circle"></i> <?= Helpers::e($qt) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/consultations/close/<?= $request['id'] ?>" id="closeForm">
            <?= Helpers::csrfField() ?>
            <div class="mb-3">
                <label class="form-label">توضیحات تماس <span class="text-danger">*</span></label>
                <textarea name="answer" id="answerText" class="form-control" rows="6" required placeholder="روی متن‌های آماده بالا کلیک کنید یا خودتان بنویسید..."></textarea>
                <small class="text-muted">
                    <span id="charCount">0</span> کاراکتر
                    <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-2" id="clearBtn">پاک کردن همه</button>
                </small>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-brand"><i class="bi bi-check-lg"></i> ثبت و بستن درخواست</button>
                <a href="<?= BASE_URL ?>/consultations" class="btn btn-light">انصراف</a>
            </div>
        </form>
    <?php endif; ?>
</div>

<style>
.quick-text-btn {
    background: #fff;
    border: 1px solid #e0d5d5;
    color: #666;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.82rem;
    cursor: pointer;
    transition: all 0.15s;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.quick-text-btn:hover {
    background: #D1B1B0;
    color: #fff;
    border-color: #D1B1B0;
    transform: translateY(-2px);
    box-shadow: 0 3px 10px rgba(209,177,176,0.3);
}
.quick-text-btn i {
    font-size: 0.9rem;
}
</style>

<script>
const answerText = document.getElementById('answerText');
const charCount = document.getElementById('charCount');

function updateCharCount() {
    if (answerText) {
        charCount.textContent = answerText.value.length;
    }
}

document.querySelectorAll('.quick-text-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const text = btn.dataset.text;
        const current = answerText.value.trim();

        // اگه متن قبلاً بود، اضافه نکن
        if (current.includes(text)) {
            return;
        }

        if (current === '') {
            answerText.value = text;
        } else {
            answerText.value = current + '\n' + text;
        }

        updateCharCount();
        answerText.focus();

        // افکت کوتاه
        btn.style.transform = 'scale(0.95)';
        setTimeout(() => { btn.style.transform = ''; }, 150);
    });
});

if (answerText) {
    answerText.addEventListener('input', updateCharCount);
    updateCharCount();
}

const clearBtn = document.getElementById('clearBtn');
if (clearBtn) {
    clearBtn.addEventListener('click', function() {
        if (confirm('متن توضیحات پاک شود؟')) {
            answerText.value = '';
            updateCharCount();
            answerText.focus();
        }
    });
}
</script>
<?php $content = ob_get_clean(); ?>
<?php require __DIR__ . '/../layouts/panel.php'; ?>