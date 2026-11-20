<?php
// ============================================
// کدهای تخفیف
// ============================================

function handleDiscount($db, $action, $input) {
    switch ($action) {

        case 'apply_discount_code':
            $code = strtoupper(trim($input['code'] ?? ''));
            $service_id = (int)($input['service_id'] ?? 0);
            $client_id = (int)($input['client_id'] ?? 0);

            if (empty($code)) jsonError('کد را وارد کنید');
            if (!$service_id) jsonError('خدمت نامعتبر');

            // اطلاعات خدمت
            $stmt = $db->prepare("SELECT id, name, price FROM services 
                                  WHERE id = ? AND is_active = 1 AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$service_id]);
            $service = $stmt->fetch();
            if (!$service) jsonError('خدمت یافت نشد');

            // جستجوی کد
            $stmt = $db->prepare("SELECT * FROM discount_codes 
                                  WHERE code = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$code]);
            $discount = $stmt->fetch();

            if (!$discount) jsonError('کد تخفیف نامعتبر است');
            if (!$discount['is_active']) jsonError('این کد تخفیف غیرفعال است');

            // تاریخ اعتبار
            $today = date('Y-m-d');
            if (!empty($discount['start_date']) && $today < $discount['start_date']) {
                jsonError('این کد هنوز فعال نشده است');
            }
            if (!empty($discount['end_date']) && $today > $discount['end_date']) {
                jsonError('این کد تخفیف منقضی شده است');
            }

            // تعداد استفاده
            if (!empty($discount['max_uses']) && $discount['used_count'] >= $discount['max_uses']) {
                jsonError('ظرفیت استفاده از این کد تکمیل شده است');
            }

            // اگه کد مخصوص خدمت خاصی باشه
            if (!empty($discount['service_id']) && $discount['service_id'] != $service_id) {
                jsonError('این کد برای خدمت انتخابی شما معتبر نیست');
            }

            // محاسبه تخفیف
            $service_price = (int)$service['price'];
            $discount_amount = 0;

            if ($discount['discount_type'] === 'percent') {
                $discount_amount = (int)floor($service_price * ((int)$discount['discount_value'] / 100));
            } else {
                $discount_amount = (int)$discount['discount_value'];
            }

            // سقف تخفیف: بیشتر از قیمت خدمت نشه
            if ($discount_amount > $service_price) {
                $discount_amount = $service_price;
            }

            jsonOut([
                'status' => 'success',
                'code_id' => (int)$discount['id'],
                'code' => $discount['code'],
                'discount_type' => $discount['discount_type'],
                'discount_value' => (int)$discount['discount_value'],
                'discount_amount' => $discount_amount,
                'service_price' => $service_price,
            ]);
            break;

        // ============ افزایش شمارنده استفاده ============
        case 'increment_discount_use':
            $code_id = (int)($input['code_id'] ?? 0);
            if (!$code_id) jsonError('کد نامعتبر');

            $db->prepare("UPDATE discount_codes SET used_count = used_count + 1 WHERE id = ?")
               ->execute([$code_id]);

            jsonOut(['status' => 'success']);
            break;
    }
}