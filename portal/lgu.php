<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_role(['lgu']);
require_once __DIR__ . '/../config/db.php';

$uid   = (int)$_SESSION['user_id'];
$fname = $_SESSION['first_name'];
$view  = $_GET['view'] ?? 'overview';

$stmt = $conn->prepare("SELECT email,org_name,`position`,barangay_name,municipality FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i",$uid); $stmt->execute();
$prof = $stmt->get_result()->fetch_assoc(); $stmt->close();
$city = $prof['municipality'] ?? $prof['barangay_name'] ?? '';
$org  = $prof['org_name']     ?? 'LGU Office';
$pos  = $prof['position']     ?? 'LGU Official';

function cq($conn,$sql,$types='',$params=[]){
    $s=$conn->prepare($sql);
    if($types && count($params)){
        $refs=[];
        foreach($params as &$v) $refs[]=&$v;
        array_unshift($refs,$types);
        call_user_func_array([$s,'bind_param'],$refs);
    }
    $s->execute(); $s->bind_result($n); $s->fetch(); $s->close();
    return (int)$n;
}

$total    = cq($conn,"SELECT COUNT(*) FROM reports WHERE is_archived=0");
$danger   = cq($conn,"SELECT COUNT(*) FROM reports WHERE status='dangerous' AND is_archived=0");
$caution  = cq($conn,"SELECT COUNT(*) FROM reports WHERE status='caution' AND is_archived=0");
$safe     = cq($conn,"SELECT COUNT(*) FROM reports WHERE status='safe' AND is_archived=0");
$contacts_count = cq($conn,"SELECT COUNT(*) FROM emergency_contacts WHERE is_active=1");

$cat_icons = ['crime'=>'fa-user-slash','accident'=>'fa-car-burst','flooding'=>'fa-water','fire'=>'fa-fire','health'=>'fa-heart-pulse','infrastructure'=>'fa-road-barrier','other'=>'fa-circle-exclamation'];

// ── Per-view data ──────────────────────────────────────────────────────────
$danger_reports = $all_reports = $brgy_stats = $contacts = [];

if ($view === 'overview' || $view === 'all_reports') {
    $limit = ($view === 'overview') ? 15 : 100;
    $status_filter = ($view === 'overview') ? "AND r.status='dangerous'" : "";
    $s = $conn->prepare("SELECT r.id,r.title,r.category,r.status,r.barangay,r.city,r.created_at,u.first_name,u.last_name FROM reports r JOIN users u ON u.id=r.user_id WHERE r.is_archived=0 $status_filter ORDER BY r.created_at DESC LIMIT $limit");
    $s->execute(); $res=$s->get_result();
    while($row=$res->fetch_assoc()) {
        if($view==='overview') $danger_reports[]=$row; else $all_reports[]=$row;
    }
    $s->close();
}

if ($view === 'overview' || $view === 'barangays') {
    $bs = $conn->query("SELECT COALESCE(barangay,'Unspecified') as b, COUNT(*) as c, SUM(status='dangerous') as d FROM reports WHERE is_archived=0 GROUP BY b ORDER BY c DESC LIMIT 15");
    if ($bs) while($r=$bs->fetch_assoc()) $brgy_stats[]=$r;
}

if ($view === 'contacts') {
    $cs = $conn->query("SELECT id,name,type,contact_number,contact_email,barangay,city,is_active FROM emergency_contacts ORDER BY type,name");
    if ($cs) while($r=$cs->fetch_assoc()) $contacts[]=$r;
}

$nav_items = [
    'overview'    => ['icon'=>'fa-gauge',            'label'=>'Overview'],
    'all_reports' => ['icon'=>'fa-file-lines',        'label'=>'All Incident Reports'],
    'map'         => ['icon'=>'fa-map-location-dot',  'label'=>'Incident Map'],
    'contacts'    => ['icon'=>'fa-address-book',      'label'=>'Emergency Contacts'],
    'barangays'   => ['icon'=>'fa-house-flag',        'label'=>'Barangay Summaries'],
    'responders'  => ['icon'=>'fa-truck-medical',     'label'=>'Responder Units'],
    'profile'     => ['icon'=>'fa-id-card',           'label'=>'My Profile'],
];
$page_titles = [
    'overview'    => 'LGU Operations Dashboard',
    'all_reports' => 'All Incident Reports',
    'map'         => 'Incident Map',
    'contacts'    => 'Emergency Contacts',
    'barangays'   => 'Barangay Summaries',
    'responders'  => 'Responder Units',
    'profile'     => 'My Profile',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?= $page_titles[$view] ?? 'LGU Portal' ?> — SenTri</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
  --navy:#0a3d62;--navy-dark:#062444;--navy-light:#1a5276;
  --gold:#f39c12;--text:#111827;--muted:#6b7280;
  --border:#e5e7eb;--bg:#f1f5f9;--card:#fff;--sidebar-w:256px;
}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',sans-serif;}
html,body{height:100%;}
body{background:var(--bg);color:var(--text);display:flex;min-height:100vh;overflow-x:hidden;}

/* ── SIDEBAR ── */
.sidebar{
  width:var(--sidebar-w);flex-shrink:0;
  background:linear-gradient(180deg,var(--navy-dark) 0%,var(--navy) 55%,var(--navy-light) 100%);
  color:#fff;display:flex;flex-direction:column;
  position:fixed;top:0;left:0;bottom:0;z-index:200;
  box-shadow:4px 0 20px rgba(0,0,0,0.25);
  transition:transform 0.3s cubic-bezier(0.4,0,0.2,1);
}
.sb-header{
  padding:18px 16px;border-bottom:1px solid rgba(255,255,255,0.1);
  display:flex;align-items:center;justify-content:space-between;flex-shrink:0;
}
.sb-brand{display:flex;align-items:center;gap:10px;}
.sb-seal{
  width:40px;height:40px;border-radius:50%;
  background:rgba(243,156,18,0.2);border:2px solid rgba(243,156,18,0.5);
  display:flex;align-items:center;justify-content:center;
  font-size:1.1rem;color:var(--gold);flex-shrink:0;
}
.sb-title{font-size:0.96rem;font-weight:800;line-height:1.2;}
.sb-sub{font-size:0.58rem;color:rgba(255,255,255,0.45);letter-spacing:1.5px;text-transform:uppercase;}
.sb-close{
  background:none;border:none;color:rgba(255,255,255,0.6);
  font-size:1.1rem;cursor:pointer;padding:4px 6px;border-radius:6px;
  display:none;flex-shrink:0;
}
.sb-office{
  padding:10px 16px;background:rgba(0,0,0,0.2);
  border-bottom:1px solid rgba(255,255,255,0.07);flex-shrink:0;
}
.sb-office p{font-size:0.65rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;}
.sb-office strong{font-size:0.82rem;color:#fff;font-weight:700;display:block;word-break:break-word;}
.sb-nav{padding:12px 8px;flex:1;overflow-y:auto;}
.sb-nav a{
  display:flex;align-items:center;gap:10px;
  color:rgba(255,255,255,0.7);text-decoration:none;
  font-size:0.84rem;font-weight:500;
  padding:10px 12px;border-radius:10px;
  transition:all 0.18s;margin-bottom:2px;
  white-space:nowrap;overflow:hidden;
}
.sb-nav a:hover{background:rgba(255,255,255,0.1);color:#fff;}
.sb-nav a.active{background:rgba(255,255,255,0.15);color:#fff;font-weight:700;}
.sb-nav a i{width:18px;text-align:center;font-size:0.92rem;flex-shrink:0;}
.sb-nav a span{overflow:hidden;text-overflow:ellipsis;}
.sb-section{
  font-size:0.62rem;color:rgba(255,255,255,0.28);
  letter-spacing:2px;text-transform:uppercase;
  padding:14px 12px 5px;font-weight:700;
}
.sb-footer{
  padding:12px 16px;border-top:1px solid rgba(255,255,255,0.1);flex-shrink:0;
}
.sb-user{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
.sb-avatar{
  width:34px;height:34px;border-radius:50%;flex-shrink:0;
  background:rgba(243,156,18,0.25);
  display:flex;align-items:center;justify-content:center;
  font-size:0.88rem;font-weight:800;color:var(--gold);
}
.sb-uname{font-size:0.82rem;font-weight:700;color:#fff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.sb-upos{font-size:0.65rem;color:rgba(255,255,255,0.4);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.sb-logout{
  display:flex;align-items:center;gap:8px;
  color:rgba(255,255,255,0.55);text-decoration:none;
  font-size:0.8rem;font-weight:600;padding:8px 10px;
  border-radius:8px;transition:all 0.18s;
}
.sb-logout:hover{background:rgba(220,38,38,0.2);color:#fca5a5;}

/* ── MAIN ── */
.main{
  margin-left:var(--sidebar-w);flex:1;
  display:flex;flex-direction:column;min-width:0;
  min-height:100vh;
}

/* ── TOPBAR ── */
.topbar{
  background:#fff;
  border-bottom:4px solid var(--navy);
  padding:0 24px;height:64px;
  display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:100;
  box-shadow:0 2px 12px rgba(0,0,0,0.08);
  position:relative; /* needed for gov-strip */
}
.topbar{position:sticky;top:0;} /* override to sticky */
.gov-strip{
  position:absolute;top:0;left:0;right:0;height:4px;
  background:linear-gradient(90deg,var(--navy-dark) 0%,var(--navy) 50%,var(--gold) 100%);
}
.topbar-left{display:flex;align-items:center;gap:12px;min-width:0;}
.ham-btn{
  background:none;border:none;font-size:1.15rem;
  color:var(--muted);cursor:pointer;
  padding:7px;border-radius:8px;
  display:none;flex-shrink:0;
}
.ham-btn:hover{background:#f3f4f6;}
.page-title{font-size:1rem;font-weight:800;color:var(--navy);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.page-sub{font-size:0.72rem;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.badge-lgu{
  background:#f0f7ff;color:var(--navy);
  font-size:0.71rem;font-weight:700;
  padding:5px 12px;border-radius:20px;
  border:1px solid #bfdbfe;white-space:nowrap;flex-shrink:0;
}

/* ── CONTENT ── */
.content{padding:22px 24px;flex:1;}

/* ── STATS ── */
.stat-grid{
  display:grid;
  grid-template-columns:repeat(5,1fr);
  gap:14px;margin-bottom:22px;
}
.stat-card{
  background:var(--card);border-radius:14px;
  padding:16px;
  box-shadow:0 2px 8px rgba(0,0,0,0.05);
  border:1px solid var(--border);
}
.stat-icon{
  width:40px;height:40px;border-radius:10px;
  display:flex;align-items:center;justify-content:center;
  font-size:1.1rem;margin-bottom:10px;
}
.stat-num{font-size:1.55rem;font-weight:800;line-height:1;color:var(--text);}
.stat-lbl{font-size:0.71rem;color:var(--muted);font-weight:600;margin-top:3px;}

/* ── TWO COL ── */
.two-col{display:grid;grid-template-columns:3fr 2fr;gap:18px;}

/* ── CARDS ── */
.card{
  background:var(--card);border-radius:14px;
  box-shadow:0 2px 10px rgba(0,0,0,0.06);
  border:1px solid var(--border);overflow:hidden;
}
.card-header{
  padding:14px 18px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
  gap:10px;
}
.card-header h3{font-size:0.88rem;font-weight:700;min-width:0;}
.card-meta{font-size:0.72rem;color:var(--muted);white-space:nowrap;}

/* ── TABLE ── */
.table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;}
table{width:100%;border-collapse:collapse;min-width:500px;}
thead tr{background:#f8fafc;}
th{
  padding:10px 14px;font-size:0.68rem;font-weight:700;
  color:var(--muted);text-transform:uppercase;letter-spacing:0.8px;
  border-bottom:1px solid var(--border);text-align:left;white-space:nowrap;
}
td{
  padding:11px 14px;font-size:0.82rem;
  border-bottom:1px solid #f3f4f6;vertical-align:middle;
}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#fafafa;}
.pill{
  display:inline-flex;align-items:center;gap:4px;
  padding:3px 9px;border-radius:20px;font-size:0.7rem;font-weight:700;
  white-space:nowrap;
}
.pill-dangerous{background:#fef2f2;color:#991b1b;}
.pill-caution{background:#fffbeb;color:#92400e;}
.pill-safe{background:#f0fdf4;color:#166534;}
.cat-chip{
  display:inline-flex;align-items:center;gap:5px;
  font-size:0.72rem;font-weight:600;color:var(--navy);
  background:#eff6ff;padding:3px 8px;border-radius:6px;white-space:nowrap;
}

/* ── BAR CHART ── */
.bar-row{
  display:flex;align-items:center;gap:10px;
  padding:9px 16px;border-bottom:1px solid #f3f4f6;
}
.bar-row:last-child{border-bottom:none;}
.bar-brgy{font-size:0.78rem;font-weight:600;width:120px;flex-shrink:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.bar-wrap{flex:1;height:7px;background:#f1f5f9;border-radius:4px;overflow:hidden;}
.bar-fill{height:100%;border-radius:4px;background:var(--navy);}
.bar-fill.danger{background:#dc2626;}
.bar-count{font-size:0.74rem;font-weight:700;color:var(--muted);width:28px;text-align:right;flex-shrink:0;}

/* ── EMPTY / PLACEHOLDER ── */
.empty{padding:40px 20px;text-align:center;color:var(--muted);}
.empty i{font-size:2rem;display:block;margin-bottom:10px;opacity:0.3;}
.coming-soon{
  padding:60px 24px;text-align:center;
}
.coming-soon i{font-size:3rem;color:var(--navy);opacity:0.15;display:block;margin-bottom:16px;}
.coming-soon h3{font-size:1rem;font-weight:700;color:var(--text);margin-bottom:6px;}
.coming-soon p{font-size:0.85rem;color:var(--muted);}

/* ── CONTACTS LIST ── */
.contact-row{
  display:flex;align-items:flex-start;gap:14px;
  padding:14px 18px;border-bottom:1px solid #f3f4f6;
}
.contact-row:last-child{border-bottom:none;}
.contact-icon{
  width:38px;height:38px;border-radius:10px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:1rem;
}
.contact-name{font-size:0.88rem;font-weight:700;margin-bottom:2px;}
.contact-meta{font-size:0.76rem;color:var(--muted);}
.contact-type{
  display:inline-block;font-size:0.65rem;font-weight:700;
  text-transform:uppercase;letter-spacing:0.8px;
  padding:2px 8px;border-radius:4px;margin-bottom:4px;
}

/* ── OVERLAY ── */
.overlay{
  display:none;position:fixed;inset:0;
  background:rgba(0,0,0,0.45);z-index:150;
}
.overlay.show{display:block;}

/* ── RESPONSIVE ── */
@media(max-width:1100px){
  .stat-grid{grid-template-columns:repeat(3,1fr);}
  .two-col{grid-template-columns:1fr;}
}
@media(max-width:860px){
  :root{--sidebar-w:256px;}
  .sidebar{transform:translateX(-100%);}
  .sidebar.open{transform:translateX(0);}
  .sb-close{display:flex;}
  .main{margin-left:0;}
  .ham-btn{display:flex;}
  .stat-grid{grid-template-columns:repeat(2,1fr);}
  .content{padding:16px;}
  .topbar{padding:0 16px;}
}
@media(max-width:480px){
  .stat-grid{grid-template-columns:1fr 1fr;}
  .badge-lgu{display:none;}
  .page-sub{display:none;}
  .bar-brgy{width:90px;}
}
</style>
</head>
<body>

<div class="overlay" id="overlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
  <div class="sb-header">
    <div class="sb-brand">
      <div class="sb-seal"><i class="fas fa-landmark"></i></div>
      <div>
        <div class="sb-title">SenTri</div>
        <div class="sb-sub">LGU Portal</div>
      </div>
    </div>
    <button class="sb-close" onclick="closeSidebar()"><i class="fas fa-xmark"></i></button>
  </div>

  <div class="sb-office">
    <p>Jurisdiction</p>
    <strong><?= htmlspecialchars($city ?: 'City / Municipality') ?></strong>
  </div>

  <nav class="sb-nav">
    <?php foreach($nav_items as $key => $item): ?>
      <?php if($key === 'contacts'): ?>
        <div class="sb-section">Operations</div>
      <?php elseif($key === 'profile'): ?>
        <div class="sb-section">Account</div>
      <?php endif; ?>
      <a href="lgu.php?view=<?= $key ?>" class="<?= $view===$key ? 'active' : '' ?>">
        <i class="fas <?= $item['icon'] ?>"></i>
        <span><?= $item['label'] ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sb-footer">
    <div class="sb-user">
      <div class="sb-avatar"><?= strtoupper(substr($fname,0,1)) ?></div>
      <div style="min-width:0;">
        <div class="sb-uname"><?= htmlspecialchars($fname) ?></div>
        <div class="sb-upos"><?= htmlspecialchars($pos) ?></div>
      </div>
    </div>
    <a href="../logout.php" class="sb-logout">
      <i class="fas fa-right-from-bracket"></i> Sign Out
    </a>
  </div>
</aside>

<div class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="gov-strip"></div>
    <div class="topbar-left">
      <button class="ham-btn" onclick="openSidebar()"><i class="fas fa-bars"></i></button>
      <div style="min-width:0;">
        <div class="page-title"><?= htmlspecialchars($page_titles[$view] ?? 'LGU Portal') ?></div>
        <div class="page-sub"><?= htmlspecialchars($org) ?><?= $city ? ' &mdash; '.htmlspecialchars($city) : '' ?></div>
      </div>
    </div>
    <span class="badge-lgu"><i class="fas fa-landmark"></i>&nbsp; LGU Official</span>
  </div>

  <div class="content">

    <?php if($view === 'overview'): ?>
    <!-- ── OVERVIEW ── -->
    <div class="stat-grid">
      <div class="stat-card">
        <div class="stat-icon" style="background:#f0f7ff;color:#0a3d62;"><i class="fas fa-file-lines"></i></div>
        <div class="stat-num"><?= $total ?></div>
        <div class="stat-lbl">Total Active Reports</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fef2f2;color:#dc2626;"><i class="fas fa-triangle-exclamation"></i></div>
        <div class="stat-num"><?= $danger ?></div>
        <div class="stat-lbl">Dangerous</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fffbeb;color:#d97706;"><i class="fas fa-circle-exclamation"></i></div>
        <div class="stat-num"><?= $caution ?></div>
        <div class="stat-lbl">Caution</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#f0fdf4;color:#16a34a;"><i class="fas fa-circle-check"></i></div>
        <div class="stat-num"><?= $safe ?></div>
        <div class="stat-lbl">Safe / Resolved</div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="fas fa-address-book"></i></div>
        <div class="stat-num"><?= $contacts_count ?></div>
        <div class="stat-lbl">Active Contacts</div>
      </div>
    </div>

    <div class="two-col">
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-triangle-exclamation" style="color:#dc2626;margin-right:6px;"></i>Active Dangerous Incidents</h3>
          <span class="card-meta"><?= count($danger_reports) ?> records</span>
        </div>
        <?php if(empty($danger_reports)): ?>
          <div class="empty"><i class="fas fa-shield-halved"></i>No dangerous incidents active.</div>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Barangay</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach($danger_reports as $r): ?>
              <tr>
                <td style="color:var(--muted);font-size:0.74rem;">#<?= $r['id'] ?></td>
                <td style="font-weight:600;max-width:180px;"><?= htmlspecialchars(mb_strimwidth($r['title'],0,50,'…')) ?></td>
                <td><span class="cat-chip"><i class="fas <?= $cat_icons[$r['category']] ?? 'fa-circle-exclamation' ?>"></i> <?= ucfirst($r['category']) ?></span></td>
                <td style="font-size:0.78rem;"><?= htmlspecialchars($r['barangay'] ?? $r['city']) ?></td>
                <td style="font-size:0.74rem;color:var(--muted);"><?= date('M j', strtotime($r['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>

      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-house-flag" style="color:#166534;margin-right:6px;"></i>Reports by Barangay</h3>
        </div>
        <?php if(empty($brgy_stats)): ?>
          <div class="empty"><i class="fas fa-chart-bar"></i>No data yet.</div>
        <?php else:
          $max = max(array_column($brgy_stats,'c')) ?: 1;
          foreach($brgy_stats as $b): ?>
          <div class="bar-row">
            <div class="bar-brgy" title="<?= htmlspecialchars($b['b']) ?>"><?= htmlspecialchars($b['b']) ?></div>
            <div class="bar-wrap">
              <div class="bar-fill <?= $b['d']>0 ? 'danger' : '' ?>" style="width:<?= round($b['c']/$max*100) ?>%"></div>
            </div>
            <div class="bar-count"><?= $b['c'] ?></div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <?php elseif($view === 'all_reports'): ?>
    <!-- ── ALL REPORTS ── -->
    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-file-lines" style="color:var(--navy);margin-right:6px;"></i>All Incident Reports</h3>
        <span class="card-meta"><?= count($all_reports) ?> records</span>
      </div>
      <?php if(empty($all_reports)): ?>
        <div class="empty"><i class="fas fa-folder-open"></i>No reports found.</div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>#</th><th>Title</th><th>Category</th><th>Status</th><th>Barangay</th><th>Reported By</th><th>Date</th></tr></thead>
          <tbody>
          <?php foreach($all_reports as $r): ?>
            <tr>
              <td style="color:var(--muted);font-size:0.74rem;">#<?= $r['id'] ?></td>
              <td style="font-weight:600;max-width:200px;"><?= htmlspecialchars(mb_strimwidth($r['title'],0,55,'…')) ?></td>
              <td><span class="cat-chip"><i class="fas <?= $cat_icons[$r['category']] ?? 'fa-circle-exclamation' ?>"></i> <?= ucfirst($r['category']) ?></span></td>
              <td><span class="pill pill-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
              <td style="font-size:0.78rem;"><?= htmlspecialchars($r['barangay'] ?? $r['city']) ?></td>
              <td style="font-size:0.78rem;color:var(--muted);"><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></td>
              <td style="font-size:0.74rem;color:var(--muted);white-space:nowrap;"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <?php elseif($view === 'contacts'): ?>
    <!-- ── CONTACTS ── -->
    <?php
    $type_colors = ['hospital'=>['#ecfdf5','#059669'],'fire'=>['#fef2f2','#dc2626'],'police'=>['#eff6ff','#2563eb'],'barangay'=>['#f0fdf4','#166534'],'municipal'=>['#f0f7ff','#0a3d62'],'other'=>['#f5f3ff','#7c3aed']];
    $type_icons  = ['hospital'=>'fa-hospital','fire'=>'fa-fire-extinguisher','police'=>'fa-shield','barangay'=>'fa-house-flag','municipal'=>'fa-landmark','other'=>'fa-phone'];
    ?>
    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-address-book" style="color:var(--navy);margin-right:6px;"></i>Emergency Contacts</h3>
        <span class="card-meta"><?= count($contacts) ?> contacts</span>
      </div>
      <?php if(empty($contacts)): ?>
        <div class="empty"><i class="fas fa-address-book"></i>No emergency contacts found.</div>
      <?php else: foreach($contacts as $c):
        $tc = $type_colors[$c['type']] ?? $type_colors['other'];
        $ti = $type_icons[$c['type']] ?? 'fa-phone';
      ?>
        <div class="contact-row">
          <div class="contact-icon" style="background:<?= $tc[0] ?>;color:<?= $tc[1] ?>;"><i class="fas <?= $ti ?>"></i></div>
          <div style="flex:1;min-width:0;">
            <div class="contact-name"><?= htmlspecialchars($c['name']) ?></div>
            <div class="contact-meta">
              <?php if($c['contact_number']): ?><span><i class="fas fa-phone" style="margin-right:4px;"></i><?= htmlspecialchars($c['contact_number']) ?></span>&nbsp;&nbsp;<?php endif; ?>
              <?php if($c['contact_email']): ?><span><i class="fas fa-envelope" style="margin-right:4px;"></i><?= htmlspecialchars($c['contact_email']) ?></span>&nbsp;&nbsp;<?php endif; ?>
              <?php if($c['city']): ?><span><i class="fas fa-location-dot" style="margin-right:4px;"></i><?= htmlspecialchars($c['barangay'] ? $c['barangay'].', '.$c['city'] : $c['city']) ?></span><?php endif; ?>
            </div>
          </div>
          <span class="contact-type" style="background:<?= $tc[0] ?>;color:<?= $tc[1] ?>;"><?= strtoupper($c['type']) ?></span>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <?php elseif($view === 'barangays'): ?>
    <!-- ── BARANGAY SUMMARIES ── -->
    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-house-flag" style="color:#166534;margin-right:6px;"></i>Barangay Incident Summary</h3>
      </div>
      <?php if(empty($brgy_stats)): ?>
        <div class="empty"><i class="fas fa-chart-bar"></i>No data yet.</div>
      <?php else:
        $max = max(array_column($brgy_stats,'c')) ?: 1;
        foreach($brgy_stats as $b): ?>
        <div class="bar-row" style="padding:12px 18px;">
          <div class="bar-brgy" style="width:160px;font-size:0.84rem;" title="<?= htmlspecialchars($b['b']) ?>"><?= htmlspecialchars($b['b']) ?></div>
          <div class="bar-wrap"><div class="bar-fill <?= $b['d']>0 ? 'danger' : '' ?>" style="width:<?= round($b['c']/$max*100) ?>%"></div></div>
          <div class="bar-count" style="width:40px;font-size:0.8rem;"><?= $b['c'] ?> reports</div>
          <?php if($b['d']>0): ?>
            <span class="pill pill-dangerous" style="margin-left:8px;"><?= $b['d'] ?> dangerous</span>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>

    <?php else: ?>
    <!-- ── COMING SOON ── -->
    <div class="card">
      <div class="coming-soon">
        <i class="fas <?= $nav_items[$view]['icon'] ?? 'fa-gear' ?>"></i>
        <h3><?= htmlspecialchars($page_titles[$view] ?? ucfirst($view)) ?></h3>
        <p>This section is under development.</p>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /content -->
</div><!-- /main -->

<script>
function openSidebar(){
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('overlay').classList.add('show');
  document.body.style.overflow='hidden';
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('overlay').classList.remove('show');
  document.body.style.overflow='';
}
</script>
</body>
</html>
