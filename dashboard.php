<?php
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require 'db_connect.php';

$user_id    = (int)$_SESSION['user_id'];
$first_name = $_SESSION['first_name'];
$last_name  = $_SESSION['last_name'];
$role       = $_SESSION['role'];

$total_reports=0;$s=$conn->prepare("SELECT COUNT(*) FROM reports WHERE is_archived=0");$s->execute();$s->bind_result($total_reports);$s->fetch();$s->close();
$danger_count=0;$s=$conn->prepare("SELECT COUNT(*) FROM reports WHERE status='dangerous' AND is_archived=0");$s->execute();$s->bind_result($danger_count);$s->fetch();$s->close();
$safe_count=0;$s=$conn->prepare("SELECT COUNT(*) FROM reports WHERE status='safe' AND is_archived=0");$s->execute();$s->bind_result($safe_count);$s->fetch();$s->close();
$my_count=0;$s=$conn->prepare("SELECT COUNT(*) FROM reports WHERE user_id=? AND is_archived=0");$s->bind_param("i",$user_id);$s->execute();$s->bind_result($my_count);$s->fetch();$s->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard – HelpGuard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
:root{--blue:#1c57b2;--blue-dark:#0e3d8c;--blue-light:#3a8dff;--red:#e53e3e;--green:#38a169;--orange:#dd6b20;--text:#1a1a2e;--muted:#666;--bg:#f0f2f7;--sidebar-w:248px;}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Poppins',sans-serif;}
body{background:var(--bg);display:flex;min-height:100vh;color:var(--text);overflow-x:hidden;}

@keyframes fadeInUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
@keyframes slideInLeft{from{opacity:0;transform:translateX(-20px);}to{opacity:1;transform:translateX(0);}}
@keyframes scaleIn{from{opacity:0;transform:scale(0.97);}to{opacity:1;transform:scale(1);}}
@keyframes cardEntrance{from{opacity:0;transform:translateY(24px);}to{opacity:1;transform:translateY(0);}}
@keyframes spin{to{transform:rotate(360deg);}}
@keyframes pulse-glow{0%,100%{box-shadow:0 0 0 0 rgba(229,62,62,0.4);}50%{box-shadow:0 0 0 8px rgba(229,62,62,0);}}

/* SIDEBAR */
.sidebar{width:var(--sidebar-w);background:linear-gradient(180deg,#1c57b2 0%,#0e3d8c 60%,#091d5c 100%);color:#fff;display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;transition:transform 0.3s cubic-bezier(0.4,0,0.2,1);box-shadow:4px 0 20px rgba(0,0,0,0.2);}
.sidebar.closed{transform:translateX(calc(-1*var(--sidebar-w)));}
.sidebar-header{padding:20px 16px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,0.1);}
.brand-logo{display:flex;align-items:center;gap:10px;}
.brand-icon-s{width:36px;height:36px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;border:1px solid rgba(255,255,255,0.2);}
.brand-name-s{font-size:1.05rem;font-weight:800;}
.toggle-btn{background:none;border:none;color:rgba(255,255,255,0.7);cursor:pointer;font-size:1rem;padding:6px;border-radius:8px;transition:all 0.2s;}
.toggle-btn:hover{background:rgba(255,255,255,0.1);color:#fff;}
.menu{padding:12px 10px;display:flex;flex-direction:column;gap:2px;}
.menu a{display:flex;align-items:center;gap:11px;padding:11px 13px;text-decoration:none;color:rgba(255,255,255,0.75);font-size:0.875rem;font-weight:500;border-radius:10px;transition:all 0.2s;white-space:nowrap;}
.menu a:hover{background:rgba(255,255,255,0.12);color:#fff;}
.menu a.active{background:rgba(255,255,255,0.18);color:#fff;font-weight:600;}
.menu a i{font-size:1rem;width:18px;text-align:center;flex-shrink:0;}
.sidebar-stats{padding:12px 14px;margin:8px 0;}
.stat-label-s{font-size:0.68rem;color:rgba(255,255,255,0.45);text-transform:uppercase;letter-spacing:0.8px;margin-bottom:10px;padding:0 4px;}
.stat-box{background:rgba(255,255,255,0.08);border-radius:10px;padding:11px 14px;margin-bottom:8px;border:1px solid rgba(255,255,255,0.05);transition:background 0.2s;}
.stat-box:hover{background:rgba(255,255,255,0.12);}
.stat-box .num{font-size:1.3rem;font-weight:700;}
.stat-box .lbl{font-size:0.74rem;opacity:0.7;margin-top:2px;}
.stat-box.danger-box{background:rgba(229,62,62,0.18);border-left:3px solid #e53e3e;}
.stat-box.safe-box{background:rgba(56,161,105,0.18);border-left:3px solid #38a169;}
.sidebar-footer{margin-top:auto;padding:16px;}
.sidebar-footer a{display:flex;align-items:center;gap:10px;color:rgba(255,255,255,0.65);text-decoration:none;font-size:0.87rem;padding:10px 13px;border-radius:10px;transition:all 0.2s;}
.sidebar-footer a:hover{background:rgba(255,255,255,0.1);color:#fff;}

.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:99;backdrop-filter:blur(2px);}
.sidebar-overlay.show{display:block;}

/* MAIN */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-width:0;transition:margin-left 0.3s;}
.topbar{background:#fff;padding:14px 28px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 12px rgba(0,0,0,0.07);position:sticky;top:0;z-index:50;animation:fadeIn 0.4s ease;}
.topbar-left{display:flex;align-items:center;gap:12px;}
.ham-btn{background:none;border:none;font-size:1.2rem;color:var(--muted);cursor:pointer;padding:7px;border-radius:9px;transition:all 0.2s;display:none;}
.ham-btn:hover{background:var(--bg);color:var(--text);}
.topbar h1{font-size:1.1rem;font-weight:700;}
.topbar .right-top{display:flex;align-items:center;gap:12px;}
.post-btn{background:linear-gradient(135deg,var(--blue-light),var(--blue));color:#fff;border:none;padding:10px 20px;border-radius:10px;font-size:0.87rem;font-weight:600;cursor:pointer;transition:all 0.25s;display:flex;align-items:center;gap:7px;font-family:'Poppins',sans-serif;white-space:nowrap;}
.post-btn:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(28,87,178,0.4);}
.user-info{display:flex;align-items:center;gap:10px;}
.avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--blue-light),var(--blue));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem;flex-shrink:0;}
.user-name{font-size:0.88rem;font-weight:600;}
.content{padding:28px;flex:1;}

/* STATS */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:28px;}
.stat-card{background:#fff;border-radius:16px;padding:18px 20px;box-shadow:0 2px 12px rgba(0,0,0,0.06);display:flex;align-items:center;gap:14px;animation:fadeInUp 0.5s both;transition:transform 0.2s,box-shadow 0.2s;position:relative;overflow:hidden;}
.stat-card:nth-child(1){animation-delay:0.05s;}.stat-card:nth-child(2){animation-delay:0.1s;}.stat-card:nth-child(3){animation-delay:0.15s;}.stat-card:nth-child(4){animation-delay:0.2s;}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 28px rgba(0,0,0,0.1);}
.stat-icon{width:48px;height:48px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0;}
.stat-icon.blue{background:#ebf2ff;color:var(--blue);}.stat-icon.red{background:#fff0f0;color:var(--red);}.stat-icon.green{background:#f0fff4;color:var(--green);}.stat-icon.orange{background:#fff8f0;color:var(--orange);}
.stat-card strong{display:block;font-size:1.5rem;font-weight:800;color:var(--text);}
.stat-card span{font-size:0.78rem;color:var(--muted);}

/* FILTERS + VIEW TOGGLE */
.filters{background:#fff;border-radius:14px;padding:16px 20px;box-shadow:0 2px 10px rgba(0,0,0,0.06);margin-bottom:22px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;animation:fadeInUp 0.5s 0.25s both;}
.filters input,.filters select{padding:9px 13px;border:1.5px solid #e0e0e0;border-radius:9px;font-size:0.87rem;outline:none;font-family:'Poppins',sans-serif;transition:all 0.2s;background:#fafafa;}
.filters input{flex:1;min-width:160px;}
.filters input:focus,.filters select:focus{border-color:var(--blue-light);background:#fff;}
.filters select{min-width:130px;}
.filters button{padding:9px 18px;border:none;border-radius:9px;font-size:0.87rem;font-weight:600;cursor:pointer;background:var(--bg);color:#444;transition:all 0.2s;font-family:'Poppins',sans-serif;}
.filters button.active,.filters button:hover{background:linear-gradient(135deg,var(--blue-light),var(--blue));color:#fff;}
.view-toggle{display:flex;gap:4px;background:var(--bg);border-radius:10px;padding:4px;margin-left:auto;flex-shrink:0;}
.view-btn{display:flex;align-items:center;gap:6px;padding:7px 15px;border:none;border-radius:7px;font-size:0.83rem;font-weight:600;cursor:pointer;background:transparent;color:var(--muted);font-family:'Poppins',sans-serif;transition:all 0.2s;white-space:nowrap;}
.view-btn.active{background:#fff;color:var(--blue);box-shadow:0 2px 8px rgba(0,0,0,0.1);}

/* FEED */
#feed{display:flex;flex-direction:column;gap:16px;}
.report-card{background:#fff;border-radius:14px;padding:20px 22px;box-shadow:0 2px 10px rgba(0,0,0,0.06);border-left:5px solid #ccc;transition:all 0.25s;animation:cardEntrance 0.45s both;}
.report-card:hover{transform:translateY(-2px);box-shadow:0 8px 28px rgba(0,0,0,0.1);}
.report-card.dangerous{border-left-color:var(--red);}.report-card.dangerous .card-header{background:linear-gradient(90deg,#fff0f0,transparent);}
.report-card.caution{border-left-color:var(--orange);}.report-card.caution .card-header{background:linear-gradient(90deg,#fff8f0,transparent);}
.report-card.safe{border-left-color:var(--green);}.report-card.safe .card-header{background:linear-gradient(90deg,#f0fff4,transparent);}
.card-header{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:10px;padding:4px 0;}
.card-header h3{font-size:1rem;font-weight:700;color:var(--text);line-height:1.4;}
.badge{padding:4px 12px;border-radius:50px;font-size:0.73rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;flex-shrink:0;}
.badge.dangerous{background:#fff0f0;color:var(--red);}.badge.caution{background:#fff8f0;color:var(--orange);}.badge.safe{background:#f0fff4;color:var(--green);}
.card-meta{display:flex;flex-wrap:wrap;gap:12px;font-size:0.79rem;color:#777;margin-bottom:10px;}
.card-meta span{display:flex;align-items:center;gap:5px;}
.card-body p{font-size:0.87rem;color:#444;line-height:1.75;}
.card-footer{margin-top:14px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.vote-btn{display:flex;align-items:center;gap:6px;padding:7px 16px;border:1.5px solid #e0e0e0;border-radius:9px;font-size:0.82rem;font-weight:600;cursor:pointer;background:#fff;color:#555;transition:all 0.2s;font-family:'Poppins',sans-serif;}
.vote-btn:hover{background:#f5f8ff;border-color:var(--blue-light);color:var(--blue);}
.vote-btn.voted{background:#ebf2ff;border-color:var(--blue);color:var(--blue);}
.vote-btn.down:hover{background:#fff5f5;border-color:var(--red);color:var(--red);}
.vote-btn.down.voted{background:#fff5f5;border-color:var(--red);color:var(--red);}
.map-pin-chip{display:inline-flex;align-items:center;gap:5px;font-size:0.74rem;background:#ebf2ff;color:var(--blue);border-radius:7px;padding:4px 10px;cursor:pointer;border:none;font-family:'Poppins',sans-serif;font-weight:600;transition:all 0.2s;}
.map-pin-chip:hover{background:#dbeafe;}
.category-tag{font-size:0.76rem;background:var(--bg);padding:5px 11px;border-radius:8px;color:var(--muted);}
.empty{text-align:center;padding:60px 20px;color:#bbb;animation:fadeIn 0.4s ease;}
.empty i{font-size:3rem;margin-bottom:14px;display:block;}
.loading{text-align:center;padding:50px;color:#888;font-size:0.9rem;animation:fadeIn 0.3s ease;}
.loading i{animation:spin 1s linear infinite;margin-right:8px;}

/* MAP VIEW */
#mapView{display:none;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.1);animation:scaleIn 0.3s ease;margin-bottom:28px;}
#mapView.show{display:block;}
#mainMap{height:560px;width:100%;background:#dde8f0;}
.map-legend{background:#fff;padding:14px 20px;display:flex;align-items:center;gap:20px;flex-wrap:wrap;border-top:1px solid #eee;}
.legend-item{display:flex;align-items:center;gap:8px;font-size:0.79rem;font-weight:600;color:#555;}
.legend-dot{width:12px;height:12px;border-radius:50%;flex-shrink:0;}
.legend-dot.dangerous{background:var(--red);}.legend-dot.caution{background:var(--orange);}.legend-dot.safe{background:var(--green);}.legend-dot.unpinned{background:#aaa;}
.map-info{margin-left:auto;font-size:0.79rem;color:var(--muted);font-style:italic;}

/* MODAL */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:200;justify-content:center;align-items:center;padding:20px;backdrop-filter:blur(3px);}
.modal-overlay.open{display:flex;animation:fadeIn 0.25s ease;}
.modal{background:#fff;border-radius:20px;padding:32px;width:100%;max-width:580px;max-height:92vh;overflow-y:auto;position:relative;animation:scaleIn 0.3s cubic-bezier(0.34,1.56,0.64,1);}
.modal h2{font-size:1.2rem;font-weight:800;color:var(--text);margin-bottom:4px;}
.modal .subtitle{font-size:0.84rem;color:var(--muted);margin-bottom:22px;}
.modal-close{position:absolute;top:16px;right:18px;background:var(--bg);border:none;font-size:1rem;color:var(--muted);cursor:pointer;width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;transition:all 0.2s;}
.modal-close:hover{background:#e0e0e0;color:var(--text);}
.modal .form-group{margin-bottom:15px;}
.modal .form-group label{display:block;font-size:0.82rem;font-weight:600;color:#444;margin-bottom:5px;}
.modal .form-group input,.modal .form-group textarea,.modal .form-group select{width:100%;padding:11px 14px;border:1.5px solid #e0e0e0;border-radius:9px;font-size:0.9rem;outline:none;transition:0.2s;font-family:'Poppins',sans-serif;resize:vertical;background:#fafafa;}
.modal .form-group input:focus,.modal .form-group textarea:focus,.modal .form-group select:focus{border-color:var(--blue-light);background:#fff;box-shadow:0 0 0 3px rgba(58,141,255,0.1);}
.modal .form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.status-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;}
.status-opt{border:2px solid #e0e0e0;border-radius:12px;padding:14px 8px;text-align:center;cursor:pointer;transition:all 0.2s;background:#fafafa;}
.status-opt:hover{border-color:var(--blue-light);transform:translateY(-2px);}
.status-opt.selected.dangerous{border-color:var(--red);background:#fff0f0;}
.status-opt.selected.caution{border-color:var(--orange);background:#fff8f0;}
.status-opt.selected.safe{border-color:var(--green);background:#f0fff4;}
.status-opt i{display:block;font-size:1.4rem;margin-bottom:6px;}
.status-opt.dangerous i{color:var(--red);}.status-opt.caution i{color:var(--orange);}.status-opt.safe i{color:var(--green);}
.status-opt span{font-size:0.82rem;font-weight:700;}
.modal-actions{display:flex;gap:12px;margin-top:22px;}
.btn-submit{flex:1;background:linear-gradient(135deg,var(--blue-light),var(--blue));color:#fff;border:none;padding:13px;border-radius:10px;font-size:0.95rem;font-weight:700;cursor:pointer;transition:all 0.25s;font-family:'Poppins',sans-serif;}
.btn-submit:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(28,87,178,0.4);}
.btn-submit:disabled{opacity:0.6;cursor:not-allowed;transform:none;}
.btn-cancel{padding:13px 22px;border:1.5px solid #e0e0e0;background:#fff;border-radius:10px;font-size:0.95rem;cursor:pointer;transition:all 0.2s;font-family:'Poppins',sans-serif;font-weight:500;}
.btn-cancel:hover{background:var(--bg);}
.modal-msg{padding:10px 14px;border-radius:9px;font-size:0.84rem;margin-bottom:14px;display:none;}
.modal-msg.success{background:#e8f5e9;color:#2e7d32;}
.modal-msg.error{background:#ffebee;color:#c62828;}
.report-card.dangerous .badge{animation:pulse-glow 2s ease-in-out infinite;}

/* MAP PICKER (inside modal) */
.picker-section{border:1.5px solid #e0e0e0;border-radius:12px;overflow:hidden;margin-bottom:6px;background:#f8faff;}
.picker-header{padding:10px 14px;background:linear-gradient(90deg,#ebf2ff,#f8faff);display:flex;align-items:center;gap:8px;font-size:0.82rem;font-weight:600;color:var(--blue);border-bottom:1px solid #e0e0e0;}
#pickerMap{height:240px;width:100%;background:#dde8f0;}
.picker-toolbar{padding:10px 14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;background:#fff;border-top:1px solid #eef0f5;}
.locate-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 13px;border:1.5px solid var(--blue-light);border-radius:8px;font-size:0.79rem;font-weight:600;color:var(--blue);background:#f0f6ff;cursor:pointer;transition:all 0.2s;font-family:'Poppins',sans-serif;}
.locate-btn:hover{background:#dbeafe;}
.locate-btn:disabled{opacity:0.6;cursor:not-allowed;}
.clear-pin-btn{display:inline-flex;align-items:center;gap:5px;padding:7px 12px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:0.79rem;font-weight:600;color:#888;background:#fff;cursor:pointer;transition:all 0.2s;font-family:'Poppins',sans-serif;}
.clear-pin-btn:hover{border-color:var(--red);color:var(--red);}
.pin-status{font-size:0.76rem;color:var(--muted);margin-left:auto;display:flex;align-items:center;gap:5px;}
.pin-status.set{color:var(--green);font-weight:600;}
.radius-row{padding:10px 14px 12px;background:#fff;display:flex;align-items:center;gap:10px;border-top:1px solid #eef0f5;}
.radius-row label{font-size:0.79rem;font-weight:600;color:#555;white-space:nowrap;flex-shrink:0;}
.radius-row input[type=range]{flex:1;accent-color:var(--blue);height:4px;}
.radius-val{font-size:0.82rem;font-weight:700;color:var(--blue);min-width:60px;text-align:right;}

/* MINI MAP POPUP in feed */
.mini-map-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:300;justify-content:center;align-items:center;padding:20px;backdrop-filter:blur(3px);}
.mini-map-modal.open{display:flex;animation:fadeIn 0.2s ease;}
.mini-map-box{background:#fff;border-radius:18px;width:100%;max-width:640px;overflow:hidden;position:relative;animation:scaleIn 0.25s cubic-bezier(0.34,1.56,0.64,1);}
.mini-map-header{padding:14px 18px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #eee;}
.mini-map-header h4{font-size:0.95rem;font-weight:700;color:var(--text);}
.mini-map-close{background:var(--bg);border:none;font-size:0.95rem;color:var(--muted);cursor:pointer;width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;transition:all 0.2s;}
.mini-map-close:hover{background:#e0e0e0;}
#miniMap{height:360px;width:100%;}
.mini-map-footer{padding:12px 18px;font-size:0.79rem;color:var(--muted);background:#f9fafc;display:flex;gap:16px;flex-wrap:wrap;}

/* RESPONSIVE */
@media(max-width:900px){
  .sidebar{transform:translateX(calc(-1*var(--sidebar-w)));}
  .sidebar.mobile-open{transform:translateX(0);}
  .main{margin-left:0;}
  .ham-btn{display:flex;}
  .stats-row{grid-template-columns:1fr 1fr;}
  .topbar{padding:12px 16px;}
  .topbar h1{font-size:1rem;}
  .content{padding:16px;}
  .filters{padding:12px 14px;gap:8px;}
  .filters input{min-width:120px;}
  .user-name{display:none;}
  #mainMap{height:420px;}
}
@media(max-width:600px){
  .stats-row{grid-template-columns:1fr 1fr;}
  .stat-card{padding:14px 16px;}
  .stat-card strong{font-size:1.3rem;}
  .modal .form-row{grid-template-columns:1fr;}
  .post-btn span{display:none;}
  .post-btn{padding:10px 13px;}
  .report-card{padding:16px 18px;}
  .card-meta{gap:8px;font-size:0.75rem;}
  .vote-btn{padding:6px 12px;font-size:0.78rem;}
  .modal{padding:24px 18px;}
  .view-btn span{display:none;}
  #mainMap{height:340px;}
}
@media(max-width:400px){.stats-row{grid-template-columns:1fr;}}
</style>
</head>
<body>

<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="brand-logo">
      <div class="brand-icon-s"><i class="fas fa-shield-halved"></i></div>
      <span class="brand-name-s">HelpGuard</span>
    </div>
    <button class="toggle-btn" onclick="closeSidebar()"><i class="fas fa-xmark"></i></button>
  </div>
  <nav class="menu">
    <a href="dashboard.php" class="active"><i class="fas fa-house"></i> Dashboard</a>
    <a href="dashboard.php?filter=my"><i class="fas fa-file-lines"></i> My Reports</a>
    <?php if ($role === 'admin'): ?>
    <a href="admin.php"><i class="fas fa-gauge"></i> Admin Panel</a>
    <?php endif; ?>
  </nav>
  <div class="sidebar-stats">
    <div class="stat-label-s">Community Stats</div>
    <div class="stat-box"><div class="num"><?= $total_reports ?></div><div class="lbl">Active Reports</div></div>
    <div class="stat-box danger-box"><div class="num"><?= $danger_count ?></div><div class="lbl">Dangerous Areas</div></div>
    <div class="stat-box safe-box"><div class="num"><?= $safe_count ?></div><div class="lbl">Safe Areas</div></div>
  </div>
  <div class="sidebar-footer">
    <a href="logout.php"><i class="fas fa-right-from-bracket"></i> Log Out</a>
  </div>
</aside>

<!-- MAIN -->
<div class="main" id="main">
  <div class="topbar">
    <div class="topbar-left">
      <button class="ham-btn" onclick="openSidebar()"><i class="fas fa-bars"></i></button>
      <h1><i class="fas fa-map-location-dot" style="color:var(--blue-light);margin-right:8px;"></i>Community Feed</h1>
    </div>
    <div class="right-top">
      <button class="post-btn" id="postBtn" onclick="openModal()"><i class="fas fa-plus"></i> <span>Post Report</span></button>
      <div class="user-info">
        <div class="avatar"><?= strtoupper(substr($first_name,0,1)) ?></div>
        <span class="user-name"><?= htmlspecialchars($first_name) ?></span>
      </div>
    </div>
  </div>

  <div class="content">
    <!-- STAT CARDS -->
    <div class="stats-row">
      <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-clipboard-list"></i></div><div><strong><?= $total_reports ?></strong><span>Total Reports</span></div></div>
      <div class="stat-card"><div class="stat-icon red"><i class="fas fa-circle-exclamation"></i></div><div><strong><?= $danger_count ?></strong><span>Dangerous Areas</span></div></div>
      <div class="stat-card"><div class="stat-icon green"><i class="fas fa-circle-check"></i></div><div><strong><?= $safe_count ?></strong><span>Safe Areas</span></div></div>
      <div class="stat-card"><div class="stat-icon orange"><i class="fas fa-pen-to-square"></i></div><div><strong><?= $my_count ?></strong><span>My Reports</span></div></div>
    </div>

    <!-- FILTERS + VIEW TOGGLE -->
    <div class="filters">
      <input type="text" id="searchInput" placeholder="Search location, city, keyword...">
      <select id="statusFilter">
        <option value="">All Statuses</option>
        <option value="dangerous">🔴 Dangerous</option>
        <option value="caution">🟠 Caution</option>
        <option value="safe">🟢 Safe</option>
      </select>
      <select id="categoryFilter">
        <option value="">All Categories</option>
        <option value="crime">Crime</option>
        <option value="accident">Accident</option>
        <option value="flooding">Flooding</option>
        <option value="fire">Fire</option>
        <option value="health">Health</option>
        <option value="infrastructure">Infrastructure</option>
        <option value="other">Other</option>
      </select>
      <button id="myBtn" onclick="toggleMyReports()"><i class="fas fa-user"></i> My Reports</button>
      <button onclick="resetFilters()"><i class="fas fa-rotate"></i> Reset</button>
      <div class="view-toggle">
        <button class="view-btn active" id="btnFeed" onclick="switchView('feed')"><i class="fas fa-list"></i> <span>Feed</span></button>
        <button class="view-btn" id="btnMap"  onclick="switchView('map')"><i class="fas fa-map"></i> <span>Map</span></button>
      </div>
    </div>

    <!-- COMMUNITY MAP VIEW -->
    <div id="mapView">
      <div id="mainMap"></div>
      <div class="map-legend">
        <div class="legend-item"><div class="legend-dot dangerous"></div>Dangerous</div>
        <div class="legend-item"><div class="legend-dot caution"></div>Caution</div>
        <div class="legend-item"><div class="legend-dot safe"></div>Safe</div>
        <div class="legend-item"><div class="legend-dot unpinned"></div>No pin (text only)</div>
        <span class="map-info" id="mapInfo"></span>
      </div>
    </div>

    <!-- FEED -->
    <div id="feed"><div class="loading"><i class="fas fa-spinner"></i> Loading community reports...</div></div>
  </div>
</div>

<!-- POST REPORT MODAL -->
<div class="modal-overlay" id="modalOverlay" onclick="outsideClose(event)">
  <div class="modal">
    <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
    <h2><i class="fas fa-map-pin" style="color:var(--blue);margin-right:8px;"></i>Post a Safety Report</h2>
    <p class="subtitle">Let the community know what you've observed in your area</p>
    <div id="modalMsg" class="modal-msg"></div>
    <form id="reportForm" novalidate>
      <div class="form-group">
        <label>Report Title *</label>
        <input type="text" id="r_title" placeholder="e.g. Flooding near market area" maxlength="255" required>
      </div>
      <div class="form-group">
        <label>Location Status *</label>
        <div class="status-grid">
          <div class="status-opt dangerous" onclick="selectStatus('dangerous')" id="opt_dangerous"><i class="fas fa-circle-exclamation"></i><span>Dangerous</span></div>
          <div class="status-opt caution"   onclick="selectStatus('caution')"   id="opt_caution"><i class="fas fa-triangle-exclamation"></i><span>Caution</span></div>
          <div class="status-opt safe"      onclick="selectStatus('safe')"      id="opt_safe"><i class="fas fa-circle-check"></i><span>Safe</span></div>
        </div>
        <input type="hidden" id="r_status">
      </div>
      <div class="form-group">
        <label>Category *</label>
        <select id="r_category">
          <option value="">-- Select --</option>
          <option value="crime">Crime</option><option value="accident">Accident</option>
          <option value="flooding">Flooding</option><option value="fire">Fire</option>
          <option value="health">Health</option><option value="infrastructure">Infrastructure</option>
          <option value="other">Other</option>
        </select>
      </div>
      <div class="form-group">
        <label>Specific Location *</label>
        <input type="text" id="r_location" placeholder="e.g. Corner Rizal St. & Mabini Ave." maxlength="255" required>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Barangay</label><input type="text" id="r_barangay" placeholder="Barangay" maxlength="150"></div>
        <div class="form-group"><label>City / Municipality *</label><input type="text" id="r_city" placeholder="e.g. Imus, Cavite" maxlength="150" required></div>
      </div>
      <div class="form-group"><label>Province / Region</label><input type="text" id="r_province" placeholder="e.g. Cavite" maxlength="150"></div>

      <!-- OSM MAP PICKER -->
      <div class="form-group">
        <label><i class="fas fa-map-location-dot" style="color:var(--blue-light);margin-right:5px;"></i>Pin Exact Location on Map</label>
        <div class="picker-section">
          <div class="picker-header">
            <i class="fas fa-crosshairs"></i>
            Click the map to drop a pin · Drag the pin to adjust · Scroll to zoom
          </div>
          <div id="pickerMap"></div>
          <div class="picker-toolbar">
            <button type="button" class="locate-btn" id="locateBtn" onclick="useMyLocation()">
              <i class="fas fa-location-crosshairs"></i> Use My Location
            </button>
            <button type="button" class="clear-pin-btn" id="clearPinBtn" onclick="clearPin()" style="display:none;">
              <i class="fas fa-xmark"></i> Clear Pin
            </button>
            <span class="pin-status" id="pinStatus"><i class="fas fa-circle-info"></i> No pin placed</span>
          </div>
          <div class="radius-row">
            <label><i class="fas fa-circle-dot" style="color:var(--blue-light);margin-right:4px;"></i>Affected radius:</label>
            <input type="range" id="radiusSlider" min="50" max="3000" step="50" value="200" oninput="onRadiusChange(this.value)">
            <span class="radius-val" id="radiusVal">200 m</span>
          </div>
        </div>
        <input type="hidden" id="r_latitude">
        <input type="hidden" id="r_longitude">
        <input type="hidden" id="r_radius_m" value="200">
      </div>

      <div class="form-group">
        <label>Description *</label>
        <textarea id="r_description" placeholder="Describe what you observed in detail..." rows="4" maxlength="2000" required></textarea>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
        <button type="submit" class="btn-submit" id="submitBtn"><i class="fas fa-paper-plane"></i> Submit Report</button>
      </div>
    </form>
  </div>
</div>

<!-- MINI MAP VIEW MODAL (from feed card) -->
<div class="mini-map-modal" id="miniMapModal" onclick="closeMiniMap(event)">
  <div class="mini-map-box" id="miniMapBox">
    <div class="mini-map-header">
      <h4 id="miniMapTitle">Location</h4>
      <button class="mini-map-close" onclick="closeMiniMapDirect()"><i class="fas fa-xmark"></i></button>
    </div>
    <div id="miniMap"></div>
    <div class="mini-map-footer" id="miniMapFooter"></div>
  </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
// ─── State ───────────────────────────────────────────────────────────────
let allReports = [], showMine = false, currentView = 'feed';
const MY_USER_ID = <?= $user_id ?>;

// Status colours
const S_COLOR = { dangerous:'#e53e3e', caution:'#dd6b20', safe:'#38a169' };
const S_FILL  = { dangerous:'rgba(229,62,62,0.15)', caution:'rgba(221,107,32,0.15)', safe:'rgba(56,161,105,0.15)' };

// ─── Sidebar ─────────────────────────────────────────────────────────────
function openSidebar(){document.getElementById('sidebar').classList.add('mobile-open');document.getElementById('overlay').classList.add('show');}
function closeSidebar(){document.getElementById('sidebar').classList.remove('mobile-open');document.getElementById('overlay').classList.remove('show');}

// ─── Feed fetch / render ─────────────────────────────────────────────────
async function fetchReports(){
  try{
    const res=await fetch('api.php?action=get_reports');
    const data=await res.json();
    if(data.status==='success'){allReports=data.reports;renderFeed();if(currentView==='map')renderMainMap();}
    else{document.getElementById('feed').innerHTML='<div class="empty"><i class="fas fa-triangle-exclamation"></i><p>Failed to load reports.</p></div>';}
  }catch{document.getElementById('feed').innerHTML='<div class="empty"><i class="fas fa-wifi"></i><p>Network error. Please refresh.</p></div>';}
}

const catIcons={crime:'fa-user-shield',accident:'fa-car-burst',flooding:'fa-water',fire:'fa-fire',health:'fa-heart-pulse',infrastructure:'fa-road',other:'fa-circle-info'};

function getFiltered(){
  const search=document.getElementById('searchInput').value.trim().toLowerCase();
  const status=document.getElementById('statusFilter').value;
  const category=document.getElementById('categoryFilter').value;
  return allReports.filter(r=>{
    if(showMine && r.user_id!=MY_USER_ID) return false;
    if(status && r.status!==status) return false;
    if(category && r.category!==category) return false;
    if(search){const hay=(r.title+r.location_name+r.city+(r.barangay||'')+(r.description||'')).toLowerCase();if(!hay.includes(search))return false;}
    return true;
  });
}

function renderFeed(){
  const feed=document.getElementById('feed');
  const filtered=getFiltered();
  if(filtered.length===0){feed.innerHTML='<div class="empty"><i class="fas fa-binoculars"></i><p>No reports found. Try adjusting your filters.</p></div>';return;}
  feed.innerHTML=filtered.map((r,i)=>{
    const date=new Date(r.created_at).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'});
    const catIcon=catIcons[r.category]||'fa-circle-info';
    const isMine=(r.user_id==MY_USER_ID);
    const upVoted=r.user_vote==='up', downVoted=r.user_vote==='down';
    const hasPin = r.latitude && r.longitude;
    return `<div class="report-card ${r.status}" id="card_${r.id}" style="animation-delay:${i*0.04}s;">
      <div class="card-header">
        <h3>${esc(r.title)}</h3>
        <span class="badge ${r.status}">${ucFirst(r.status)}</span>
      </div>
      <div class="card-meta">
        <span><i class="fas fa-map-location-dot"></i>${esc(r.location_name)}</span>
        <span><i class="fas fa-city"></i>${esc(r.city)}${r.province?', '+esc(r.province):''}</span>
        <span><i class="fas fa-clock"></i>${date}</span>
        <span><i class="fas fa-user"></i>${esc(r.poster_name)}</span>
      </div>
      <div class="card-body"><p>${esc(r.description)}</p></div>
      <div class="card-footer">
        <button class="vote-btn ${upVoted?'voted':''}" onclick="vote(${r.id},'up')"><i class="fas fa-thumbs-up"></i><span id="up_${r.id}">${r.upvotes}</span></button>
        <button class="vote-btn down ${downVoted?'voted':''}" onclick="vote(${r.id},'down')"><i class="fas fa-thumbs-down"></i><span id="down_${r.id}">${r.downvotes}</span></button>
        ${hasPin?`<button class="map-pin-chip" onclick="openMiniMap(${r.id})"><i class="fas fa-map-pin"></i> View on Map</button>`:''}
        ${isMine
          ?`<button class="vote-btn" onclick="deleteReport(${r.id})" style="margin-left:auto;border-color:var(--red);color:var(--red);"><i class="fas fa-trash-can"></i></button>`
          :`<span class="category-tag" style="margin-left:auto;"><i class="fas ${catIcon}"></i> ${ucFirst(r.category)}</span>`}
      </div>
    </div>`;
  }).join('');
}

let searchTimeout;
document.getElementById('searchInput').addEventListener('input',()=>{clearTimeout(searchTimeout);searchTimeout=setTimeout(()=>{renderFeed();if(currentView==='map')renderMainMap();},300);});
document.getElementById('statusFilter').addEventListener('change',()=>{renderFeed();if(currentView==='map')renderMainMap();});
document.getElementById('categoryFilter').addEventListener('change',()=>{renderFeed();if(currentView==='map')renderMainMap();});
function toggleMyReports(){showMine=!showMine;document.getElementById('myBtn').classList.toggle('active',showMine);renderFeed();if(currentView==='map')renderMainMap();}
function resetFilters(){document.getElementById('searchInput').value='';document.getElementById('statusFilter').value='';document.getElementById('categoryFilter').value='';showMine=false;document.getElementById('myBtn').classList.remove('active');renderFeed();if(currentView==='map')renderMainMap();}

async function vote(id,voteType){
  const fd=new FormData();fd.append('action','vote');fd.append('report_id',id);fd.append('vote',voteType);
  try{const res=await fetch('api.php',{method:'POST',body:fd});const data=await res.json();
    if(data.status==='success'){const rep=allReports.find(r=>r.id==id);if(rep){rep.upvotes=data.upvotes;rep.downvotes=data.downvotes;rep.user_vote=data.user_vote;}renderFeed();}
  }catch{}
}
async function deleteReport(id){
  if(!confirm('Delete this report?')) return;
  const fd=new FormData();fd.append('action','delete_report');fd.append('report_id',id);
  try{const res=await fetch('api.php',{method:'POST',body:fd});const data=await res.json();
    if(data.status==='success'){allReports=allReports.filter(r=>r.id!=id);renderFeed();}
  }catch{}
}

// ─── View toggle ──────────────────────────────────────────────────────────
function switchView(v){
  currentView=v;
  const feedEl=document.getElementById('feed');
  const mapEl=document.getElementById('mapView');
  if(v==='map'){
    feedEl.style.display='none';
    mapEl.classList.add('show');
    document.getElementById('btnFeed').classList.remove('active');
    document.getElementById('btnMap').classList.add('active');
    initMainMap();
  } else {
    feedEl.style.display='';
    mapEl.classList.remove('show');
    document.getElementById('btnMap').classList.remove('active');
    document.getElementById('btnFeed').classList.add('active');
  }
}

// ─── Community map ────────────────────────────────────────────────────────
let mainMap=null, mainLayers=[];

function makeMarkerIcon(status){
  const c=S_COLOR[status]||'#888888';
  const svg=`<svg xmlns="http://www.w3.org/2000/svg" width="30" height="38" viewBox="0 0 30 38">
    <path d="M15 0C6.716 0 0 6.716 0 15c0 10 15 23 15 23S30 25 30 15C30 6.716 23.284 0 15 0z" fill="${c}" stroke="white" stroke-width="2"/>
    <circle cx="15" cy="15" r="6" fill="white" opacity="0.9"/>
  </svg>`;
  return L.divIcon({html:svg,className:'',iconSize:[30,38],iconAnchor:[15,38],popupAnchor:[0,-38]});
}

function initMainMap(){
  if(!mainMap){
    mainMap=L.map('mainMap').setView([14.5995,120.9842],11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
      attribution:'© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
      maxZoom:19
    }).addTo(mainMap);
  }
  renderMainMap();
}

function renderMainMap(){
  if(!mainMap) return;
  mainLayers.forEach(l=>mainMap.removeLayer(l));
  mainLayers=[];

  const filtered=getFiltered();
  const bounds=[];
  let pinCount=0;

  filtered.forEach(r=>{
    if(!r.latitude||!r.longitude) return;
    pinCount++;
    const ll=[r.latitude,r.longitude];
    bounds.push(ll);

    const circle=L.circle(ll,{
      radius:r.radius_m||200,
      color:S_COLOR[r.status]||'#888',
      fillColor:S_FILL[r.status]||'rgba(136,136,136,0.15)',
      fillOpacity:1,weight:2,dashArray:'6 4'
    }).addTo(mainMap);
    mainLayers.push(circle);

    const date=new Date(r.created_at).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric'});
    const marker=L.marker(ll,{icon:makeMarkerIcon(r.status)}).addTo(mainMap);
    marker.bindPopup(`
      <div style="min-width:210px;max-width:260px;font-family:'Poppins',sans-serif;font-size:0.82rem;line-height:1.5;">
        <div style="font-weight:800;font-size:0.93rem;color:#1a1a2e;margin-bottom:6px;">${esc(r.title)}</div>
        <span style="display:inline-block;background:${S_COLOR[r.status]};color:#fff;padding:2px 10px;border-radius:50px;font-size:0.69rem;font-weight:700;text-transform:uppercase;margin-bottom:8px;">${ucFirst(r.status)}</span>
        <div style="color:#555;"><i class="fas fa-map-location-dot" style="margin-right:4px;color:${S_COLOR[r.status]};"></i>${esc(r.location_name)}, ${esc(r.city)}</div>
        <div style="color:#888;font-size:0.74rem;margin-top:3px;">${date} · ${esc(r.poster_name)}</div>
        <div style="margin-top:8px;padding-top:8px;border-top:1px solid #eee;color:#444;">${esc((r.description||'').substring(0,150))}${(r.description||'').length>150?'…':''}</div>
        <div style="margin-top:7px;font-size:0.73rem;color:#999;display:flex;align-items:center;gap:5px;"><i class="fas fa-circle-dot"></i> Radius: ${r.radius_m||200}m</div>
      </div>`,{maxWidth:280});
    mainLayers.push(marker);
  });

  if(bounds.length>0) mainMap.fitBounds(bounds,{padding:[50,50],maxZoom:15});
  document.getElementById('mapInfo').textContent=`${pinCount} of ${filtered.length} report${filtered.length!==1?'s':''} pinned on map`;
  setTimeout(()=>mainMap.invalidateSize(),60);
}

// ─── Mini-map (view from feed card) ───────────────────────────────────────
let miniMap=null, miniLayers=[];

function openMiniMap(reportId){
  const r=allReports.find(x=>x.id==reportId);
  if(!r||!r.latitude||!r.longitude) return;
  document.getElementById('miniMapModal').classList.add('open');
  document.getElementById('miniMapTitle').textContent=r.title;
  document.getElementById('miniMapFooter').innerHTML=`
    <span><i class="fas fa-map-location-dot"></i> ${esc(r.location_name)}, ${esc(r.city)}${r.province?', '+esc(r.province):''}</span>
    <span><i class="fas fa-circle-dot"></i> Affected radius: ${r.radius_m||200}m</span>
    <span style="color:${S_COLOR[r.status]};font-weight:700;"><i class="fas fa-circle-exclamation"></i> ${ucFirst(r.status)}</span>`;
  setTimeout(()=>{
    if(!miniMap){
      miniMap=L.map('miniMap');
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
        attribution:'© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',maxZoom:19
      }).addTo(miniMap);
    }
    miniLayers.forEach(l=>miniMap.removeLayer(l));
    miniLayers=[];
    const ll=[r.latitude,r.longitude];
    const circle=L.circle(ll,{radius:r.radius_m||200,color:S_COLOR[r.status]||'#888',fillColor:S_FILL[r.status]||'rgba(136,136,136,0.15)',fillOpacity:1,weight:2,dashArray:'6 4'}).addTo(miniMap);
    const marker=L.marker(ll,{icon:makeMarkerIcon(r.status)}).addTo(miniMap).bindPopup(esc(r.title)).openPopup();
    miniLayers.push(circle,marker);
    miniMap.setView(ll,16);
    miniMap.invalidateSize();
  },150);
}

function closeMiniMap(e){if(e.target===document.getElementById('miniMapModal'))closeMiniMapDirect();}
function closeMiniMapDirect(){document.getElementById('miniMapModal').classList.remove('open');}

// ─── Post report modal ────────────────────────────────────────────────────
function openModal(){
  document.getElementById('modalOverlay').classList.add('open');
  setTimeout(initPickerMap,200);
}
function closeModal(){
  document.getElementById('modalOverlay').classList.remove('open');
  document.getElementById('reportForm').reset();
  clearStatusSelection();
  document.getElementById('modalMsg').style.display='none';
  clearPin();
}
function outsideClose(e){if(e.target===document.getElementById('modalOverlay'))closeModal();}
function selectStatus(s){clearStatusSelection();document.getElementById('opt_'+s).classList.add('selected');document.getElementById('r_status').value=s;}
function clearStatusSelection(){['dangerous','caution','safe'].forEach(s=>document.getElementById('opt_'+s).classList.remove('selected'));document.getElementById('r_status').value='';}

document.getElementById('reportForm').addEventListener('submit',async function(e){
  e.preventDefault();
  const msgEl=document.getElementById('modalMsg');
  const btn=document.getElementById('submitBtn');
  const title=document.getElementById('r_title').value.trim();
  const status=document.getElementById('r_status').value;
  const category=document.getElementById('r_category').value;
  const location=document.getElementById('r_location').value.trim();
  const city=document.getElementById('r_city').value.trim();
  const desc=document.getElementById('r_description').value.trim();
  if(!title||!status||!category||!location||!city||!desc){showMsg(msgEl,'error','Please fill in all required fields and choose a status.');return;}
  btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Submitting...';
  const fd=new FormData();
  fd.append('action','post_report');
  fd.append('title',title);fd.append('status',status);fd.append('category',category);
  fd.append('location_name',location);fd.append('barangay',document.getElementById('r_barangay').value.trim());
  fd.append('city',city);fd.append('province',document.getElementById('r_province').value.trim());
  fd.append('description',desc);
  fd.append('latitude', document.getElementById('r_latitude').value);
  fd.append('longitude',document.getElementById('r_longitude').value);
  fd.append('radius_m', document.getElementById('r_radius_m').value);
  try{
    const res=await fetch('api.php',{method:'POST',body:fd});const data=await res.json();
    if(data.status==='success'){showMsg(msgEl,'success','Report posted!');setTimeout(()=>{closeModal();fetchReports();},1000);}
    else{showMsg(msgEl,'error',data.message||'Failed to submit.');}
  }catch{showMsg(msgEl,'error','Network error. Please try again.');}
  btn.disabled=false;btn.innerHTML='<i class="fas fa-paper-plane"></i> Submit Report';
});

// ─── Map picker ───────────────────────────────────────────────────────────
let pickerMap=null, pickerMarker=null, pickerCircle=null;
let pickerRadius=200;

function initPickerMap(){
  if(pickerMap){setTimeout(()=>pickerMap.invalidateSize(),60);return;}
  pickerMap=L.map('pickerMap').setView([14.5995,120.9842],11);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{
    attribution:'© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',maxZoom:19
  }).addTo(pickerMap);
  pickerMap.on('click',function(e){placePin(e.latlng.lat,e.latlng.lng,true);});
  setTimeout(()=>pickerMap.invalidateSize(),100);
}

function placePin(lat,lng,doReverseGeo){
  document.getElementById('r_latitude').value=lat;
  document.getElementById('r_longitude').value=lng;

  if(pickerMarker) pickerMap.removeLayer(pickerMarker);
  if(pickerCircle) pickerMap.removeLayer(pickerCircle);

  pickerCircle=L.circle([lat,lng],{
    radius:pickerRadius,
    color:'#3a8dff',fillColor:'rgba(58,141,255,0.12)',fillOpacity:1,
    weight:2,dashArray:'6 4'
  }).addTo(pickerMap);

  pickerMarker=L.marker([lat,lng],{
    icon:L.divIcon({
      html:`<svg xmlns="http://www.w3.org/2000/svg" width="30" height="38" viewBox="0 0 30 38">
        <path d="M15 0C6.716 0 0 6.716 0 15c0 10 15 23 15 23S30 25 30 15C30 6.716 23.284 0 15 0z" fill="#3a8dff" stroke="white" stroke-width="2"/>
        <circle cx="15" cy="15" r="6" fill="white" opacity="0.9"/>
      </svg>`,
      className:'',iconSize:[30,38],iconAnchor:[15,38]
    }),
    draggable:true
  }).addTo(pickerMap);

  pickerMarker.on('dragend',function(ev){
    const p=ev.target.getLatLng();
    placePin(p.lat,p.lng,true);
  });

  const ps=document.getElementById('pinStatus');
  ps.className='pin-status set';
  ps.innerHTML=`<i class="fas fa-circle-check"></i> ${lat.toFixed(5)}, ${lng.toFixed(5)}`;
  document.getElementById('clearPinBtn').style.display='inline-flex';

  pickerMap.setView([lat,lng],Math.max(pickerMap.getZoom(),15));

  if(doReverseGeo) reverseGeocode(lat,lng);
}

function clearPin(){
  if(pickerMarker){pickerMap.removeLayer(pickerMarker);pickerMarker=null;}
  if(pickerCircle){pickerMap.removeLayer(pickerCircle);pickerCircle=null;}
  document.getElementById('r_latitude').value='';
  document.getElementById('r_longitude').value='';
  document.getElementById('r_radius_m').value='200';
  document.getElementById('radiusSlider').value=200;
  document.getElementById('radiusVal').textContent='200 m';
  pickerRadius=200;
  const ps=document.getElementById('pinStatus');
  ps.className='pin-status';
  ps.innerHTML='<i class="fas fa-circle-info"></i> No pin placed';
  document.getElementById('clearPinBtn').style.display='none';
}

function onRadiusChange(val){
  pickerRadius=parseInt(val);
  const display=pickerRadius>=1000?(pickerRadius/1000).toFixed(1)+' km':pickerRadius+' m';
  document.getElementById('radiusVal').textContent=display;
  document.getElementById('r_radius_m').value=pickerRadius;
  if(pickerCircle) pickerCircle.setRadius(pickerRadius);
}

async function reverseGeocode(lat,lng){
  try{
    const res=await fetch(`geocode_proxy.php?lat=${lat}&lon=${lng}`);
    const data=await res.json();
    if(data&&data.address){
      const a=data.address;
      if(!document.getElementById('r_location').value)
        document.getElementById('r_location').value=a.road||a.hamlet||a.suburb||'';
      if(!document.getElementById('r_barangay').value)
        document.getElementById('r_barangay').value=a.suburb||a.village||a.quarter||a.neighbourhood||'';
      if(!document.getElementById('r_city').value)
        document.getElementById('r_city').value=a.city||a.town||a.municipality||'';
      if(!document.getElementById('r_province').value)
        document.getElementById('r_province').value=a.state||a.province||'';
    }
  }catch(e){}
}

function useMyLocation(){
  if(!navigator.geolocation){alert('Geolocation not supported by your browser.');return;}
  const btn=document.getElementById('locateBtn');
  btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Locating…';
  btn.disabled=true;
  navigator.geolocation.getCurrentPosition(
    pos=>{
      btn.innerHTML='<i class="fas fa-location-crosshairs"></i> Use My Location';
      btn.disabled=false;
      placePin(pos.coords.latitude,pos.coords.longitude,true);
    },
    ()=>{
      btn.innerHTML='<i class="fas fa-location-crosshairs"></i> Use My Location';
      btn.disabled=false;
      alert('Could not get your location. Please allow location access and try again.');
    }
  );
}

// ─── Helpers ──────────────────────────────────────────────────────────────
function esc(s){if(!s)return'';return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function ucFirst(s){return s?s.charAt(0).toUpperCase()+s.slice(1):'';}
function showMsg(el,type,text){el.className='modal-msg '+type;el.textContent=text;el.style.display='block';}

// ─── Boot ─────────────────────────────────────────────────────────────────
fetchReports();
setInterval(fetchReports,60000);

if(new URLSearchParams(window.location.search).get('filter')==='my'){
  setTimeout(()=>{showMine=true;document.getElementById('myBtn').classList.add('active');renderFeed();},500);
}
</script>
</body>
</html>
