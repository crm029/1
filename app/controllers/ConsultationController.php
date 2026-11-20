<?php
class ConsultationController extends Controller {

    public function index() {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);

        $filters = [
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];

        $model = new Model();
        $sql = "SELECT * FROM consultation_requests WHERE deleted_at IS NULL";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (full_name LIKE ? OR mobile LIKE ? OR message LIKE ?)";
            $s = '%' . $filters['search'] . '%';
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }
        $sql .= " ORDER BY 
                    CASE status 
                        WHEN 'pending' THEN 1 
                        WHEN 'answered' THEN 2 
                        WHEN 'closed' THEN 3 
                    END,
                    created_at DESC";

        $requests = $model->fetchAll($sql, $params);

        $this->view('consultations/index', [
            'title' => 'درخواست‌های مشاوره',
            'requests' => $requests,
            'filters' => $filters,
        ]);
    }

    public function show($id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);

        $model = new Model();
        $request = $model->fetchOne(
            "SELECT * FROM consultation_requests WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );

        if (!$request) {
            $_SESSION['error'] = 'درخواست یافت نشد';
            return $this->redirect(BASE_URL . '/consultations');
        }

        $this->view('consultations/show', [
            'title' => 'جزئیات درخواست مشاوره',
            'request' => $request,
        ]);
    }

    public function close($id) {
        Auth::requireRole(['super_admin', 'admin', 'secretary']);
        Helpers::verifyCsrf();

        $model = new Model();
        $request = $model->fetchOne(
            "SELECT * FROM consultation_requests WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );

        if (!$request) {
            $_SESSION['error'] = 'درخواست یافت نشد';
            return $this->redirect(BASE_URL . '/consultations');
        }

        $answer = Helpers::clean($_POST['answer'] ?? '');

        if (mb_strlen($answer) < 3) {
            $_SESSION['error'] = 'توضیحات تماس الزامی است';
            return $this->redirect(BASE_URL . '/consultations/show/' . $id);
        }

        $model->update('consultation_requests', [
            'status' => 'closed',
            'answer' => $answer,
            'answered_by' => Auth::id(),
            'answered_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        (new ActivityLog())->log('close', 'بستن درخواست مشاوره ' . $request['full_name'], 'consultation_requests', $id);

        $_SESSION['success'] = 'درخواست مشاوره با موفقیت بسته شد';
        $this->redirect(BASE_URL . '/consultations');
    }
}