-- ===================================================
-- ระบบล็อกอิน/สมัครสมาชิก พร้อมอนุมัติโดย Admin
-- ===================================================

CREATE DATABASE IF NOT EXISTS auth_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE auth_system;

-- ตาราง users
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NOT NULL UNIQUE,
    password    VARCHAR(255)  NOT NULL,
    role        ENUM('admin','user') NOT NULL DEFAULT 'user',
    status      ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตาราง sessions
CREATE TABLE IF NOT EXISTS sessions (
    id          VARCHAR(128) PRIMARY KEY,
    user_id     INT NOT NULL,
    ip_address  VARCHAR(45),
    user_agent  TEXT,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at  DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ตาราง email_logs (บันทึกการส่งอีเมล)
CREATE TABLE IF NOT EXISTS email_logs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    to_email    VARCHAR(150) NOT NULL,
    subject     VARCHAR(255) NOT NULL,
    status      ENUM('sent','failed') NOT NULL DEFAULT 'sent',
    sent_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin เริ่มต้น (password: admin1234)
INSERT IGNORE INTO users (name, email, password, role, status)
VALUES ('Administrator', 'admin@system.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'approved');
-- หมายเหตุ: hash ด้านบนคือ password_hash('admin1234', PASSWORD_BCRYPT)
-- เปลี่ยน admin เป็น email/password ที่ต้องการใน config.php
