<?php
// ===================================================
// mailer.php — ส่งอีเมลแบบ 3 วิธี (เลือกได้ใน config)
// MODE 1: php_mail  — ใช้ mail() ของโฮสต์ (แนะนำสำหรับ shared hosting)
// MODE 2: smtp      — SMTP ภายนอก เช่น Gmail (ต้องโฮสต์เปิด port 587)
// MODE 3: smtp_host — SMTP ของโฮสต์เอง (cPanel email)
// ===================================================

class Mailer {

    public static function sendApproval(int $userId, string $toEmail, string $toName): bool {
        $subject = '✅ บัญชีของคุณได้รับการอนุมัติแล้ว — ' . APP_NAME;
        $body    = self::approvalTemplate($toName, $toEmail);

        $mode = defined('MAIL_MODE') ? MAIL_MODE : 'php_mail';

        $sent = match($mode) {
            'smtp'      => self::sendSMTP($toEmail, $toName, $subject, $body),
            'smtp_host' => self::sendSMTP($toEmail, $toName, $subject, $body),
            default     => self::sendPhpMail($toEmail, $toName, $subject, $body),
        };

        try {
            DB::run(
                "INSERT INTO email_logs (user_id, to_email, subject, status) VALUES (?, ?, ?, ?)",
                [$userId, $toEmail, $subject, $sent ? 'sent' : 'failed']
            );
        } catch (Exception $e) {
            error_log('Email log error: ' . $e->getMessage());
        }

        return $sent;
    }

    // ===================================================
    // MODE 1: PHP mail() — ใช้กับ shared hosting ทั่วไป
    // ===================================================
    private static function sendPhpMail(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $boundary       = md5(uniqid());
        $fromName       = '=?UTF-8?B?' . base64_encode(MAIL_FROM_NAME) . '?=';

        $headers  = "From: {$fromName} <" . MAIL_FROM_EMAIL . ">\r\n";
        $headers .= "Reply-To: " . MAIL_FROM_EMAIL . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $plain  = strip_tags(str_replace(['<br>','<br/>','</p>'], "\n", $htmlBody));
        $body   = "--{$boundary}\r\n";
        $body  .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body  .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body  .= chunk_split(base64_encode($plain)) . "\r\n";
        $body  .= "--{$boundary}\r\n";
        $body  .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body  .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body  .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $body  .= "--{$boundary}--";

        $to = "=?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>";
        return mail($to, $encodedSubject, $body, $headers);
    }

    // ===================================================
    // MODE 2 & 3: SMTP socket (Gmail หรือ SMTP โฮสต์)
    // ===================================================
    private static function sendSMTP(string $toEmail, string $toName, string $subject, string $htmlBody): bool {
        try {
            $socket = fsockopen(MAIL_HOST, MAIL_PORT, $errno, $errstr, 15);
            if (!$socket) throw new Exception("Connect failed: $errstr ($errno)");
            stream_set_timeout($socket, 15);

            self::sr($socket);
            self::sw($socket, "EHLO " . gethostname());
            self::sr($socket);
            self::sw($socket, "STARTTLS");
            self::sr($socket);
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            self::sw($socket, "EHLO " . gethostname());
            self::sr($socket);
            self::sw($socket, "AUTH LOGIN");
            self::sr($socket);
            self::sw($socket, base64_encode(MAIL_USERNAME));
            self::sr($socket);
            self::sw($socket, base64_encode(MAIL_PASSWORD));
            $auth = self::sr($socket);
            if (strpos($auth, '235') === false) throw new Exception("Auth failed: $auth");

            self::sw($socket, "MAIL FROM:<" . MAIL_FROM_EMAIL . ">");
            self::sr($socket);
            self::sw($socket, "RCPT TO:<{$toEmail}>");
            self::sr($socket);
            self::sw($socket, "DATA");
            self::sr($socket);

            $boundary       = md5(uniqid());
            $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $fromName       = '=?UTF-8?B?' . base64_encode(MAIL_FROM_NAME) . '?=';
            $plain          = strip_tags(str_replace(['<br>','<br/>','</p>'], "\n", $htmlBody));

            $msg  = "From: {$fromName} <" . MAIL_FROM_EMAIL . ">\r\n";
            $msg .= "To: =?UTF-8?B?" . base64_encode($toName) . "?= <{$toEmail}>\r\n";
            $msg .= "Subject: {$encodedSubject}\r\n";
            $msg .= "MIME-Version: 1.0\r\n";
            $msg .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $msg .= "Date: " . date('r') . "\r\n\r\n";
            $msg .= "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n";
            $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $msg .= chunk_split(base64_encode($plain)) . "\r\n";
            $msg .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n";
            $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $msg .= chunk_split(base64_encode($htmlBody)) . "\r\n";
            $msg .= "--{$boundary}--\r\n";

            fwrite($socket, $msg . "\r\n.\r\n");
            $result = self::sr($socket);
            self::sw($socket, "QUIT");
            fclose($socket);
            return strpos($result, '250') !== false;

        } catch (Exception $e) {
            error_log('SMTP Error: ' . $e->getMessage());
            if (isset($socket) && is_resource($socket)) fclose($socket);
            return false;
        }
    }

    private static function sw($socket, string $cmd): void { fwrite($socket, $cmd . "\r\n"); }
    private static function sr($socket): string {
        $r = '';
        while ($line = fgets($socket, 515)) { $r .= $line; if ($line[3] === ' ') break; }
        return $r;
    }

    private static function approvalTemplate(string $name, string $email): string {
        $loginUrl = APP_URL . '/login.php';
        $appName  = APP_NAME;
        $year     = date('Y');
        return <<<HTML
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  body{margin:0;padding:0;background:#f0f2f5;font-family:'Segoe UI',Arial,sans-serif;}
  .wrap{max-width:540px;margin:32px auto;background:#fff;border-radius:20px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,.1);}
  .header{background:linear-gradient(135deg,#667eea,#764ba2);padding:44px 32px;text-align:center;}
  .header h1{color:#fff;font-size:26px;margin:0 0 6px;font-weight:700;}
  .header p{color:rgba(255,255,255,.85);font-size:15px;margin:0;}
  .body{padding:36px 40px;}
  .badge{display:inline-block;background:#e8faf5;border:1.5px solid #00b894;color:#00a381;border-radius:50px;padding:8px 22px;font-size:14px;font-weight:700;margin-bottom:22px;}
  .body p{color:#555;line-height:1.9;font-size:15px;margin:0 0 16px;}
  .email-box{background:#f8f9ff;border:1.5px solid #dde3ff;border-radius:10px;padding:13px 18px;color:#667eea;font-size:15px;font-weight:600;margin:20px 0 28px;word-break:break-all;}
  .btn{display:block;text-align:center;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff!important;text-decoration:none;padding:16px 32px;border-radius:12px;font-size:16px;font-weight:700;}
  .footer{background:#f8f9fa;padding:20px 32px;text-align:center;color:#aaa;font-size:12px;border-top:1px solid #eee;}
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <div style="font-size:52px;margin-bottom:12px">🎉</div>
    <h1>ยินดีต้อนรับ!</h1>
    <p>บัญชีสมาชิกของคุณพร้อมใช้งานแล้ว</p>
  </div>
  <div class="body">
    <div class="badge">✅ ได้รับการอนุมัติแล้ว</div>
    <p>สวัสดีคุณ <strong style="color:#2d3436">{$name}</strong>,</p>
    <p>บัญชีสมาชิกของคุณใน <strong>{$appName}</strong> ได้รับการอนุมัติจากผู้ดูแลระบบเรียบร้อยแล้ว คุณสามารถเข้าสู่ระบบได้ทันทีด้วยอีเมล:</p>
    <div class="email-box">📧 {$email}</div>
    <a href="{$loginUrl}" class="btn">เข้าสู่ระบบเลย →</a>
  </div>
  <div class="footer">© {$year} {$appName} &nbsp;·&nbsp; อีเมลนี้ถูกส่งโดยอัตโนมัติ กรุณาอย่าตอบกลับ</div>
</div>
</body>
</html>
HTML;
    }
}