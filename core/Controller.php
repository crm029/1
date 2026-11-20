<?php
class Controller {
    protected function view($path, $data = []) {
        extract($data);
        $file = __DIR__ . '/../app/views/' . $path . '.php';
        if (!file_exists($file)) die("View not found: {$path}");
        require $file;
    }

    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }

    protected function json($data) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}