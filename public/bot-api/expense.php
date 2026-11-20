<?php
// ============================================
// API حسابدار — دسته‌بندی هزینه + ثبت هزینه
// ============================================

function handleExpense($db, $action, $input) {
    switch ($action) {
        case 'get_expense_categories':  return expGetCategories($db);
        case 'get_expense_category':    return expGetCategory($db, $input);
        case 'add_expense_category':    return expAddCategory($db, $input);
        case 'update_expense_category': return expUpdateCategory($db, $input);
        case 'delete_expense_category': return expDeleteCategory($db, $input);
        case 'submit_expense':          return expSubmit($db, $input);
        default: jsonError('اکشن نامعتبر');
    }
}

// ---------- لیست دسته‌ها + تعداد تراکنش ----------
function expGetCategories($db) {
    $stmt = $db->query("
        SELECT ec.id, ec.name, ec.emoji,
               (SELECT COUNT(*) FROM transactions t
                WHERE t.expense_category_id = ec.id
                  AND t.type = 'expense'
                  AND t.deleted_at IS NULL) AS tx_count
        FROM expense_categories ec
        WHERE ec.is_active = 1
        ORDER BY ec.sort_order, ec.id
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    jsonOut(['status' => 'success', 'data' => $rows]);
}

// ---------- دریافت یک دسته ----------
function expGetCategory($db, $input) {
    $id = (int)($input['id'] ?? 0);
    if (!$id) jsonError('id الزامی است');

    $stmt = $db->prepare("
        SELECT ec.id, ec.name, ec.emoji,
               (SELECT COUNT(*) FROM transactions t
                WHERE t.expense_category_id = ec.id
                  AND t.type = 'expense'
                  AND t.deleted_at IS NULL) AS tx_count
        FROM expense_categories ec
        WHERE ec.id = ? AND ec.is_active = 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) jsonError('دسته یافت نشد');
    jsonOut(['status' => 'success', 'data' => $row]);
}

// ---------- افزودن دسته ----------
function expAddCategory($db, $input) {
    $name  = trim($input['name'] ?? '');
    $emoji = trim($input['emoji'] ?? '');
    if ($name === '') jsonError('نام دسته الزامی است');
    if (mb_strlen($name) > 100) jsonError('نام خیلی طولانی است');

    try {
        $stmt = $db->prepare("INSERT INTO expense_categories (name, emoji, sort_order) VALUES (?, ?, 99)");
        $stmt->execute([$name, $emoji !== '' ? $emoji : null]);
        jsonOut(['status' => 'success', 'data' => ['id' => (int)$db->lastInsertId()]]);
    } catch (PDOException $e) {
        jsonError('نام دسته تکراری است');
    }
}

// ---------- ویرایش دسته ----------
function expUpdateCategory($db, $input) {
    $id    = (int)($input['id'] ?? 0);
    $name  = trim($input['name'] ?? '');
    $emoji = trim($input['emoji'] ?? '');
    if (!$id || $name === '') jsonError('اطلاعات ناقص است');

    $stmt = $db->prepare("SELECT id FROM expense_categories WHERE id = ? AND is_active = 1");
    $stmt->execute([$id]);
    if (!$stmt->fetchColumn()) jsonError('دسته یافت نشد');

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM transactions
        WHERE expense_category_id = ? AND type = 'expense' AND deleted_at IS NULL
    ");
    $stmt->execute([$id]);
    $hasTx = (int)$stmt->fetchColumn() > 0;

    try {
        if ($hasTx) {
            // فقط ایموجی قابل ویرایش است
            $db->prepare("UPDATE expense_categories SET emoji = ? WHERE id = ?")
               ->execute([$emoji !== '' ? $emoji : null, $id]);
            jsonOut(['status' => 'success', 'note' => 'فقط ایموجی به‌روزرسانی شد (دسته تراکنش دارد)']);
        }
        $db->prepare("UPDATE expense_categories SET name = ?, emoji = ? WHERE id = ?")
           ->execute([$name, $emoji !== '' ? $emoji : null, $id]);
        jsonOut(['status' => 'success']);
    } catch (PDOException $e) {
        jsonError('نام دسته تکراری است');
    }
}

// ---------- حذف دسته (فقط اگر خالی باشد) ----------
function expDeleteCategory($db, $input) {
    $id = (int)($input['id'] ?? 0);
    if (!$id) jsonError('id الزامی است');

    $stmt = $db->prepare("SELECT id FROM expense_categories WHERE id = ? AND is_active = 1");
    $stmt->execute([$id]);
    if (!$stmt->fetchColumn()) jsonError('دسته یافت نشد');

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM transactions
        WHERE expense_category_id = ? AND type = 'expense' AND deleted_at IS NULL
    ");
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0)
        jsonError('این دسته تراکنش دارد و قابل حذف نیست');

    $db->prepare("DELETE FROM expense_categories WHERE id = ?")->execute([$id]);
    jsonOut(['status' => 'success']);
}

// ---------- ثبت هزینه ----------
function expSubmit($db, $input) {
    $catId  = (int)($input['category_id'] ?? 0);
    $amount = (int)($input['amount'] ?? 0);
    $desc   = trim($input['description'] ?? '');
    $date   = trim($input['date'] ?? '');
    $userId = (int)($input['user_id'] ?? 0);

    if (!$catId)       jsonError('دسته الزامی است');
    if ($amount <= 0)  jsonError('مبلغ باید بزرگ‌تر از صفر باشد');
    if (!$userId)      jsonError('کاربر نامشخص');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) jsonError('تاریخ نامعتبر است');

    $stmt = $db->prepare("SELECT id FROM expense_categories WHERE id = ? AND is_active = 1");
    $stmt->execute([$catId]);
    if (!$stmt->fetchColumn()) jsonError('دسته نامعتبر است');

    $stmt = $db->prepare("
        INSERT INTO transactions
            (type, expense_category_id, amount, description, transaction_date, created_by, created_at)
        VALUES ('expense', ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$catId, $amount, $desc !== '' ? $desc : null, $date, $userId]);

    jsonOut([
        'status' => 'success',
        'data'   => [
            'id'       => (int)$db->lastInsertId(),
            'amount'   => $amount,
            'date'     => $date,
            'category' => $catId,
        ]
    ]);
}