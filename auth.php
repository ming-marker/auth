<?php
// ===================================================
// auth.php — ฟังก์ชันจัดการ Auth
// ===================================================

class Auth {

    // --- ตรวจสอบว่า login อยู่หรือไม่ ---
    public static function check(): bool {
        return isset($_SESSION['user_id']);
    }

    // --- ข้อมูล user ปัจจุบัน ---
    public static function user(): ?array {
        if (!self::check()) return null;
        return DB::run(
            "SELECT id, name, email, role, status FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        )->fetch();
    }

    // --- ตรวจสอบว่าเป็น admin ---
    public static function isAdmin(): bool {
        $user = self::user();
        return $user && $user['role'] === 'admin';
    }

    // --- บังคับ login ---
    public static function requireLogin(string $redirect = 'login.php'): void {
        if (!self::check()) {
            header("Location: {$redirect}");
            exit;
        }
    }

    // --- บังคับ admin ---
    public static function requireAdmin(string $redirect = 'dashboard.php'): void {
        self::requireLogin();
        if (!self::isAdmin()) {
            header("Location: {$redirect}");
            exit;
        }
    }

    // --- Login ---
    public static function login(string $email, string $password): array {
        $user = DB::run(
            "SELECT * FROM users WHERE email = ? LIMIT 1",
            [trim($email)]
        )->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return ['ok' => false, 'msg' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง'];
        }
        if ($user['status'] === 'pending') {
            return ['ok' => false, 'msg' => 'บัญชีของคุณยังรอการอนุมัติจากผู้ดูแลระบบ'];
        }
        if ($user['status'] === 'rejected') {
            return ['ok' => false, 'msg' => 'บัญชีของคุณถูกปฏิเสธ กรุณาติดต่อผู้ดูแลระบบ'];
        }

        // สร้าง session
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_at'] = time();

        return ['ok' => true, 'role' => $user['role']];
    }

    // --- Register ---
    public static function register(string $name, string $email, string $password): array {
        $name  = trim($name);
        $email = strtolower(trim($email));

        if (strlen($name) < 2)  return ['ok' => false, 'msg' => 'ชื่อต้องมีอย่างน้อย 2 ตัวอักษร'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'msg' => 'รูปแบบอีเมลไม่ถูกต้อง'];
        if (strlen($password) < 6) return ['ok' => false, 'msg' => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร'];

        $exists = DB::run("SELECT id FROM users WHERE email = ?", [$email])->fetch();
        if ($exists) return ['ok' => false, 'msg' => 'อีเมลนี้ถูกใช้งานแล้ว'];

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        DB::run(
            "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'user', 'pending')",
            [$name, $email, $hash]
        );

        return ['ok' => true];
    }

    // --- Logout ---
    public static function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
