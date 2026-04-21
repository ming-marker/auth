<?php
// ===================================================
// dashboard.php — หน้าหลักสำหรับผู้ใช้ทั่วไป
// ===================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

Auth::requireLogin();
$user = Auth::user();

if ($user['role'] === 'admin') {
    header('Location: admin.php'); exit;
}

if (isset($_GET['logout'])) { Auth::logout(); header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>หน้าหลัก — <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    min-height: 100vh; background: #0d0d14; font-family: 'Sarabun', sans-serif;
    display: flex; align-items: center; justify-content: center; padding: 24px;
  }
  .bg-orb { position:fixed; border-radius:50%; filter:blur(90px); opacity:.15; pointer-events:none; }
  .bg-orb-1 { width:500px; height:500px; background:#667eea; top:-150px; left:-150px; }
  .bg-orb-2 { width:400px; height:400px; background:#00b894; bottom:-100px; right:-100px; }

  .card {
    position: relative; z-index: 1; width: 100%; max-width: 500px;
    background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.09);
    border-radius: 24px; backdrop-filter: blur(24px);
    box-shadow: 0 32px 80px rgba(0,0,0,.5); overflow: hidden;
    animation: fadeUp .5s ease;
  }
  @keyframes fadeUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:none} }

  .card-header {
    background: linear-gradient(135deg,#667eea,#764ba2);
    padding: 32px; text-align: center;
  }
  .avatar {
    width: 72px; height: 72px; border-radius: 50%; background: rgba(255,255,255,.2);
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; font-weight: 700; color: #fff; margin: 0 auto 14px;
    border: 3px solid rgba(255,255,255,.3);
  }
  .card-header h2 { color: #fff; font-size: 22px; margin-bottom: 4px; }
  .card-header p  { color: rgba(255,255,255,.75); font-size: 14px; }

  .card-body { padding: 28px 32px; }
  .info-row {
    display: flex; align-items: center; gap: 12px; padding: 14px 0;
    border-bottom: 1px solid rgba(255,255,255,.06); color: #ccc; font-size: 15px;
  }
  .info-row:last-child { border-bottom: none; }
  .info-icon { font-size: 20px; width: 30px; text-align: center; flex-shrink: 0; }
  .info-label { color: #666; font-size: 13px; margin-bottom: 2px; }

  .badge-approved {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(0,184,148,.15); border: 1px solid rgba(0,184,148,.35);
    color: #00b894; border-radius: 50px; padding: 6px 16px; font-size: 14px; font-weight: 600;
  }
  .logout-link {
    display: block; text-align: center; margin-top: 20px; padding: 12px;
    background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08);
    border-radius: 12px; color: #888; text-decoration: none; font-size: 15px;
    transition: all .2s;
  }
  .logout-link:hover { background: rgba(214,48,49,.12); color: #ff7675; border-color: rgba(214,48,49,.3); }
</style>
</head>
<body>
<div class="bg-orb bg-orb-1"></div>
<div class="bg-orb bg-orb-2"></div>

<div class="card">
  <div class="card-header">
    <div class="avatar"><?= mb_strtoupper(mb_substr($user['name'], 0, 1)) ?></div>
    <h2>ยินดีต้อนรับ!</h2>
    <p>คุณเข้าสู่ระบบสำเร็จแล้ว 🎉</p>
  </div>
  <div class="card-body">
    <div class="info-row">
      <span class="info-icon">👤</span>
      <div>
        <div class="info-label">ชื่อ-นามสกุล</div>
        <div><?= htmlspecialchars($user['name']) ?></div>
      </div>
    </div>
    <div class="info-row">
      <span class="info-icon">📧</span>
      <div>
        <div class="info-label">อีเมล</div>
        <div><?= htmlspecialchars($user['email']) ?></div>
      </div>
    </div>
    <div class="info-row">
      <span class="info-icon">🔖</span>
      <div>
        <div class="info-label">สถานะบัญชี</div>
        <div><span class="badge-approved">✅ ได้รับการอนุมัติแล้ว</span></div>
      </div>
    </div>
    <a href="?logout=1" class="logout-link">🚪 ออกจากระบบ</a>
  </div>
</div>
</body>
</html>
