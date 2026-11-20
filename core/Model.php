<?php
class Model {
    protected $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    public function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }

    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    public function insert($table, $data) {
        $cols = implode(',', array_keys($data));
        $ph = implode(',', array_fill(0, count($data), '?'));
        $this->query("INSERT INTO {$table} ({$cols}) VALUES ({$ph})", array_values($data));
        return $this->db->lastInsertId();
    }

    public function update($table, $data, $where, $whereParams = []) {
        $set = implode('=?,', array_keys($data)) . '=?';
        $this->query("UPDATE {$table} SET {$set} WHERE {$where}", array_merge(array_values($data), $whereParams));
    }
	
	    public function beginTransaction() {
        return $this->db->beginTransaction();
    }

    public function commit() {
        return $this->db->commit();
    }

    public function rollBack() {
        return $this->db->rollBack();
    }
}