<?php
require_once __DIR__ . '/../models/Backup.php';

class BackupController extends Controller {
    private $backupDir;

    public function __construct() {
        $this->backupDir = __DIR__ . '/../../storage/backups/';
        if (!is_dir($this->backupDir)) mkdir($this->backupDir, 0700, true);
        @chmod($this->backupDir, 0700);
    }

    public function index() {
        Auth::requireRole(['super_admin']);
        $model = new Backup();
        $model->autoCleanup(30);

        $this->view('backups/index', [
            'title' => 'مدیریت بکاپ‌ها',
            'backups' => $model->all(),
        ]);
    }

    public function create() {
        Auth::requireRole(['super_admin']);
        Helpers::verifyCsrf();

        $result = $this->generateBackup('manual');
        if ($result['success']) {
            $_SESSION['success'] = 'بکاپ با موفقیت ایجاد شد: ' . $result['filename'];
        } else {
            $_SESSION['error'] = 'خطا در ایجاد بکاپ: ' . $result['error'];
        }
        $this->redirect(BASE_URL . '/backups');
    }
	
    public function createFull() {
        Auth::requireRole(['super_admin']);
        Helpers::verifyCsrf();

        $result = $this->generateFullZip();
        if ($result['success']) {
            $_SESSION['success'] = 'بکاپ کامل پروژه ایجاد شد: ' . $result['filename'];
        } else {
            $_SESSION['error'] = 'خطا در ایجاد بکاپ کامل: ' . $result['error'];
        }
        $this->redirect(BASE_URL . '/backups');
    }

    private function generateFullZip() {
        $rootDir = realpath(__DIR__ . '/../../');
        $filename = 'full_backup_' . date('Y-m-d_H-i-s') . '.zip';
        $filepath = $this->backupDir . $filename;

        if (!class_exists('ZipArchive')) {
            return ['success' => false, 'error' => 'افزونه ZipArchive روی سرور فعال نیست'];
        }

        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return ['success' => false, 'error' => 'امکان ساخت فایل ZIP نیست'];
        }

        // پوشه‌هایی که نباید داخل بکاپ باشن
        $excludeDirs = [
            realpath($this->backupDir),           // پوشه بکاپ‌ها (جلوگیری از بازگشت)
            realpath($rootDir . '/storage/backups'),
            realpath($rootDir . '/storage/logs'),
        ];

        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($rootDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                $path = $file->getRealPath();
                $skip = false;
                foreach ($excludeDirs as $ex) {
                    if ($ex && strpos($path, $ex) === 0) { $skip = true; break; }
                }
                if ($skip) continue;

                // مسیر نسبی داخل ZIP
                $relative = str_replace($rootDir . DIRECTORY_SEPARATOR, '', $path);
                $relative = str_replace('\\', '/', $relative);

                // Never package live credentials or repository history.
                if (in_array($relative, ['config/config.php', 'config/config.local.php', 'public/bot-api/config.php'], true)
                    || $relative === '.git' || strpos($relative, '.git/') === 0) {
                    continue;
                }

                if ($file->isDir()) {
                    $zip->addEmptyDir($relative);
                } else {
                    $zip->addFile($path, $relative);
                }
            }

            $zip->addFromString('RESTORE-NOTES.txt', "Nayrica full backup\n\nFor security, live configuration files were excluded:\n- config/config.php\n- public/bot-api/config.php\n\nRestore by creating these files from the deployment's secure configuration template and re-entering credentials. Never commit live credentials.\n");
            $zip->close();
            @chmod($filepath, 0600);
            $size = filesize($filepath);

            $model = new Backup();
            $model->add($filename, $size, 'manual', 'بکاپ کامل پروژه', 'success', 'full');

            require_once __DIR__ . '/../models/ActivityLog.php';
            (new ActivityLog())->log('backup', 'بکاپ کامل پروژه ایجاد شد: ' . $filename);

            return ['success' => true, 'filename' => $filename, 'size' => $size];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function delete($id) {
        Auth::requireRole(['super_admin']);
        $model = new Backup();
        $backup = $model->findById($id);
        if (!$backup) {
            $_SESSION['error'] = 'بکاپ یافت نشد';
            return $this->redirect(BASE_URL . '/backups');
        }

        $file = $this->backupDir . $backup['filename'];
        if (file_exists($file)) unlink($file);
        $model->remove($id);

        require_once __DIR__ . '/../models/ActivityLog.php';
        (new ActivityLog())->log('delete', 'حذف بکاپ ' . $backup['filename']);

        $_SESSION['success'] = 'بکاپ حذف شد';
        $this->redirect(BASE_URL . '/backups');
    }

    public function download($id) {
        Auth::requireRole(['super_admin']);
        $model = new Backup();
        $backup = $model->findById($id);
        if (!$backup) {
            $_SESSION['error'] = 'بکاپ یافت نشد';
            return $this->redirect(BASE_URL . '/backups');
        }

        $file = $this->backupDir . $backup['filename'];
        if (!file_exists($file)) {
            $_SESSION['error'] = 'فایل بکاپ روی سرور موجود نیست';
            return $this->redirect(BASE_URL . '/backups');
        }

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $backup['filename'] . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }

    // بکاپ خودکار (از طریق Cron فراخوانی می‌شود)
    public function cron() {
        $secret = $_POST['secret'] ?? '';
        $expectedSecret = getenv('NAYRICA_CRON_SECRET') ?: '';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $expectedSecret === '' || !hash_equals((string)$expectedSecret, (string)$secret)) {
            http_response_code(403);
            die('دسترسی غیرمجاز');
        }

        $result = $this->generateBackup('auto');
        if ($result['success']) {
            echo 'OK: ' . $result['filename'];
        } else {
            echo 'ERROR: ' . $result['error'];
        }
    }

    // ساخت فایل SQL از دیتابیس
    private function generateBackup($type) {
        $config = require __DIR__ . '/../../config/config.php';
        $host = $config['db']['host'];
        $dbname = $config['db']['name'];
        $user = $config['db']['user'];
        $pass = $config['db']['pass'];

        $filename = 'backup_' . $dbname . '_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $this->backupDir . $filename;

        try {
            $pdo = new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (PDOException $e) {
            return ['success' => false, 'error' => 'اتصال به دیتابیس ناموفق'];
        }

        $out = "-- Nayrica Backup\n";
        $out .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
        $out .= "-- Database: {$dbname}\n\n";
        $out .= "SET NAMES utf8mb4;\n";
        $out .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        try {
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                // ساختار
                $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
                $out .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $out .= $create['Create Table'] . ";\n\n";

                // داده‌ها
                $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
                if ($table === 'settings') {
                    foreach ($rows as &$settingRow) {
                        $settingKey = (string)($settingRow['setting_key'] ?? '');
                        if (preg_match('/password|secret|api[_-]?key|token|username/i', $settingKey)) {
                            $settingRow['setting_value'] = '[REDACTED - reconfigure after restore]';
                        }
                    }
                    unset($settingRow);
                }
                if (!empty($rows)) {
                    $columns = array_keys($rows[0]);
                    $colList = '`' . implode('`,`', $columns) . '`';

                    foreach ($rows as $row) {
                        $values = array_map(function($v) use ($pdo) {
                            if ($v === null) return 'NULL';
                            return $pdo->quote($v);
                        }, array_values($row));
                        $out .= "INSERT INTO `{$table}` ({$colList}) VALUES (" . implode(',', $values) . ");\n";
                    }
                    $out .= "\n";
                }
            }

            $out .= "SET FOREIGN_KEY_CHECKS=1;\n";

            if (file_put_contents($filepath, $out, LOCK_EX) === false) {
                return ['success' => false, 'error' => 'ذخیره فایل بکاپ ناموفق بود'];
            }
            @chmod($filepath, 0600);
            $size = filesize($filepath);

            $model = new Backup();
            $model->add($filename, $size, $type);

            require_once __DIR__ . '/../models/ActivityLog.php';
            (new ActivityLog())->log('backup', 'بکاپ ' . ($type === 'auto' ? 'خودکار' : 'دستی') . ' ایجاد شد: ' . $filename);

            return ['success' => true, 'filename' => $filename, 'size' => $size];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}