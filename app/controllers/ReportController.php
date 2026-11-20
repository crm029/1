<?php
require_once __DIR__ . '/../models/Transaction.php';
require_once __DIR__ . '/../models/Setting.php';

class ReportController extends Controller {
    public function index() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        $this->view('reports/index', ['title' => 'گزارش‌ها']);
    }

    // داده‌های درآمد برای نمودار (بازه دلخواه)
    public function revenueData() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        $from = Helpers::clean($_GET['from'] ?? date('Y-m-d', strtotime('-30 days')));
        $to = Helpers::clean($_GET['to'] ?? date('Y-m-d'));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-d', strtotime('-30 days'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) $to = date('Y-m-d');

        $model = new Model();

        // درآمد روزانه (deposit + payment)
        $rows = $model->fetchAll(
            "SELECT DATE(created_at) AS day,
                    SUM(CASE WHEN type IN ('deposit','payment') THEN amount ELSE 0 END) AS income,
                    SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) AS expense,
                    SUM(CASE WHEN type = 'commission' THEN amount ELSE 0 END) AS commission,
                    SUM(CASE WHEN type = 'salary' THEN amount ELSE 0 END) AS salary
             FROM transactions
             WHERE DATE(created_at) BETWEEN ? AND ? AND deleted_at IS NULL
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            [$from, $to]
        );

        // پر کردن روزهای خالی
        $map = [];
        foreach ($rows as $r) $map[$r['day']] = $r;

        $labels = [];
        $incomes = [];
        $expenses = [];
        $profits = [];

        $cur = $from;
        while ($cur <= $to) {
            [$jy, $jm, $jd] = Helpers::toJalaliArray($cur);
            $labels[] = sprintf('%02d/%02d', $jm, $jd);
            $row = $map[$cur] ?? null;
            $inc = (int)($row['income'] ?? 0);
            $exp = (int)($row['expense'] ?? 0) + (int)($row['commission'] ?? 0) + (int)($row['salary'] ?? 0);
            $incomes[] = $inc;
            $expenses[] = $exp;
            $profits[] = $inc - $exp;
            $cur = date('Y-m-d', strtotime($cur . ' +1 day'));
        }

        $totalIncome = array_sum($incomes);
        $totalExpense = array_sum($expenses);
        $totalProfit = $totalIncome - $totalExpense;

        return $this->json([
            'labels' => $labels,
            'income' => $incomes,
            'expense' => $expenses,
            'profit' => $profits,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'totalProfit' => $totalProfit,
        ]);
    }

    // درآمد به تفکیک خدمات
    public function servicesData() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        $from = Helpers::clean($_GET['from'] ?? date('Y-m-d', strtotime('-30 days')));
        $to = Helpers::clean($_GET['to'] ?? date('Y-m-d'));

        $model = new Model();
        $rows = $model->fetchAll(
            "SELECT s.name AS service_name, COUNT(a.id) AS cnt, SUM(a.service_price) AS total
             FROM appointments a
             JOIN services s ON s.id = a.service_id
             WHERE a.status = 'done' AND a.deleted_at IS NULL
               AND a.appointment_date BETWEEN ? AND ?
             GROUP BY s.id, s.name
             ORDER BY total DESC
             LIMIT 10",
            [$from, $to]
        );

        $labels = [];
        $values = [];
        foreach ($rows as $r) {
            $labels[] = $r['service_name'];
            $values[] = (int)$r['total'];
        }
        return $this->json(['labels' => $labels, 'values' => $values]);
    }

    // دستمزد اپراتورها
    public function operatorsData() {
        Auth::requireRole(['super_admin', 'admin', 'accountant']);
        $from = Helpers::clean($_GET['from'] ?? date('Y-m-d', strtotime('-30 days')));
        $to = Helpers::clean($_GET['to'] ?? date('Y-m-d'));

        $model = new Model();
        $rows = $model->fetchAll(
            "SELECT u.full_name AS operator_name,
                    COUNT(a.id) AS cnt,
                    SUM(a.service_price) AS revenue,
                    SUM(a.operator_commission) AS commission
             FROM appointments a
             JOIN users u ON u.id = a.operator_id
             WHERE a.status = 'done' AND a.deleted_at IS NULL
               AND a.appointment_date BETWEEN ? AND ?
             GROUP BY u.id, u.full_name
             ORDER BY commission DESC
             LIMIT 15",
            [$from, $to]
        );

        $labels = [];
        $revenues = [];
        $commissions = [];
        foreach ($rows as $r) {
            $labels[] = $r['operator_name'];
            $revenues[] = (int)$r['revenue'];
            $commissions[] = (int)$r['commission'];
        }
        return $this->json([
            'labels' => $labels,
            'revenues' => $revenues,
            'commissions' => $commissions,
        ]);
    }
}