<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_role(['first_responder']);
require_once __DIR__ . '/../config/db.php';

$uid   = (int)$_SESSION['user_id'];
$fname = $_SESSION['first_name'];

$stmt = $conn->prepare("SELECT org_name,position,responder_type,barangay_name,municipality FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i",$uid); $stmt->execute();
$prof = $stmt->get_result()->fetch_assoc(); $stmt->close();
$unit = $prof['org_name']      ?? 'Responder Unit';
$pos  = $prof['position']      ?? 'First Responder';
$type = strtoupper($prof['responder_type'] ?? 'UNIT');
$area = $prof['municipality']  ?? $prof['barangay_name'] ?? '';

// Active dangerous/caution reports (dispatch queue)
$active = [];
$s = $conn->prepare("SELECT r.id,r.title,r.category,r.status,r.barangay,r.city,r.latitude,r.longitude,r.created_at,r.assigned_to,u.first_name,u.last_name FROM reports r JOIN users u ON u.id=r.user_id WHERE r.is_archived=0 AND r.status IN('dangerous','caution') ORDER BY FIELD(r.status,'dangerous','caution'), r.created_at DESC LIMIT 50");
$s->execute(); $res=$s->get_result();
while($row=$res->fetch_assoc()) $active[]=$row;
$s->close();

// My assigned reports
$assigned = [];
$s = $conn->prepare("SELECT r.id,r.title,r.category,r.status,r.barangay,r.city,r.created_at FROM reports r WHERE r.assigned_to=? AND r.is_archived=0 ORDER BY r.created_at DESC");
$s->bind_param("i",$uid); $s->execute(); $res=$s->get_result();
while($row=$res->fetch_assoc()) $assigned[]=$row;
$s->close();

function cq($conn,$sql,$t='',$p=[]){$s=$conn->prepare($sql);if($t)$s->bind_param($t,...$p);$s->execute();$s->bind_result($n);$s->fetch();$s->close();return(int)$n;}
$danger_count = cq($conn,"SELECT COUNT(*) FROM reports WHERE status='dangerous' AND is_archived=0");
$my_count = count($assigned);

$cat_icons=['crime'=>'fa-user-slash','accident'=>'fa-car-burst','flooding'=>'fa-water','fire'=>'fa-fire','health'=>'fa-heart-pulse','infrastructure'=>'fa-road-barrier','other'=>'fa-circle-exclamation'];
$type_colors=['bfp'=>'#dc2626','pnp'=>'#1d4ed8','ems'=>'#15803d','drrmo'=>'#d97706','mdrrmo'=>'#d97706','hospital'=>'#0e7490','other'=>'#6b7280'];
$unit_color = $type_colors[strtolower($prof['responder_type'] ?? '')] ?? '#dc2626';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Responder Portal — SenTri</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--red:#b91c1c;--red-dark:#7f1d1d;--red-light:#dc2626;--gold:#f39c12;--text:#111827;--muted:#6b7280;--border:#e5e7eb;--bg:#fafafa;--card:#fff;--sidebar-w:250px;}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Inter',sans-serif;}
body{background:var(--bg);display:flex;min-height:100vh;color:var(--text);}
.sidebar{width:var(--sidebar-w);background:linear-gradient(180deg,#450a0a 0%,#7f1d1d 50%,#991b1b 100%);color:#fff;display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;box-shadow:4px 0 20px rgba(0,0,0,0.3);transition:transform 0.3s;}
.sb-header{padding:18px 16px;border-bottom:1px solid rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:space-between;}
.sb-brand{display:flex;align-items:center;gap:10px;}
.sb-seal{width:40px;height:40px;background:rgba(239,68,68,0.2);border:2px solid rgba(239,68,68,0.5);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fca5a5;}
.sb-title{font-size:0.95rem;font-weight:800;}
.sb-sub{font-size:0.58rem;color:rgba(255,255,255,0.45);letter-spacing:1.5px;text-transform:uppercase;}
.sb-close{background:none;border:none;color:rgba(255,255,255,0.6);font-size:1rem;cursor:pointer;display:none;}
.sb-unit{padding:12px 16px;background:rgba(0,0,0,0.25);border-bottom:1px solid rgba(255,255,255,0.08);}
.sb-unit p{font-size:0.65rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:1px;margin-bottom:2px;}
.sb-unit strong{font-size:0.83rem;color:#fff;}
.unit-badge{display:inline-block;padding:2px 8px;border-radius:4px;font-size:0.68rem;font-weight:800;letter-spacing:1px;margin-top:4px;}
.sb-menu{padding:14px 10px;flex:1;overflow-y:auto;}
.sb-menu a{display:flex;align-items:center;gap:10px;color:rgba(255,255,255,0.72);text-decoration:none;font-size:0.84rem;font-weight:500;padding:10px 12px;border-radius:10px;transition:all 0.18s;margin-bottom:3px;}
.sb-menu a:hover,.sb-menu a.active{background:rgba(255,255,255,0.12);color:#fff;}
.sb-menu a.active{font-weight:700;}
.sb-menu a i{width:18px;text-align:center;}
.section-lbl{font-size:0.63rem;color:rgba(255,255,255,0.28);letter-spacing:2px;text-transform:uppercase;padding:12px 12px 4px;font-weight:700;}
.sb-footer{padding:14px 16px;border-top:1px solid rgba(255,255,255,0.1);}
.sb-user{display:flex;align-items:center;gap:10px;margin-bottom:10px;}
.sb-avatar{width:34px;height:34px;border-radius:50%;background:rgba(239,68,68,0.25);display:flex;align-items:center;justify-content:center;font-size:0.88rem;font-weight:800;color:#fca5a5;}
.sb-uname{font-size:0.82rem;font-weight:700;color:#fff;}
.sb-upos{font-size:0.67rem;color:rgba(255,255,255,0.45);}
.sb-logout{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,0.55);text-decoration:none;font-size:0.8rem;font-weight:600;padding:8px 10px;border-radius:8px;transition:all 0.18s;}
.sb-logout:hover{background:rgba(220,38,38,0.2);color:#fca5a5;}
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;}
.topbar{background:#fff;border-bottom:4px solid var(--red-light);padding:0 28px;height:62px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;box-shadow:0 2px 10px rgba(0,0,0,0.08);}
.topbar-left{display:flex;align-items:center;gap:12px;}
.ham-btn{background:none;border:none;font-size:1.1rem;color:var(--muted);cursor:pointer;display:none;padding:7px;border-radius:8px;}
.page-title{font-size:1.05rem;font-weight:800;color:#7f1d1d;}
.page-sub{font-size:0.73rem;color:var(--muted);}
.badge-resp{font-size:0.71rem;font-weight:700;padding:5px 12px;border-radius:20px;border:1px solid;}
.alert-banner{background:#fef2f2;border:1px solid #fecaca;border-left:4px solid #dc2626;padding:12px 20px;display:flex;align-items:center;gap:12px;font-size:0.85rem;font-weight:600;color:#991b1b;}
.alert-banner i{font-size:1.1rem;color:#dc2626;flex-shrink:0;}
.content{padding:22px 28px;flex:1;}
.stat-row{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:22px;}
.stat-card{background:var(--card);border-radius:14px;padding:18px;box-shadow:0 2px 8px rgba(0,0,0,0.06);border:1px solid var(--border);display:flex;align-items:center;gap:14px;}
.stat-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;}
.stat-num{font-size:1.7rem;font-weight:800;line-height:1;}
.stat-lbl{font-size:0.72rem;color:var(--muted);font-weight:600;margin-top:2px;}
.card{background:var(--card);border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,0.07);border:1px solid var(--border);overflow:hidden;margin-bottom:20px;}
.card-header{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.card-header h3{font-size:0.88rem;font-weight:700;}
.incident-list{display:flex;flex-direction:column;}
.incident-row{display:flex;align-items:flex-start;gap:14px;padding:14px 18px;border-bottom:1px solid #f3f4f6;transition:background 0.15s;}
.incident-row:last-child{border-bottom:none;}
.incident-row:hover{background:#fafafa;}
.incident-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;}
.incident-body{flex:1;min-width:0;}
.incident-title{font-size:0.88rem;font-weight:700;color:var(--text);margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.incident-meta{font-size:0.75rem;color:var(--muted);display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.incident-actions{display:flex;flex-direction:column;gap:6px;align-items:flex-end;}
.pill{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:0.7rem;font-weight:700;}
.pill-dangerous{background:#fef2f2;color:#991b1b;}
.pill-caution{background:#fffbeb;color:#92400e;}
.pill-safe{background:#f0fdf4;color:#166534;}
.btn-dispatch{background:#dc2626;color:#fff;border:none;padding:6px 12px;border-radius:7px;font-size:0.75rem;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:background 0.18s;white-space:nowrap;}
.btn-dispatch:hover{background:#b91c1c;}
.btn-assigned{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;padding:5px 11px;border-radius:7px;font-size:0.74rem;font-weight:700;cursor:default;}
.empty{padding:36px;text-align:center;color:var(--muted);font-size:0.85rem;}
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:99;}
.overlay.show{display:block;}
@media(max-width:860px){.sidebar{transform:translateX(calc(-1 * var(--sidebar-w)));}.sidebar.open{transform:translateX(0);}.sb-close{display:flex;}.main{margin-left:0;}.ham-btn{display:flex;}.stat-row{grid-template-columns:1fr 1fr;}}
@media(max-width:500px){.stat-row{grid-template-columns:1fr;}.content{padding:14px;}.topbar{padding:0 14px;}}
</style>
</head>
<body>
<div class="overlay" id="overlay" onclick="closeSidebar()"></div>
<aside class="sidebar" id="sidebar">
  <div class="sb-header">
    <div class="sb-brand">
      <div class="sb-seal"><i class="fas fa-truck-medical"></i></div>
      <div><div class="sb-title">SenTri</div><div class="sb-sub">Responder Portal</div></div>
    </div>
    <button class="sb-close" onclick="closeSidebar()"><i class="fas fa-xmark"></i></button>
  </div>
  <div class="sb-unit">
    <p>Unit</p>
    <strong><?= htmlspecialchars($unit) ?></strong>
    <div><span class="unit-badge" style="background:<?= $unit_color ?>;color:#fff;"><?= htmlspecialchars($type) ?></span></div>
  </div>
  <nav class="sb-menu">
    <a href="responder.php" class="active"><i class="fas fa-siren-on"></i> Dispatch Queue</a>
    <a href="responder.php?view=assigned"><i class="fas fa-clipboard-check"></i> My Assignments <span id="myCount" style="background:rgba(255,255,255,0.15);padding:1px 7px;border-radius:10px;font-size:0.72rem;margin-left:auto;"><?= $my_count ?></span></a>
    <a href="responder.php?view=map"><i class="fas fa-map-location-dot"></i> Incident Map</a>
    <div class="section-lbl">Reference</div>
    <a href="responder.php?view=contacts"><i class="fas fa-address-book"></i> Emergency Contacts</a>
    <div class="section-lbl">Account</div>
    <a href="responder.php?view=profile"><i class="fas fa-id-card"></i> My Profile</a>
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
        <div class="page-title">Dispatch Queue</div>
        <div class="page-sub"><?= htmlspecialchars($unit) ?><?= $area ? ' &mdash; '.htmlspecialchars($area) : '' ?></div>
      </div>
    </div>
    <span class="badge-resp" style="color:<?= $unit_color ?>;border-color:<?= $unit_color ?>;background:<?= $unit_color ?>18;"><i class="fas fa-truck-medical"></i> <?= htmlspecialchars($type) ?></span>
  </div>

  <?php if($danger_count > 0): ?>
  <div class="alert-banner">
    <i class="fas fa-siren"></i>
    <span><?= $danger_count ?> ACTIVE DANGEROUS INCIDENT<?= $danger_count > 1 ? 'S' : '' ?> — Immediate response required.</span>
  </div>
  <?php endif; ?>

  <div class="content">
    <div class="stat-row">
      <div class="stat-card">
        <div class="stat-icon" style="background:#fef2f2;color:#dc2626;"><i class="fas fa-triangle-exclamation"></i></div>
        <div><div class="stat-num"><?= $danger_count ?></div><div class="stat-lbl">Dangerous</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fffbeb;color:#d97706;"><i class="fas fa-circle-exclamation"></i></div>
        <div><div class="stat-num"><?php $caution_count=0; foreach($active as $_r){ if($_r['status']==='caution') $caution_count++; } echo $caution_count; ?></div><div class="stat-lbl">Caution</div></div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#f0fdf4;color:#166534;"><i class="fas fa-clipboard-check"></i></div>
        <div><div class="stat-num"><?= $my_count ?></div><div class="stat-lbl">My Assignments</div></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3><i class="fas fa-siren-on" style="color:#dc2626;margin-right:6px;"></i> Active Incidents — Dispatch Queue</h3>
        <span style="font-size:0.73rem;color:var(--muted);"><?= count($active) ?> incidents</span>
      </div>
      <div class="incident-list">
        <?php if(empty($active)): ?>
          <div class="empty"><i class="fas fa-shield-check" style="font-size:2rem;display:block;margin-bottom:8px;color:#16a34a;opacity:0.5;"></i>No active incidents in the queue.</div>
        <?php else: foreach($active as $r):
          $is_mine = (int)$r['assigned_to'] === $uid;
          $is_assigned = $r['assigned_to'] !== null;
          $icon_bg = $r['status']==='dangerous' ? '#fef2f2' : '#fffbeb';
          $icon_color = $r['status']==='dangerous' ? '#dc2626' : '#d97706';
        ?>
          <div class="incident-row">
            <div class="incident-icon" style="background:<?= $icon_bg ?>;color:<?= $icon_color ?>;"><i class="fas <?= $cat_icons[$r['category']] ?? 'fa-circle-exclamation' ?>"></i></div>
            <div class="incident-body">
              <div class="incident-title"><?= htmlspecialchars($r['title']) ?></div>
              <div class="incident-meta">
                <span class="pill pill-<?= $r['status'] ?>"><i class="fas fa-circle" style="font-size:0.45rem;"></i> <?= ucfirst($r['status']) ?></span>
                <span><i class="fas fa-location-dot" style="margin-right:3px;color:var(--muted);"></i><?= htmlspecialchars($r['barangay'] ?? $r['city']) ?></span>
                <span style="color:var(--muted);"><?= date('M j, g:ia', strtotime($r['created_at'])) ?></span>
                <?php if($r['latitude']): ?><span><i class="fas fa-map-pin" style="color:#2563eb;margin-right:3px;"></i><a href="https://maps.google.com/?q=<?= $r['latitude'] ?>,<?= $r['longitude'] ?>" target="_blank" style="color:#2563eb;font-size:0.73rem;font-weight:600;text-decoration:none;">View Map</a></span><?php endif; ?>
              </div>
            </div>
            <div class="incident-actions">
              <?php if($is_mine): ?>
                <span class="btn-assigned"><i class="fas fa-check"></i> Assigned to Me</span>
              <?php elseif($is_assigned): ?>
                <span style="font-size:0.72rem;color:var(--muted);">Assigned</span>
              <?php else: ?>
                <button class="btn-dispatch" onclick="assign(<?= $r['id'] ?>,this)"><i class="fas fa-hand-pointer"></i> Assign to Me</button>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
function openSidebar(){document.getElementById('sidebar').classList.add('open');document.getElementById('overlay').classList.add('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('open');document.getElementById('overlay').classList.remove('show');}
async function assign(reportId, btn) {
  btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i>';
  try {
    const fd=new FormData(); fd.append('action','assign_report'); fd.append('report_id',reportId);
    const res=await fetch('../api/reports.php',{method:'POST',body:fd});
    const data=await res.json();
    if(data.status==='success'){
      btn.className='btn-assigned'; btn.innerHTML='<i class="fas fa-check"></i> Assigned to Me'; btn.disabled=false;
    } else { btn.disabled=false; btn.innerHTML='<i class="fas fa-hand-pointer"></i> Assign to Me'; alert(data.message||'Error'); }
  } catch { btn.disabled=false; btn.innerHTML='<i class="fas fa-hand-pointer"></i> Assign to Me'; }
}
// Auto-refresh dispatch queue every 60s
setInterval(()=>location.reload(), 60000);
</script>
</body>
</html>
