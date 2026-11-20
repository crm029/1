<?php
class FinancialLock extends Model {
    public function all() {
        return $this->fetchAll(
            "SELECT fl.*, u.full_name AS locked_by_name
             FROM financial_locks fl
             LEFT JOIN users u ON u.id = fl.locked_by
             ORDER BY fl.lock_year DESC, fl.lock_month DESC"
        );
    }

    public function isLocked($date) {
        [$jy, $jm, $jd] = Helpers::toJalaliArray($date);
        return $this->fetchOne(
            "SELECT id FROM financial_locks WHERE lock_year = ? AND lock_month = ?",
            [$jy, $jm]
        ) ? true : false;
    }

    public function lock($year, $month, $notes = null) {
        $exists = $this->fetchOne(
            "SELECT id FROM financial_locks WHERE lock_year = ? AND lock_month = ?",
            [$year, $month]
        );
        if ($exists) return false;
        $this->insert('financial_locks', [
            'lock_year' => $year,
            'lock_month' => $month,
            'locked_by' => Auth::id(),
            'notes' => $notes,
        ]);
        return true;
    }

    public function unlock($id) {
        $this->query("DELETE FROM financial_locks WHERE id = ?", [$id]);
    }
}