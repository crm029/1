<?php
class Captcha {
    public static function generate() {
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $_SESSION['captcha_answer'] = $a + $b;
        $_SESSION['captcha_time'] = time();
        return "{$a} + {$b} = ?";
    }

    public static function verify($input) {
        if (!isset($_SESSION['captcha_answer'])) return false;
        if (time() - ($_SESSION['captcha_time'] ?? 0) > 300) return false;
        $ok = (int)$input === (int)$_SESSION['captcha_answer'];
        unset($_SESSION['captcha_answer'], $_SESSION['captcha_time']);
        return $ok;
    }
}