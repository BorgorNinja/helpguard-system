<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_role(['lgu']);
require_once __DIR__ . '/../config/db.php';

$uid   = (int)$_SESSION['user_id'];
$fname = $_SESSION['first_name'];

$stmt = $conn->prepare("SELECT email,org_name,position,barangay_name,municipality FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i",$uid); $stmt->execute();
$prof = $stmt->get_result()->fetch_assoc(); $stmt->close();
$city = $prof['municipality'] ?? $prof['barangay_name'] ?? 'City';
$org  = $prof['org_name']     ?? 'LGU Office';
$pos  = $prof['position']     ?? 'LGU Official';

function cq($conn,$sql,$types='',$params=[]){$s=$conn->prepare($sql);if($types)$s->bind_param($types,...$params);$s->execute();$s->bind_result($n);$s->fetch();$s->close();return(int)$n;}
$total    = cq($conn,"SELECT COUNT(*) FROM reports WHERE is_archived=0");
$danger   = cq($conn,"SELECT COUNT(*) FROM reports WHERE status='dangerous' AND is_archived=0");
$caution  = cq($conn,"SELECT COUNT(*) FROM reports WHERE status='caution' AND is_archived=0");
$safe     = cq($conn,"SELECT COUNT(*) FROM reports WHERE status='safe' AND is_archived=0");
$contacts = cq($conn,"SELECT COUNT(*) FROM emergency_contacts WHERE is_active=1");

// Barangay breakdown
$brgy_stats = [];
$bs = $conn->query("SELECT COALESCE(barangay,'Unspecified') as b, COUNT(*) as c, SUM(status='dangerous') as d FROM reports WHERE is_archived=0 GROUP BY b ORDER BY c DESC LIMIT 10");
while($r=$bs->fetch_assoc()) $brgy_stats[]=$r;

// Recent danger reports
$danger_reports = [];
$dr = $conn->prepare("SELECT r.id,r.title,r.category,r.status,r.barangay,r.city,r.created_at,u.first_name,u.last_name FROM reports r JOIN users u ON u.id=r.user_id WHERE r.status='dangerous' AND r.is_archived=0 ORDER BY r.created_at DESC LIMIT 15");
$dr->execute(); $res=$dr->get_result();
while($row=$res->fetch_assoc()) $danger_reports[]=$row;
$dr->close();

$cat_icons=['crime'=>'fa-user-slash','accident'=>'fa-car-burst','flooding'=>'fa-water','fire'=>'fa-fire','health'=>'fa-heart-pulse','infrastructure'=>'fa-road-barrier','other'=>'fa-circle-exclamation'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>LGU Portal — SenTri</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--navy:#0a3d62;--navy-dark:#062444;--navy-light:#1a5276;--gold:#f39c12;--text:#111827;--muted:#6b7280;--border:#e5e7eb;--bg:#f1f5f9;--card:#fff;--sidebar-w:256px;}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',sans-serif;}
body{background:var(--bg);display:flex;min-height:100vh;color:var(--text);}
.sidebar{width:var(--sidebar-w);background:linear-gradient(180deg,var(--navy-dark) 0%,var(--navy) 55%,var(--navy-light) 100%);color:#fff;display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;box-shadow:4px 0 20px rgba(0,0,0,0.25);transition:transform 0.3s;}
.sb-header{padding:18px 16px;border-bottom:1px solid rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:space-between;}
.sb-brand{display:flex;align-items:center;gap:10px;}
.sb-seal{width:40px;height:40px;background:rgba(243,156,18,0.2);border:2px solid rgba(243,156,18,0.5);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:var(--gold);}
.sb-title{font-size:0.96rem;font-weight:800;}
.sb-sub{font-size:0.58rem;color:rgba(255,255,255,0.45);letter-spacing:1.5px;text-transform:uppercase;}
.sb-close{background:none;border:none;color:rgba(255,255,255,0.6);font-size:1rem;cursor:pointer;display:none;}
.sb-office{padding:12px 16px;background:rgba(0,0,0,0.2);border-bottom:1px solid rgba(255,255,255,0.07);}
.sb-office p{font-size:0.67rem;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;}
.sb-office strong{font-size:0.83rem;color:#fff;}
.sb-menu{padding:14px 10px;flex:1;overflow-y:auto;}
.sb-menu a{display:flex;align-items:center;gap:10px;color:rgba(255,255,255,0.72);text-decoration:none;font-size:0.84rem;font-weight:500;padding:10px 12px;border-radius:10px;transition:all 0.18s;margin-bottom:3px;}
.sb-menu a:hover,.sb-menu a.active{background:rgba(255,255,255,0.12);color:#fff;}
.sb-menu a.active{font-weight:700;}
.sb-menu a i{width:18px;text-align:center;}
.section-lbl{font-size:0.63rem;color:rgba(255,255,255,0.3);letter-spacing:2px;text-transform:uppercase;padding:12px 12px 4px;font-weight:700;}
.sb-footer{padding:14px 16px;border-top:1px solid rgba(255,255,255,0.1);}
.sb-user{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
.sb-avatar{width:34px;height:34px;border-radius:50%;background:rgba(243,156,18,0.25);display:flex;align-items:center;justify-content:center;font-size:0.88rem;font-weight:800;color:var(--gold);}
.sb-uname{font-size:0.82rem;font-weight:700;color:#fff;}
.sb-upos{font-size:0.67rem;color:rgba(255,255,255,0.45);}
.sb-logout{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,0.55);text-decoration:none;font-size:0.8rem;font-weight:600;padding:8px 10px;border-radius:8px;transition:all 0.18s;}
.sb-logout:hover{background:rgba(220,38,38,0.2);color:#fca5a5;}
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;}
.topbar{background:#fff;border-bottom:4px solid var(--navy);padding:0 28px;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:0 2px 12px rgba(0,0,0,0.08);}
.gov-strip{width:100%;height:5px;background:linear-gradient(90deg,var(--navy-dark) 0%,var(--navy) 50%,var(--gold) 100%);position:absolute;top:0;left:0;}
.topbar-left{display:flex;align-items:center;gap:12px;}
.ham-btn{background:none;border:none;font-size:1.1rem;color:var(--muted);cursor:pointer;padding:7px;border-radius:8px;display:none;}
.page-title{font-size:1.05rem;font-weight:800;color:var(--navy);}
.page-sub{font-size:0.73rem;color:var(--muted);}
.badge-lgu{background:#f0f7ff;color:var(--navy);font-size:0.71rem;font-weight:700;padding:5px 12px;border-radius:20px;border:1px solid #bfdbfe;}
.content{padding:24px 28px;flex:1;}
.stat-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:14px;margin-bottom:24px;}
.stat-card{background:var(--card);border-radius:14px;padding:18px 16px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid var(--border);}
.stat-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;margin-bottom:10px;}
.stat-num{font-size:1.65rem;font-weight:800;line-height:1;color:var(--text);}
.stat-lbl{font-size:0.72rem;color:var(--muted);font-weight:600;margin-top:3px;}
.two-col{display:grid;grid-template-columns:1.4fr 1fr;gap:18px;}
.card{background:var(--card);border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,0.07);border:1px solid var(--border);overflow:hidden;}
.card-header{padding:15px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.card-header h3{font-size:0.88rem;font-weight:700;}
table{width:100%;border-collapse:collapse;}
thead tr{background:#f8fafc;}
th{padding:10px 16px;font-size:0.7rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.8px;border-bottom:1px solid var(--border);text-align:left;}
td{padding:11px 16px;font-size:0.83rem;border-bottom:1px solid #f3f4f6;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#fafafa;}
.pill{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:0.71rem;font-weight:700;}
.pill-danger{background:#fef2f2;color:#991b1b;}
.pill-caution{background:#fffbeb;color:#92400e;}
.pill-safe{background:#f0fdf4;color:#166534;}
.bar-row{display:flex;align-items:center;gap:10px;padding:10px 16px;border-bottom:1px solid #f3f4f6;}
.bar-row:last-child{border-bottom:none;}
.bar-brgy{font-size:0.8rem;font-weight:600;min-width:130px;color:var(--text);}
.bar-wrap{flex:1;height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;}
.bar-fill{height:100%;border-radius:4px;background:var(--navy);transition:width 0.4s;}
.bar-fill.danger-fill{background:#dc2626;}
.bar-count{font-size:0.75rem;font-weight:700;color:var(--muted);min-width:30px;text-align:right;}
.cat-chip{display:inline-flex;align-items:center;gap:5px;font-size:0.73rem;font-weight:600;color:var(--navy);background:#eff6ff;padding:3px 8px;border-radius:6px;}
.empty{padding:40px;text-align:center;color:var(--muted);font-size:0.85rem;}
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:99;}
.overlay.show{display:block;}
@media(max-width:960px){.two-col{grid-template-columns:1fr;}.stat-grid{grid-template-columns:repeat(3,1fr);}}
@media(max-width:860px){.sidebar{transform:translateX(calc(-1 * var(--sidebar-w)));}.sidebar.open{transform:translateX(0);}.sb-close{display:flex;}.main{margin-left:0;}.ham-btn{display:flex;}.stat-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:500px){.stat-grid{grid-template-columns:1fr 1fr;}.content{padding:14px;}.topbar{padding:0 16px;}}
</style>
</head>
<body>
<div class="overlay" id="overlay" onclick="closeSidebar()"></div>
<aside class="sidebar" id="sidebar">
  <div class="sb-header">
    <div class="sb-brand">
      <div class="sb-seal"><i class="fas fa-landmark"></i></div>
      <div><div class="sb-title">SenTri</div><div class="sb-sub">LGU Portal</div></div>
    </div>
    <button class="sb-close" onclick="closeSidebar()"><i class="fas fa-xmark"></i></button>
  </div>
  <div class="sb-office"><p>Jurisdiction</p><strong><?= htmlspecialchars($city ?: 'City / Municipality') ?></strong></div>
  <nav class="sb-menu">
    <a href="lgu.php" class="active"><i class="fas fa-gauge"></i> Overview</a>
    <a href="lgu.php?view=all_reports"><i class="fas fa-file-lines"></i> All Incident Reports</a>
    <a href="lgu.php?view=map"><i class="fas fa-map-location-dot"></i> Incident Map</a>
    <div class="section-lbl">Operations</div>
    <a href="lgu.php?view=contacts"><i class="fas fa-address-book"></i> Emergency Contacts</a>
    <a href="lgu.php?view=barangays"><i class="fas fa-house-flag"></i> Barangay Summaries</a>
    <a href="lgu.php?view=responders"><i class="fas fa-truck-medical"></i> Responder Units</a>
    <div class="section-lbl">Account</div>
    <a href="lgu.php?view=profile"><i class="fas fa-id-card"></i> My Profile</a>
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
    <div class="gov-strip"></div>
    <div class="topbar-left">
      <button class="ham-btn" onclick="openSidebar()"><i class="fas fa-bars"></i></button>
      <div>
        <div class="page-title">LGU Operations Dashboard</div>
        <div class="page-sub"><?= htmlspecialchars($org) ?> &mdash; <?= htmlspecialchars($city) ?></div>
      </div>
    </div>
    <div>
      <span class="badge-lgu"><i class="fas fa-landmark"></i> LGU Official</span>
    </div>
  </div>

  <div class="content">
    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background:#f0f7ff;color:#0a3d62;"><i class="fas fa-file-lines"></i></div>
        <div class="stat-num"><?= $total ?></div><div class="stat-lbl">Total Active Reports</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fef2f2;color:#dc2626;"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="stat-num"><?= $danger ?></div><div class="stat-lbl">Dangerous</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fffbeb;color:#d97706;"><i class="fas fa-circle-exclamation"></i></div>
        <div class="stat-num"><?= $caution ?></div><div class="stat-lbl">Caution</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#f0fdf4;color:#16a34a;"><i class="fas fa-circle-check"></i></div>
        <div class="stat-num"><?= $safe ?></div><div class="stat-lbl">Safe / Resolved</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="fas fa-address-book"></i></div>
        <div class="stat-num"><?= $contacts ?></div><div class="stat-lbl">Active Contacts</div>
      </div>
    </div>

    <div class="two-col">
      <!-- Danger reports table -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-triangle-exclamation" style="color:#dc2626;margin-right:6px;"></i> Active Dangerous Incidents</h3>
          <span style="font-size:0.73rem;color:var(--muted);"><?= count($danger_reports) ?> records</span>
        </div>
        <?php if(empty($danger_reports)): ?>
          <div class="empty"><i class="fas fa-shield-halved" style="font-size:2rem;display:block;margin-bottom:8px;opacity:0.3;"></i>No dangerous incidents active.</div>
        <?php else: ?>
        <div style="overflow-x:auto;">
          <table>
            <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Barangay</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach($danger_reports as $r): ?>
              <tr>
                <td style="color:var(--muted);font-size:0.75rem;">#<?= $r['id'] ?></td>
                <td style="font-weight:600;max-width:160px;font-size:0.82rem;"><?= htmlspecialchars(mb_strimwidth($r['title'],0,50,'…')) ?></td>
                <td><span class="cat-chip"><i class="fas <?= $cat_icons[$r['category']] ?? 'fa-circle-exclamation' ?>"></i> <?= ucfirst($r['category']) ?></span></td>
                <td style="font-size:0.79rem;"><?= htmlspecialchars($r['barangay'] ?? $r['city']) ?></td>
                <td style="font-size:0.75rem;color:var(--muted);white-space:nowrap;"><?= date('M j', strtotime($r['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <!-- Barangay breakdown -->
      <div class="card">
        <div class="card-header"><h3><i class="fas fa-house-flag" style="color:#166534;margin-right:6px;"></i> Reports by Barangay</h3></div>
        <?php if(empty($brgy_stats)): ?>
          <div class="empty">No data yet.</div>
        <?php else:
          $max = max(array_column($brgy_stats,'c')) ?: 1;
          foreach($brgy_stats as $b): ?>
          <div class="bar-row">
            <div class="bar-brgy"><?= htmlspecialchars($b['b']) ?></div>
            <div class="bar-wrap"><div class="bar-fill <?= $b['d']>0 ? 'danger-fill' : '' ?>" style="width:<?= round($b['c']/$max*100) ?>%"></div></div>
            <div class="bar-count"><?= $b['c'] ?></div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>
<script>
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('overlay').classList.add('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('show');}
</script>
</body>
</html>
