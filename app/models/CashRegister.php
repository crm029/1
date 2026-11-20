<?php
class CashRegister extends Model {
    public function findByDate($date) {
        return $this->fetchOne("SELECT * FROM cash_register WHERE register_date = ?", [$date]);
    }

    public function findById($id) {
        return $this->fetchOne("SELECT * FROM cash_register WHERE id = ?", [$id]);
    }

    public function getOrCreate($date) {
        $row = $this->findByDate($date);
        if ($row) return $row;
        $id = $this->insert('cash_register', ['register_date' => $date]);
        return $this->findById($id);
    }

    public function updateStats($id, $data) {
        $this->update('cash_register', $data, 'id = ?', [$id]);
    }

    public function lastClosed() {
        return $this->fetchOne("SELECT * FROM cash_register WHERE is_closed = 1 ORDER BY register_date DESC LIMIT 1");
    }

    public function recent($limit = 30) {
        return $this->fetchAll("SELECT * FROM cash_register ORDER BY register_date DESC LIMIT {$limit}");
    }
}