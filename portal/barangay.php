<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_role(['barangay']);
require_once __DIR__ . '/../config/db.php';

$uid   = (int)$_SESSION['user_id'];
$fname = $_SESSION['first_name'];
$role  = $_SESSION['role'];

// Fetch user profile
$stmt = $conn->prepare("SELECT email, org_name, position, barangay_name, municipality FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i",$uid); $stmt->execute();
$prof = $stmt->get_result()->fetch_assoc(); $stmt->close();
$brgy = $prof['barangay_name'] ?? 'All Barangays';
$city = $prof['municipality']  ?? '';
$org  = $prof['org_name']      ?? 'Barangay Office';
$pos  = $prof['position']      ?? 'Barangay Official';

// Stats
function count_q($conn,$sql,$types='',$params=[]){$s=$conn->prepare($sql);if($types)$s->bind_param($types,...$params);$s->execute();$s->bind_result($n);$s->fetch();$s->close();return(int)$n;}
$total   = count_q($conn,"SELECT COUNT(*) FROM reports WHERE is_archived=0");
$danger  = count_q($conn,"SELECT COUNT(*) FROM reports WHERE status='dangerous' AND is_archived=0");
$caution = count_q($conn,"SELECT COUNT(*) FROM reports WHERE status='caution' AND is_archived=0");
$safe    = count_q($conn,"SELECT COUNT(*) FROM reports WHERE status='safe' AND is_archived=0");

// Recent reports (all, or filtered by barangay if set)
$brgy_filter = $prof['barangay_name'];
$reports = [];
if ($brgy_filter) {
    $s = $conn->prepare("SELECT r.id,r.title,r.category,r.status,r.barangay,r.city,r.created_at,u.first_name,u.last_name FROM reports r JOIN users u ON u.id=r.user_id WHERE r.is_archived=0 AND r.barangay=? ORDER BY r.created_at DESC LIMIT 40");
    $s->bind_param("s",$brgy_filter);
} else {
    $s = $conn->prepare("SELECT r.id,r.title,r.category,r.status,r.barangay,r.city,r.created_at,u.first_name,u.last_name FROM reports r JOIN users u ON u.id=r.user_id WHERE r.is_archived=0 ORDER BY r.created_at DESC LIMIT 40");
}
$s->execute(); $res=$s->get_result();
while($row=$res->fetch_assoc()) $reports[]=$row;
$s->close();

$status_colors = ['dangerous'=>'#dc2626','caution'=>'#d97706','safe'=>'#16a34a'];
$cat_icons = ['crime'=>'fa-user-slash','accident'=>'fa-car-burst','flooding'=>'fa-water','fire'=>'fa-fire','health'=>'fa-heart-pulse','infrastructure'=>'fa-road-barrier','other'=>'fa-circle-exclamation'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Barangay Portal — SenTri</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--navy:#0a3d62;--navy-dark:#062444;--green:#166534;--green-light:#16a34a;--gold:#f39c12;--text:#111827;--muted:#6b7280;--border:#e5e7eb;--bg:#f1f5f9;--card:#fff;--sidebar-w:250px;}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',sans-serif;}
body{background:var(--bg);display:flex;min-height:100vh;color:var(--text);}
/* ── SIDEBAR ── */
.sidebar{width:var(--sidebar-w);background:linear-gradient(180deg,#052e16 0%,#14532d 50%,#166534 100%);color:#fff;display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;box-shadow:4px 0 20px rgba(0,0,0,0.25);transition:transform 0.3s;}
.sidebar.closed{transform:translateX(calc(-1 * var(--sidebar-w)));}
.sb-header{padding:18px 16px;border-bottom:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:space-between;}
.sb-brand{display:flex;align-items:center;gap:10px;}
.sb-seal{width:38px;height:38px;background:rgba(243,156,18,0.2);border:2px solid rgba(243,156,18,0.4);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1rem;color:var(--gold);}
.sb-title{font-size:0.95rem;font-weight:800;}
.sb-sub{font-size:0.58rem;color:rgba(255,255,255,0.5);letter-spacing:1px;text-transform:uppercase;}
.sb-close{background:none;border:none;color:rgba(255,255,255,0.6);font-size:1rem;cursor:pointer;display:none;}
.sb-brgy{padding:12px 16px;background:rgba(0,0,0,0.2);border-bottom:1px solid rgba(255,255,255,0.07);}
.sb-brgy p{font-size:0.7rem;color:rgba(255,255,255,0.5);text-transform:uppercase;letter-spacing:1px;margin-bottom:3px;}
.sb-brgy strong{font-size:0.85rem;color:#fff;font-weight:700;}
.sb-menu{padding:14px 10px;flex:1;overflow-y:auto;}
.sb-menu a{display:flex;align-items:center;gap:10px;color:rgba(255,255,255,0.75);text-decoration:none;font-size:0.86rem;font-weight:500;padding:10px 12px;border-radius:10px;transition:all 0.18s;margin-bottom:3px;}
.sb-menu a:hover{background:rgba(255,255,255,0.1);color:#fff;}
.sb-menu a.active{background:rgba(255,255,255,0.15);color:#fff;font-weight:700;}
.sb-menu a i{width:18px;text-align:center;font-size:0.95rem;}
.sb-menu .section-lbl{font-size:0.65rem;color:rgba(255,255,255,0.35);letter-spacing:2px;text-transform:uppercase;padding:12px 12px 4px;font-weight:700;}
.sb-footer{padding:14px 16px;border-top:1px solid rgba(255,255,255,0.1);}
.sb-user{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
.sb-avatar{width:34px;height:34px;border-radius:50%;background:rgba(243,156,18,0.25);display:flex;align-items:center;justify-content:center;font-size:0.85rem;font-weight:800;color:var(--gold);}
.sb-uname{font-size:0.83rem;font-weight:700;color:#fff;}
.sb-upos{font-size:0.68rem;color:rgba(255,255,255,0.5);}
.sb-logout{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,0.6);text-decoration:none;font-size:0.82rem;font-weight:600;padding:8px 10px;border-radius:8px;transition:all 0.18s;}
.sb-logout:hover{background:rgba(255,0,0,0.15);color:#fca5a5;}
/* ── MAIN ── */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-width:0;}
.topbar{background:#fff;border-bottom:3px solid var(--green);padding:0 28px;height:62px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:0 2px 10px rgba(0,0,0,0.07);}
.topbar-left{display:flex;align-items:center;gap:12px;}
.ham-btn{background:none;border:none;font-size:1.1rem;color:var(--muted);cursor:pointer;padding:7px;border-radius:8px;display:none;}
.page-title{font-size:1.05rem;font-weight:800;color:var(--navy);}
.page-sub{font-size:0.75rem;color:var(--muted);font-weight:500;}
.topbar-right{display:flex;align-items:center;gap:12px;}
.badge-role{background:#f0fdf4;color:var(--green);font-size:0.72rem;font-weight:700;padding:5px 11px;border-radius:20px;border:1px solid #bbf7d0;letter-spacing:0.5px;}
.content{padding:24px 28px;flex:1;}
/* ── STATS ── */
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
.stat-card{background:var(--card);border-radius:14px;padding:20px;display:flex;align-items:center;gap:14px;box-shadow:0 2px 10px rgba(0,0,0,0.06);border:1px solid var(--border);}
.stat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;}
.stat-num{font-size:1.7rem;font-weight:800;line-height:1;color:var(--text);}
.stat-lbl{font-size:0.75rem;color:var(--muted);font-weight:600;margin-top:3px;}
/* ── SECTION HEADER ── */
.section-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.section-bar h2{font-size:1rem;font-weight:800;color:var(--text);}
.btn-sm{padding:7px 14px;border-radius:8px;font-size:0.8rem;font-weight:700;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:6px;text-decoration:none;transition:all 0.18s;}
.btn-green{background:var(--green);color:#fff;}
.btn-green:hover{background:var(--green-light);}
.btn-outline{background:#fff;color:var(--green);border:1.5px solid var(--green);}
.btn-outline:hover{background:#f0fdf4;}
/* ── TABLE ── */
.table-card{background:var(--card);border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,0.07);border:1px solid var(--border);overflow:hidden;}
.table-header{padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.table-header h3{font-size:0.9rem;font-weight:700;color:var(--text);}
table{width:100%;border-collapse:collapse;}
thead tr{background:#f8fafc;}
th{padding:11px 16px;text-align:left;font-size:0.73rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid var(--border);}
td{padding:12px 16px;font-size:0.85rem;border-bottom:1px solid #f3f4f6;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#fafafa;}
.status-pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:20px;font-size:0.72rem;font-weight:700;text-transform:capitalize;}
.pill-dangerous{background:#fef2f2;color:#991b1b;}
.pill-caution{background:#fffbeb;color:#92400e;}
.pill-safe{background:#f0fdf4;color:#166534;}
.cat-chip{display:inline-flex;align-items:center;gap:5px;font-size:0.75rem;font-weight:600;color:var(--navy);background:#eff6ff;padding:3px 9px;border-radius:6px;text-transform:capitalize;}
.reporter{font-size:0.8rem;color:var(--muted);}
.action-btns{display:flex;gap:6px;}
.btn-icon{width:30px;height:30px;border:none;border-radius:7px;cursor:pointer;font-size:0.8rem;display:flex;align-items:center;justify-content:center;transition:all 0.15s;}
.btn-view{background:#eff6ff;color:#2563eb;}
.btn-view:hover{background:#dbeafe;}
.btn-escalate{background:#fef9ec;color:#d97706;}
.btn-escalate:hover{background:#fde68a;}
.empty-state{padding:48px;text-align:center;color:var(--muted);}
.empty-state i{font-size:2.5rem;display:block;margin-bottom:12px;opacity:0.4;}
/* ── OVERLAY ── */
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:99;}
.overlay.show{display:block;}
@media(max-width:900px){
  .sidebar{transform:translateX(calc(-1 * var(--sidebar-w)));}
  .sidebar.open{transform:translateX(0);}
  .sb-close{display:flex;}
  .main{margin-left:0;}
  .ham-btn{display:flex;}
  .stat-grid{grid-template-columns:repeat(2,1fr);}
}
@media(max-width:600px){.stat-grid{grid-template-columns:1fr 1fr;}.content{padding:16px;}.topbar{padding:0 16px;}}
</style>
</head>
<body>
<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
  <div class="sb-header">
    <div class="sb-brand">
      <div class="sb-seal"><i class="fas fa-house-flag"></i></div>
      <div><div class="sb-title">SenTri</div><div class="sb-sub">Barangay Portal</div></div>
    </div>
    <button class="sb-close" onclick="closeSidebar()"><i class="fas fa-xmark"></i></button>
  </div>
  <div class="sb-brgy">
    <p>Jurisdiction</p>
    <strong><?= htmlspecialchars($brgy) ?><?= $city ? ', '.htmlspecialchars($city) : '' ?></strong>
  </div>
  <nav class="sb-menu">
    <a href="barangay.php" class="active"><i class="fas fa-gauge"></i> Dashboard</a>
    <a href="barangay.php?view=reports"><i class="fas fa-file-lines"></i> Incident Reports</a>
    <a href="barangay.php?view=map"><i class="fas fa-map-location-dot"></i> Incident Map</a>
    <div class="section-lbl">Management</div>
    <a href="barangay.php?view=contacts"><i class="fas fa-address-book"></i> Emergency Contacts</a>
    <a href="barangay.php?view=residents"><i class="fas fa-users"></i> Residents</a>
    <div class="section-lbl">Account</div>
    <a href="barangay.php?view=profile"><i class="fas fa-id-card"></i> My Profile</a>
  </nav>
  <div class="sb-footer">
    <div class="sb-user">
      <div class="sb-avatar"><?= strtoupper(substr($fname,0,1)) ?></div>
      <div><div class="sb-uname"><?= htmlspecialchars($fname) ?></div><div class="sb-upos"><?= htmlspecialchars($pos) ?></div></div>
    </div>
    <a href="../logout.php" class="sb-logout"><i class="fas fa-right-from-bracket"></i> Sign Out</a>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <div class="topbar-left">
      <button class="ham-btn" onclick="openSidebar()"><i class="fas fa-bars"></i></button>
      <div>
        <div class="page-title">Barangay Dashboard</div>
        <div class="page-sub"><?= htmlspecialchars($org) ?></div>
      </div>
    </div>
    <div class="topbar-right">
      <span class="badge-role"><i class="fas fa-house-flag"></i> Barangay Official</span>
    </div>
  </div>

  <div class="content">
    <!-- Stats -->
    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background:#f0f7ff;color:#0a3d62;"><i class="fas fa-file-lines"></i></div>
        <div><div class="stat-num"><?= $total ?></div><div class="stat-lbl">Total Reports</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fef2f2;color:#dc2626;"><i class="fas fa-triangle-exclamation"></i></div>
        <div><div class="stat-num"><?= $danger ?></div><div class="stat-lbl">Dangerous</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fffbeb;color:#d97706;"><i class="fas fa-circle-exclamation"></i></div>
        <div><div class="stat-num"><?= $caution ?></div><div class="stat-lbl">Caution</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#f0fdf4;color:#16a34a;"><i class="fas fa-circle-check"></i></div>
        <div><div class="stat-num"><?= $safe ?></div><div class="stat-lbl">Safe / Resolved</div></div>
      </div>
    </div>

    <!-- Reports table -->
    <div class="section-bar">
      <h2>Recent Incident Reports <?= $brgy_filter ? '— '.htmlspecialchars($brgy_filter) : '(All)' ?></h2>
      <div style="display:flex;gap:8px;">
        <a href="../portal/barangay.php" class="btn-sm btn-outline"><i class="fas fa-rotate"></i> Refresh</a>
      </div>
    </div>

    <div class="table-card">
      <div class="table-header">
        <h3><i class="fas fa-list" style="margin-right:6px;color:var(--green);"></i> Incident Log</h3>
        <span style="font-size:0.75rem;color:var(--muted);"><?= count($reports) ?> records</span>
      </div>
      <?php if (empty($reports)): ?>
        <div class="empty-state"><i class="fas fa-folder-open"></i>No reports found for this barangay.</div>
      <?php else: ?>
      <div style="overflow-x:auto;">
        <table>
          <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Status</th><th>Barangay</th><th>Reported By</th><th>Date</th><th>Actions</th></tr></thead>
          <tbody>
          <?php foreach($reports as $r): ?>
            <tr>
              <td style="color:var(--muted);font-size:0.78rem;">#<?= $r['id'] ?></td>
              <td style="font-weight:600;max-width:200px;"><?= htmlspecialchars(mb_strimwidth($r['title'],0,60,'…')) ?></td>
              <td><span class="cat-chip"><i class="fas <?= $cat_icons[$r['category']] ?? 'fa-circle-exclamation' ?>"></i> <?= ucfirst($r['category']) ?></span></td>
              <td><span class="status-pill pill-<?= $r['status'] ?>"><i class="fas fa-circle" style="font-size:0.5rem;"></i> <?= ucfirst($r['status']) ?></span></td>
              <td style="font-size:0.8rem;"><?= htmlspecialchars($r['barangay'] ?? $r['city']) ?></td>
              <td class="reporter"><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></td>
              <td style="font-size:0.78rem;color:var(--muted);white-space:nowrap;"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
              <td>
                <div class="action-btns">
                  <button class="btn-icon btn-view" title="View"><i class="fas fa-eye"></i></button>
                  <button class="btn-icon btn-escalate" title="Escalate to LGU"><i class="fas fa-arrow-up-from-bracket"></i></button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('overlay').classList.add('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('show');}
</script>
</body>
</html>
