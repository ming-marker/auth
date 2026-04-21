<?php
// ===================================================
// register.php
// ===================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (Auth::check()) {
    header('Location: dashboard.php'); exit;
}

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if ($password !== $confirm) {
        $error = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
    } else {
        $result = Auth::register(
            $_POST['name']     ?? '',
            $_POST['email']    ?? '',
            $password
        );
        if ($result['ok']) {
            $success = true;
        } else {
            $error = $result['msg'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>สมัครสมาชิก — <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    background: #0d0d14; font-family: 'Sarabun', sans-serif; padding: 24px;
  }
  .bg-orb { position: fixed; border-radius: 50%; filter: blur(80px); opacity: .2; pointer-events: none; }
  .bg-orb-1 { width:450px; height:450px; background:#00b894; top:-100px; right:-100px; animation:float1 9s ease-in-out infinite; }
  .bg-orb-2 { width:380px; height:380px; background:#667eea; bottom:-80px; left:-80px; animation:float2 11s ease-in-out infinite; }
  @keyframes float1 { 0%,100%{transform:translate(0,0)} 50%{transform:translate(-30px,30px)} }
  @keyframes float2 { 0%,100%{transform:translate(0,0)} 50%{transform:translate(30px,-30px)} }

  .card {
    position: relative; z-index: 1; width: 100%; max-width: 440px; padding: 48px 40px;
    background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.09);
    border-radius: 24px; backdrop-filter: blur(24px);
    box-shadow: 0 32px 80px rgba(0,0,0,.5); animation: fadeUp .5s ease;
  }
  @keyframes fadeUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:none} }

  /* Success state */
  .success-box { text-align: center; }
  .success-icon { font-size: 64px; margin-bottom: 16px; }
  .success-box h2 { color: #fff; font-size: 24px; margin-bottom: 12px; }
  .success-box p { color: #aaa; line-height: 1.8; font-size: 15px; margin-bottom: 24px; }
  .badge-pending {
    display: inline-block; background: rgba(243,156,18,.15);
    border: 1px solid rgba(243,156,18,.4); color: #f39c12;
    border-radius: 50px; padding: 8px 20px; font-size: 14px; font-weight: 600; margin-bottom: 20px;
  }

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
  input:focus { border-color: rgba(0,184,148,.7); background: rgba(0,184,148,.06); }
  input::placeholder { color: #444; }

  .alert {
    background: rgba(214,48,49,.15); border: 1px solid rgba(214,48,49,.4);
    color: #ff7675; border-radius: 10px; padding: 12px 16px; font-size: 14px;
    margin-bottom: 18px; display: flex; align-items: center; gap: 8px;
  }
  .btn {
    width: 100%; padding: 15px; margin-top: 4px;
    background: linear-gradient(135deg,#00b894,#00cec9);
    border: none; border-radius: 12px; color: #fff; font-size: 16px;
    font-weight: 700; font-family: 'Sarabun', sans-serif; cursor: pointer;
    transition: opacity .2s, transform .1s;
  }
  .btn:hover { opacity: .9; transform: translateY(-1px); }
  .btn-secondary {
    width: 100%; padding: 13px; background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.08); border-radius: 12px; color: #a29bfe;
    font-size: 15px; font-weight: 600; font-family: 'Sarabun', sans-serif;
    cursor: pointer; text-decoration: none; display: block; text-align: center;
    transition: background .2s; margin-top: 14px;
  }
  .btn-secondary:hover { background: rgba(162,155,254,.1); }

  .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .strength { display: flex; gap: 4px; margin-top: 6px; }
  .strength-bar { flex: 1; height: 3px; border-radius: 2px; background: rgba(255,255,255,.1); transition: background .3s; }
</style>
</head>
<body>
<div class="bg-orb bg-orb-1"></div>
<div class="bg-orb bg-orb-2"></div>

<div class="card">
<?php if ($success): ?>
  <div class="success-box">
    <div class="success-icon">📬</div>
    <div class="badge-pending">⏳ รอการอนุมัติ</div>
    <h2>สมัครสมาชิกสำเร็จ!</h2>
    <p>
      ขอบคุณที่สมัครสมาชิก บัญชีของคุณกำลังรอการอนุมัติจากผู้ดูแลระบบ<br>
      คุณจะได้รับ <strong style="color:#fff">อีเมลแจ้งเตือน</strong> ทันทีเมื่อบัญชีได้รับการอนุมัติ
    </p>
    <a href="login.php" class="btn" style="background:linear-gradient(135deg,#667eea,#764ba2)">
      กลับสู่หน้าเข้าสู่ระบบ
    </a>
  </div>
<?php else: ?>
  <div class="logo">📝</div>
  <h1>สมัครสมาชิก</h1>
  <p class="subtitle">สร้างบัญชีใหม่ ฟรี!</p>

  <?php if ($error): ?>
    <div class="alert">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" autocomplete="off">
    <div class="field">
      <label>ชื่อ-นามสกุล</label>
      <input type="text" name="name" placeholder="กรอกชื่อของคุณ"
             value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
    </div>
    <div class="field">
      <label>อีเมล</label>
      <input type="email" name="email" placeholder="example@email.com"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
    </div>
    <div class="row2">
      <div class="field">
        <label>รหัสผ่าน</label>
        <input type="password" name="password" id="pw" placeholder="อย่างน้อย 6 ตัว" required minlength="6">
        <div class="strength">
          <div class="strength-bar" id="s1"></div>
          <div class="strength-bar" id="s2"></div>
          <div class="strength-bar" id="s3"></div>
          <div class="strength-bar" id="s4"></div>
        </div>
      </div>
      <div class="field">
        <label>ยืนยันรหัสผ่าน</label>
        <input type="password" name="confirm" placeholder="กรอกอีกครั้ง" required>
      </div>
    </div>
    <button type="submit" class="btn">สมัครสมาชิก →</button>
  </form>

  <a href="login.php" class="btn-secondary">มีบัญชีแล้ว? เข้าสู่ระบบ</a>
<?php endif; ?>
</div>

<script>
// Password strength indicator
const pw = document.getElementById('pw');
const bars = [document.getElementById('s1'), document.getElementById('s2'),
              document.getElementById('s3'), document.getElementById('s4')];
const colors = ['#d63031','#e17055','#f39c12','#00b894'];

pw?.addEventListener('input', () => {
  const v = pw.value;
  let score = 0;
  if (v.length >= 6)  score++;
  if (v.length >= 10) score++;
  if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
  if (/[0-9!@#$%^&*]/.test(v)) score++;
  bars.forEach((b, i) => {
    b.style.background = i < score ? colors[score - 1] : 'rgba(255,255,255,.1)';
  });
});
</script>
</body>
</html>
