<?php
// ============================================
// خدمات، دسته‌بندی‌ها و اپراتورها
// ============================================

function handleServices($db, $action, $input) {
    switch ($action) {

        // ============ لیست دسته‌بندی‌ها ============
        case 'get_categories':
            $stmt = $db->query("SELECT id, name, icon, color 
                                FROM service_categories 
                                WHERE is_active = 1 AND deleted_at IS NULL 
                                ORDER BY sort_order ASC, name ASC");
            $categories = $stmt->fetchAll();

            $result = [];
            foreach ($categories as $c) {
                $chk = $db->prepare("SELECT COUNT(*) AS c 
                                     FROM services 
                                     WHERE category_id = ? AND is_active = 1 AND deleted_at IS NULL");
                $chk->execute([$c['id']]);
                $count = (int)$chk->fetch()['c'];
                if ($count > 0) {
                    $c['services_count'] = $count;
                    $result[] = $c;
                }
            }

            jsonOut(['status' => 'success', 'categories' => $result]);
            break;

        // ============ لیست خدمات یک دسته ============
        case 'get_services':
            $category_id = (int)($input['category_id'] ?? 0);
            $client_gender = $input['client_gender'] ?? null;

            if (!$category_id) {
                jsonError('دسته‌بندی نامعتبر');
            }

            $stmt = $db->prepare("SELECT id, name, description, duration_minutes, price, 
                                         is_doctor_service, gender_restriction 
                                  FROM services 
                                  WHERE category_id = ? AND is_active = 1 AND deleted_at IS NULL 
                                  ORDER BY name ASC");
            $stmt->execute([$category_id]);
            $services = $stmt->fetchAll();

            // فیلتر بر اساس جنسیت
            $filtered = [];
            foreach ($services as $s) {
                if ($s['gender_restriction'] === 'none' 
                    || !$client_gender 
                    || $s['gender_restriction'] === $client_gender) {
                    $filtered[] = $s;
                }
            }

            jsonOut(['status' => 'success', 'services' => $filtered]);
            break;

        // ============ لیست اپراتورهای یک خدمت ============
        // ============ لیست اپراتورهای یک خدمت ============
        case 'get_operators':
            $service_id = (int)($input['service_id'] ?? 0);
            $client_gender = $input['client_gender'] ?? null;

            if (!$service_id) {
                jsonError('خدمت نامعتبر');
            }

            $stmt = $db->prepare("SELECT id, name, price, is_doctor_service, require_same_gender_operator 
                                  FROM services 
                                  WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$service_id]);
            $service = $stmt->fetch();
            if (!$service) {
                jsonError('خدمت یافت نشد');
            }

            $stmt = $db->prepare("SELECT u.id, u.full_name, u.gender, u.is_doctor 
                                  FROM operator_services os
                                  JOIN users u ON u.id = os.operator_id
                                  WHERE os.service_id = ? AND os.deleted_at IS NULL 
                                    AND u.is_active = 1 AND u.deleted_at IS NULL
                                  ORDER BY u.full_name ASC");
            $stmt->execute([$service_id]);
            $operators = $stmt->fetchAll();

            $result = [];
            foreach ($operators as $op) {
                if ($service['require_same_gender_operator'] 
                    && $client_gender 
                    && $op['gender'] !== $client_gender) {
                    continue;
                }
                $result[] = [
                    'id' => (int)$op['id'],
                    'name' => $op['full_name'],
                    'is_doctor' => (int)$op['is_doctor'],
                ];
            }

            jsonOut([
                'status' => 'success',
                'operators' => $result,
                'is_doctor_service' => (int)$service['is_doctor_service'],
                'service_name' => $service['name'],
                'service_price' => (int)$service['price'],
            ]);
            break;
    }
}