<?php
class Setting extends Model {
    public function all() {
        $rows = $this->fetchAll("SELECT * FROM settings");
        $out = [];
        foreach ($rows as $r) {
            $out[$r['setting_key']] = $r['setting_value'];
        }
        return $out;
    }

    public function get($key, $default = null) {
        $row = $this->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        return $row ? $row['setting_value'] : $default;
    }

    public function set($key, $value, $userId) {
        $exists = $this->fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
        if ($exists) {
            $this->query("UPDATE settings SET setting_value = ?, updated_by = ? WHERE setting_key = ?", [$value, $userId, $key]);
        } else {
            $this->query("INSERT INTO settings (setting_key, setting_value, updated_by) VALUES (?, ?, ?)", [$key, $value, $userId]);
        }
    }
}