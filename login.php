<?php
// ===================================================
// login.php
// ===================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

// ถ้า login แล้วให้ redirect
if (Auth::check()) {
    $role = $_SESSION['user_role'] ?? 'user';
    header('Location: ' . ($role === 'admin' ? 'admin.php' : 'dashboard.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = Auth::login($_POST['email'] ?? '', $_POST['password'] ?? '');
    if ($result['ok']) {
        header('Location: ' . ($result['role'] === 'admin' ? 'admin.php' : 'dashboard.php'));
        exit;
    }
    $error = $result['msg'];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>เข้าสู่ระบบ — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: #0d0d14; font-family: 'Sarabun', sans-serif; overflow: hidden;
  }
  /* Animated background */
  .bg-orb {
    position: fixed; border-radius: 50%; filter: blur(80px); opacity: .25; pointer-events: none;
  }
  .bg-orb-1 { width:500px; height:500px; background:#667eea; top:-150px; left:-150px; animation: float1 8s ease-in-out infinite; }
  .bg-orb-2 { width:400px; height:400px; background:#764ba2; bottom:-100px; right:-100px; animation: float2 10s ease-in-out infinite; }
  @keyframes float1 { 0%,100%{transform:translate(0,0)} 50%{transform:translate(40px,30px)} }
  @keyframes float2 { 0%,100%{transform:translate(0,0)} 50%{transform:translate(-30px,-40px)} }

  .card {
    position: relative; z-index: 1; width: 100%; max-width: 420px; padding: 48px 40px;
    background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.09);
    border-radius: 24px; backdrop-filter: blur(24px);
    box-shadow: 0 32px 80px rgba(0,0,0,.5); animation: fadeUp .5s ease;
  }
  @keyframes fadeUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:none} }

  .logo { font-size: 52px; text-align: center; margin-bottom: 12px; }
  h1 { color: #fff; font-size: 26px; font-weight: 700; text-align: center; margin-bottom: 4px; }
  .subtitle { color: #777; text-align: center; font-size: 15px; margin-bottom: 32px; }

  label { display: block; color: #aaa; font-size: 13px; font-weight: 600;
          letter-spacing: .5px; margin-bottom: 6px; text-transform: uppercase; }
  .field { margin-bottom: 18px; }
  input[type=email], input[type=password], input[type=text] {
    width: 100%; padding: 13px 16px; background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1); border-radius: 12px;
    color: #fff; font-size: 15px; font-family: 'Sarabun', sans-serif;
    outline: none; transition: border-color .2s, background .2s;
  }
  input:focus { border-color: rgba(102,126,234,.7); background: rgba(102,126,234,.08); }
  input::placeholder { color: #444; }

  .alert {
    background: rgba(214,48,49,.15); border: 1px solid rgba(214,48,49,.4);
    color: #ff7675; border-radius: 10px; padding: 12px 16px; font-size: 14px;
    margin-bottom: 18px; display: flex; align-items: center; gap: 8px;
  }
  .btn {
    width: 100%; padding: 15px; margin-top: 4px;
    background: linear-gradient(135deg,#667eea,#764ba2);
    border: none; border-radius: 12px; color: #fff; font-size: 16px;
    font-weight: 700; font-family: 'Sarabun', sans-serif; cursor: pointer;
    transition: opacity .2s, transform .1s; letter-spacing: .3px;
  }
  .btn:hover { opacity: .9; transform: translateY(-1px); }
  .btn:active { transform: translateY(0); }

  .divider { text-align: center; color: #444; font-size: 13px; margin: 22px 0; position: relative; }
  .divider::before, .divider::after {
    content:''; position:absolute; top:50%; width:42%; height:1px; background:rgba(255,255,255,.08);
  }
  .divider::before { left: 0; } .divider::after { right: 0; }

  .link-btn {
    display: block; text-align: center; padding: 13px;
    background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08);
    border-radius: 12px; color: #a29bfe; font-size: 15px; font-weight: 600;
    text-decoration: none; transition: background .2s;
  }
  .link-btn:hover { background: rgba(162,155,254,.1); }

  .hint { text-align: center; color: #444; font-size: 12px; margin-top: 20px; }
</style>
</head>
<body>
<div class="bg-orb bg-orb-1"></div>
<div class="bg-orb bg-orb-2"></div>

<div class="card">
  <div class="logo">🔐</div>
  <h1>เข้าสู่ระบบ</h1>
  <p class="subtitle">ยินดีต้อนรับกลับมา</p>

  <?php if ($error): ?>
    <div class="alert">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div class="field">
      <label>อีเมล</label>
      <input type="email" name="email" placeholder="example@email.com"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
    </div>
    <div class="field">
      <label>รหัสผ่าน</label>
      <input type="password" name="password" placeholder="••••••••" required>
    </div>
    <button type="submit" class="btn">เข้าสู่ระบบ</button>
  </form>

  <div class="divider">หรือ</div>
  <a href="register.php" class="link-btn">📝 สมัครสมาชิกใหม่</a>
  <p class="hint">Admin: admin@system.com / admin1234</p>
</div>
</body>
</html>
