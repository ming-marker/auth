<?php
// ===================================================
// config.php — ตั้งค่าระบบ
// ===================================================

// ---------- ฐานข้อมูล ----------
define('DB_HOST', 'localhost');
define('DB_NAME', 'auth_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// define('DB_HOST', 'z301288-md48ev.ps13.zwhhosting.com');
// define('DB_NAME', 'zmdevpsz_auth_system');
// define('DB_USER', 'zmdevpsz');
// define('DB_PASS', 'v2N)2BT2Z5bwq#');
// define('DB_CHARSET', 'utf8mb4');

// ---------- อีเมล ----------
// เลือก MODE การส่งเมล:
//   'php_mail'  = ใช้ mail() ของโฮสต์ (แนะนำสำหรับ shared hosting ทั่วไป)
//   'smtp'      = SMTP ภายนอก เช่น Gmail (โฮสต์ต้องเปิด port 587)
//   'smtp_host' = SMTP ของโฮสต์เอง เช่น mail.yourdomain.com
 
define('MAIL_MODE', 'php_mail'); // ← เปลี่ยนตรงนี้
 
// --- ตั้งค่า SMTP (ใช้เมื่อ MAIL_MODE = 'smtp' หรือ 'smtp_host') ---
define('MAIL_HOST',       'smtp.gmail.com');   // Gmail: smtp.gmail.com | โฮสต์เอง: mail.yourdomain.com
define('MAIL_PORT',       587);                // Gmail: 587 | cPanel: 587 หรือ 465
define('MAIL_USERNAME',   'your_email@gmail.com');
define('MAIL_PASSWORD',   'your_app_password');
 
// --- ชื่อและอีเมลผู้ส่ง (ใช้ทุก mode) ---
define('MAIL_FROM_EMAIL', 'your_email@gmail.com');
define('MAIL_FROM_NAME',  'ระบบจัดการสมาชิก');
 
// ---------- แอปพลิเคชัน ----------
define('APP_NAME', 'Auth System');
define('APP_URL',  'https://baitrus.com/auth');
 
// ---------- Session ----------
define('SESSION_LIFETIME', 3600);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();