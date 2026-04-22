<?php
// ===================================================
// admin.php — กัญญาวีร์ อินทร์เทียน
// ===================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/mailer.php';

Auth::requireAdmin();
$currentUser = Auth::user();

$toast = ['msg' => '', 'type' => ''];

// --- ประมวลผลคำสั่ง ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId > 0) {
        $target = DB::run("SELECT * FROM users WHERE id = ? AND role = 'user'", [$userId])->fetch();

        if ($target) {
            if ($action === 'approve') {
                DB::run("UPDATE users SET status = 'approved' WHERE id = ?", [$userId]);
                $sent = Mailer::sendApproval($userId, $target['email'], $target['name']);
                $toast = [
                    'msg'  => "อนุมัติ {$target['name']} เรียบร้อย" . ($sent ? ' และส่งอีเมลแจ้งเตือนแล้ว ✉️' : ' (ส่งอีเมลไม่สำเร็จ)'),
                    'type' => $sent ? 'success' : 'warning'
                ];
            } elseif ($action === 'reject') {
                DB::run("UPDATE users SET status = 'rejected' WHERE id = ?", [$userId]);
                $toast = ['msg' => "ปฏิเสธบัญชี {$target['name']} แล้ว", 'type' => 'error'];
            }
        }
    }
}

// --- ดึงข้อมูล ---
$tab     = $_GET['tab'] ?? 'pending';
$allowed = ['pending', 'approved', 'rejected', 'all'];
$tab     = in_array($tab, $allowed) ? $tab : 'pending';

$where   = $tab !== 'all' ? "AND status = '{$tab}'" : '';
$users   = DB::run("SELECT * FROM users WHERE role = 'user' {$where} ORDER BY created_at DESC")->fetchAll();

$counts  = DB::run("
    SELECT
        SUM(status='pending')  AS pending,
        SUM(status='approved') AS approved,
        SUM(status='rejected') AS rejected,
        COUNT(*) AS total
    FROM users WHERE role = 'user'
")->fetch();

// --- Logout ---
if (isset($_GET['logout'])) { Auth::logout(); header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>แผงควบคุม Admin — <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { min-height:100vh; background:#0d0d14; font-family:'Sarabun',sans-serif; color:#fff; }

  /* Toast */
  .toast {
    position: fixed; top: 20px; right: 20px; z-index: 9999;
    padding: 14px 20px; border-radius: 14px; font-size: 15px;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 8px 32px rgba(0,0,0,.4); animation: slideIn .3s ease;
    max-width: 360px;
  }
  .toast.success { background: linear-gradient(135deg,#00b894,#00cec9); }
  .toast.error   { background: linear-gradient(135deg,#d63031,#e17055); }
  .toast.warning { background: linear-gradient(135deg,#f39c12,#e17055); }
  @keyframes slideIn { from{opacity:0;transform:translateX(20px)} to{opacity:1;transform:none} }

  /* Layout */
  .sidebar {
    position: fixed; top: 0; left: 0; bottom: 0; width: 240px;
    background: rgba(255,255,255,.03); border-right: 1px solid rgba(255,255,255,.07);
    padding: 28px 20px; display: flex; flex-direction: column; gap: 8px;
  }
  .sidebar-logo { font-size: 22px; font-weight: 700; color: #fff; padding: 8px 12px; margin-bottom: 16px; }
  .sidebar-logo span { color: #a29bfe; }
  .nav-item {
    padding: 11px 14px; border-radius: 10px; color: #888; font-size: 15px;
    text-decoration: none; display: flex; align-items: center; gap: 10px;
    transition: all .2s; cursor: pointer; border: none; background: none; width: 100%; font-family: 'Sarabun', sans-serif;
  }
  .nav-item:hover { background: rgba(255,255,255,.06); color: #ccc; }
  .nav-item.active { background: linear-gradient(135deg,rgba(102,126,234,.25),rgba(118,75,162,.25));
                     color: #a29bfe; border: 1px solid rgba(162,155,254,.2); }
  .main { margin-left: 240px; padding: 32px; min-height: 100vh; }

  /* Header */
  .page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 32px; }
  .page-title { font-size: 26px; font-weight: 700; }
  .page-sub { color: #666; font-size: 14px; margin-top: 4px; }
  .logout-btn {
    background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.1);
    color: #aaa; border-radius: 10px; padding: 10px 18px; cursor: pointer;
    font-family: 'Sarabun', sans-serif; font-size: 14px; text-decoration: none;
    transition: background .2s;
  }
  .logout-btn:hover { background: rgba(214,48,49,.15); color: #ff7675; }

  /* Stats */
  .stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 28px; }
  .stat-card {
    background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07);
    border-radius: 16px; padding: 20px 16px; text-align: center;
  }
  .stat-num { font-size: 36px; font-weight: 700; line-height: 1; margin: 6px 0 4px; }
  .stat-lbl { color: #666; font-size: 13px; }

  /* Tabs */
  .tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
  .tab-btn {
    padding: 9px 18px; border-radius: 10px; font-size: 14px; font-weight: 600;
    font-family: 'Sarabun', sans-serif; cursor: pointer; text-decoration: none;
    background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08);
    color: #888; transition: all .2s;
  }
  .tab-btn:hover { color: #ccc; background: rgba(255,255,255,.08); }
  .tab-btn.active {
    background: linear-gradient(135deg,#667eea,#764ba2);
    color: #fff; border-color: transparent;
  }

  /* Table */
  .table-wrap { background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.07); border-radius: 18px; overflow: hidden; }
  table { width: 100%; border-collapse: collapse; }
  thead th { padding: 14px 20px; font-size: 12px; font-weight: 700; color: #555;
             text-transform: uppercase; letter-spacing: .5px; text-align: left;
             border-bottom: 1px solid rgba(255,255,255,.06); }
  tbody tr { border-bottom: 1px solid rgba(255,255,255,.04); transition: background .2s; }
  tbody tr:last-child { border-bottom: none; }
  tbody tr:hover { background: rgba(255,255,255,.03); }
  td { padding: 16px 20px; font-size: 14px; vertical-align: middle; }

  .avatar {
    width: 42px; height: 42px; border-radius: 50%; display: inline-flex;
    align-items: center; justify-content: center; font-weight: 700; font-size: 18px;
    background: linear-gradient(135deg,#667eea,#764ba2); color: #fff; flex-shrink: 0;
  }
  .user-info { display: flex; align-items: center; gap: 12px; }
  .user-name { font-weight: 600; color: #fff; font-size: 15px; }
  .user-email { color: #666; font-size: 13px; margin-top: 2px; }
  .user-date { color: #555; font-size: 12px; margin-top: 2px; }

  .badge {
    display: inline-block; padding: 4px 12px; border-radius: 20px;
    font-size: 13px; font-weight: 600; white-space: nowrap;
  }
  .badge-pending  { background:rgba(243,156,18,.15);  color:#f39c12;  border:1px solid rgba(243,156,18,.3); }
  .badge-approved { background:rgba(0,184,148,.15);   color:#00b894;  border:1px solid rgba(0,184,148,.3); }
  .badge-rejected { background:rgba(214,48,49,.15);   color:#ff7675;  border:1px solid rgba(214,48,49,.3); }

  .actions { display: flex; gap: 8px; flex-wrap: wrap; }
  .btn-approve, .btn-reject, .btn-reapprove {
    padding: 8px 16px; border: none; border-radius: 9px; cursor: pointer;
    font-size: 13px; font-weight: 700; font-family: 'Sarabun', sans-serif; transition: opacity .2s;
  }
  .btn-approve   { background: linear-gradient(135deg,#00b894,#00cec9); color:#fff; }
  .btn-reject    { background: linear-gradient(135deg,#d63031,#e17055); color:#fff; }
  .btn-reapprove { background: linear-gradient(135deg,#667eea,#764ba2); color:#fff; }
  .btn-approve:hover, .btn-reject:hover, .btn-reapprove:hover { opacity:.85; }

  .empty { text-align: center; padding: 60px; color: #444; font-size: 18px; }

  /* Responsive */
  @media(max-width:768px){
    .sidebar{ display:none; }
    .main{ margin-left:0; padding:20px; }
    .stats-grid{ grid-template-columns:1fr 1fr; }
    table thead{ display:none; }
    tbody tr{ display:block; padding:16px 20px; }
    td{ display:block; border:none; padding:4px 0; }
  }
</style>
</head>
<body>

<?php if ($toast['msg']): ?>
<div class="toast <?= $toast['type'] ?>" id="toast">
  <?= $toast['type'] === 'success' ? '✅' : ($toast['type'] === 'error' ? '❌' : '⚠️') ?>
  <?= htmlspecialchars($toast['msg']) ?>
</div>
<script>setTimeout(()=>document.getElementById('toast')?.remove(), 4000);</script>
<?php endif; ?>

<!-- Sidebar -->
<aside class="sidebar">
  <div class="sidebar-logo">⚙️ <span>Admin</span></div>
  <a href="admin.php?tab=pending"  class="nav-item <?= $tab==='pending'  ? 'active':'' ?>">⏳ รอการอนุมัติ <strong style="margin-left:auto;color:#f39c12"><?= $counts['pending'] ?></strong></a>
  <a href="admin.php?tab=approved" class="nav-item <?= $tab==='approved' ? 'active':'' ?>">✅ อนุมัติแล้ว</a>
  <a href="admin.php?tab=rejected" class="nav-item <?= $tab==='rejected' ? 'active':'' ?>">❌ ถูกปฏิเสธ</a>
  <a href="admin.php?tab=all"      class="nav-item <?= $tab==='all'      ? 'active':'' ?>">👥 ทั้งหมด</a>
  <div style="margin-top:auto">
    <a href="?logout=1" class="nav-item" style="color:#ff7675">🚪 ออกจากระบบ</a>
  </div>
</aside>

<!-- Main -->
<main class="main">
  <div class="page-header">
    <div>
      <div class="page-title">แผงควบคุมผู้ดูแลระบบ</div>
      <div class="page-sub">สวัสดี <?= htmlspecialchars($currentUser['name']) ?> · จัดการการสมัครสมาชิก</div>
    </div>
    <a href="?logout=1" class="logout-btn">🚪 ออกจากระบบ</a>
  </div>

  <!-- Stats -->
  <div class="stats-grid">
    <div class="stat-card" style="border-top:3px solid #f39c12">
      <div style="font-size:24px">⏳</div>
      <div class="stat-num" style="color:#f39c12"><?= $counts['pending'] ?></div>
      <div class="stat-lbl">รอการอนุมัติ</div>
    </div>
    <div class="stat-card" style="border-top:3px solid #00b894">
      <div style="font-size:24px">✅</div>
      <div class="stat-num" style="color:#00b894"><?= $counts['approved'] ?></div>
      <div class="stat-lbl">อนุมัติแล้ว</div>
    </div>
    <div class="stat-card" style="border-top:3px solid #d63031">
      <div style="font-size:24px">❌</div>
      <div class="stat-num" style="color:#d63031"><?= $counts['rejected'] ?></div>
      <div class="stat-lbl">ถูกปฏิเสธ</div>
    </div>
    <div class="stat-card" style="border-top:3px solid #667eea">
      <div style="font-size:24px">👥</div>
      <div class="stat-num" style="color:#a29bfe"><?= $counts['total'] ?></div>
      <div class="stat-lbl">สมาชิกทั้งหมด</div>
    </div>
  </div>

  <!-- Tabs -->
  <div class="tabs">
    <a href="?tab=pending"  class="tab-btn <?= $tab==='pending'  ? 'active':'' ?>">⏳ รอการอนุมัติ (<?= $counts['pending'] ?>)</a>
    <a href="?tab=approved" class="tab-btn <?= $tab==='approved' ? 'active':'' ?>">✅ อนุมัติแล้ว (<?= $counts['approved'] ?>)</a>
    <a href="?tab=rejected" class="tab-btn <?= $tab==='rejected' ? 'active':'' ?>">❌ ถูกปฏิเสธ (<?= $counts['rejected'] ?>)</a>
    <a href="?tab=all"      class="tab-btn <?= $tab==='all'      ? 'active':'' ?>">👥 ทั้งหมด (<?= $counts['total'] ?>)</a>
  </div>

  <!-- Table -->
  <div class="table-wrap">
    <?php if (empty($users)): ?>
      <div class="empty">📭 ไม่มีข้อมูลในหมวดนี้</div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>ผู้ใช้</th>
          <th>สถานะ</th>
          <th>วันที่สมัคร</th>
          <th>การดำเนินการ</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div class="user-info">
              <div class="avatar"><?= mb_strtoupper(mb_substr($u['name'], 0, 1)) ?></div>
              <div>
                <div class="user-name"><?= htmlspecialchars($u['name']) ?></div>
                <div class="user-email"><?= htmlspecialchars($u['email']) ?></div>
              </div>
            </div>
          </td>
          <td>
            <span class="badge badge-<?= $u['status'] ?>">
              <?= ['pending'=>'⏳ รอการอนุมัติ','approved'=>'✅ อนุมัติแล้ว','rejected'=>'❌ ถูกปฏิเสธ'][$u['status']] ?>
            </span>
          </td>
          <td style="color:#666;font-size:13px">
            <?= date('d/m/Y H:i', strtotime($u['created_at'])) ?>
          </td>
          <td>
            <div class="actions">
              <?php if ($u['status'] === 'pending'): ?>
                <form method="POST">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <input type="hidden" name="action" value="approve">
                  <button type="submit" class="btn-approve" onclick="return confirm('อนุมัติบัญชี <?= htmlspecialchars($u['name']) ?> ?')">✅ อนุมัติ</button>
                </form>
                <form method="POST">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <input type="hidden" name="action" value="reject">
                  <button type="submit" class="btn-reject" onclick="return confirm('ปฏิเสธบัญชี <?= htmlspecialchars($u['name']) ?> ?')">❌ ปฏิเสธ</button>
                </form>
              <?php elseif ($u['status'] === 'rejected'): ?>
                <form method="POST">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <input type="hidden" name="action" value="approve">
                  <button type="submit" class="btn-reapprove">↩️ อนุมัติใหม่</button>
                </form>
              <?php else: ?>
                <span style="color:#555;font-size:13px">—</span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
