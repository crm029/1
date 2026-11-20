<?php
class Backup extends Model {
    public function all() {
        return $this->fetchAll("SELECT * FROM backups ORDER BY id DESC");
    }

    public function findById($id) {
        return $this->fetchOne("SELECT * FROM backups WHERE id = ?", [$id]);
    }

    public function add($filename, $size, $type = 'manual', $note = null, $status = 'success', $backup_type = 'database') {
        return $this->insert('backups', [
            'filename' => $filename,
            'file_size' => $size,
            'type' => $type,
            'backup_type' => $backup_type,
            'status' => $status,
            'note' => $note,
        ]);
    }

    public function remove($id) {
        $this->query("DELETE FROM backups WHERE id = ?", [$id]);
    }

    public function autoCleanup($days = 30) {
        $rows = $this->fetchAll(
            "SELECT * FROM backups WHERE type = 'auto' AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)",
            [$days]
        );
        $dir = __DIR__ . '/../../storage/backups/';
        foreach ($rows as $r) {
            if (file_exists($dir . $r['filename'])) unlink($dir . $r['filename']);
            $this->remove($r['id']);
        }
    }

    public function lastAutoBackup() {
        return $this->fetchOne("SELECT * FROM backups WHERE type = 'auto' ORDER BY id DESC LIMIT 1");
    }
}