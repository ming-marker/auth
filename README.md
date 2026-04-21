# ระบบล็อกอิน / สมัครสมาชิก พร้อมอนุมัติโดย Admin
**PHP + MySQL + PHPMailer**

---

## โครงสร้างไฟล์

```
auth/
├── config.php       ← ตั้งค่า DB, SMTP, App
├── db.php           ← PDO connection class
├── auth.php         ← ฟังก์ชัน login / register / session
├── mailer.php       ← ส่งอีเมลแจ้งเตือนผ่าน SMTP
├── login.php        ← หน้าเข้าสู่ระบบ
├── register.php     ← หน้าสมัครสมาชิก
├── dashboard.php    ← หน้าหลักผู้ใช้ทั่วไป
├── admin.php        ← แผงควบคุมผู้ดูแลระบบ
├── database.sql     ← สร้างตาราง + ข้อมูลเริ่มต้น
└── vendor/          ← PHPMailer (ติดตั้งผ่าน Composer)
```

---

## วิธีติดตั้ง

### 1. สร้างฐานข้อมูล
```sql
-- รันไฟล์ database.sql ใน phpMyAdmin หรือ MySQL CLI
mysql -u root -p < database.sql
```

### 2. ติดตั้ง PHPMailer
```bash
cd auth/
composer require phpmailer/phpmailer
```

### 3. ตั้งค่าใน config.php
```php
// ฐานข้อมูล
define('DB_HOST', 'localhost');
define('DB_NAME', 'auth_system');
define('DB_USER', 'root');
define('DB_PASS', 'your_db_password');

// Gmail SMTP
define('MAIL_USERNAME', 'your@gmail.com');
define('MAIL_PASSWORD', 'xxxx xxxx xxxx xxxx'); // App Password
```

### 4. ตั้งค่า Gmail App Password
1. เปิด Google Account → Security
2. เปิด 2-Step Verification
3. ไปที่ App Passwords → สร้าง App Password ใหม่
4. นำ password ที่ได้มาใส่ใน `MAIL_PASSWORD`

---

## การใช้งาน

| หน้า | URL |
|------|-----|
| เข้าสู่ระบบ | `/auth/login.php` |
| สมัครสมาชิก | `/auth/register.php` |
| หน้าผู้ใช้ | `/auth/dashboard.php` |
| แผงควบคุม Admin | `/auth/admin.php` |

**บัญชี Admin เริ่มต้น:**
- Email: `admin@system.com`
- Password: `admin1234`

---

## Flow การทำงาน

```
[ผู้ใช้สมัครสมาชิก]
       ↓
[บันทึก DB: status = 'pending']
       ↓
[Admin เห็นใน admin.php]
       ↓
[Admin กด "อนุมัติ"]
       ↓
[DB: status = 'approved']  +  [ส่งอีเมลแจ้งเตือน]
       ↓
[ผู้ใช้ได้รับอีเมล → เข้าสู่ระบบได้]
```

---

## ตารางฐานข้อมูล

### users
| คอลัมน์ | ชนิด | คำอธิบาย |
|---------|------|----------|
| id | INT AUTO_INCREMENT | Primary key |
| name | VARCHAR(100) | ชื่อผู้ใช้ |
| email | VARCHAR(150) UNIQUE | อีเมล |
| password | VARCHAR(255) | bcrypt hash |
| role | ENUM(admin,user) | บทบาท |
| status | ENUM(pending,approved,rejected) | สถานะ |
| created_at | DATETIME | วันที่สมัคร |

### email_logs
| คอลัมน์ | ชนิด | คำอธิบาย |
|---------|------|----------|
| id | INT | Primary key |
| user_id | INT FK | อ้างอิง users |
| to_email | VARCHAR | ผู้รับ |
| subject | VARCHAR | หัวข้อ |
| status | ENUM(sent,failed) | ผลการส่ง |
| sent_at | DATETIME | เวลาส่ง |
