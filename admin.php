<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); exit;
}
require 'db_connect.php';

$total_users    = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$total_reports  = $conn->query("SELECT COUNT(*) FROM reports WHERE is_archived=0")->fetch_row()[0];
$total_archived = $conn->query("SELECT COUNT(*) FROM reports WHERE is_archived=1")->fetch_row()[0];
$danger_count   = $conn->query("SELECT COUNT(*) FROM reports WHERE status='dangerous' AND is_archived=0")->fetch_row()[0];
$first_name     = $_SESSION['first_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard – HelpGuard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{
  --blue:#1c57b2;--blue-dark:#0e3d8c;--blue-light:#3a8dff;
  --admin:#7c3aed;--admin-light:#a78bfa;
  --red:#e53e3e;--green:#38a169;--orange:#dd6b20;
  --text:#1a1a2e;--muted:#666;--border:#eee;
  --bg:#f0f2f7;--card:#fff;
  --sidebar-w:260px;
}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Poppins',sans-serif;}
body{background:var(--bg);display:flex;min-height:100vh;color:var(--text);}

/* ── ANIMATIONS ── */
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
@keyframes slideDown{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
@keyframes scaleIn{from{opacity:0;transform:scale(0.95);}to{opacity:1;transform:scale(1);}}
@keyframes countUp{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}
@keyframes shimmer{0%{background-position:-200% 0;}100%{background-position:200% 0;}}
@keyframes pulse-dot{0%,100%{opacity:1;}50%{opacity:0.4;}}
@keyframes spin{to{transform:rotate(360deg);}}

/* ── SIDEBAR ── */
.sidebar{
  width:var(--sidebar-w);
  background:linear-gradient(180deg,#1a1a2e 0%,#16213e 60%,#0f1629 100%);
  color:#fff;display:flex;flex-direction:column;flex-shrink:0;
  position:fixed;top:0;left:0;bottom:0;z-index:100;
  transition:transform 0.3s cubic-bezier(0.4,0,0.2,1);
  box-shadow:4px 0 24px rgba(0,0,0,0.25);
}
.sidebar.closed{transform:translateX(calc(-1 * var(--sidebar-w)));}
.sidebar-header{padding:22px 18px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(255,255,255,0.08);}
.brand-row{display:flex;align-items:center;gap:10px;}
.brand-icon{width:38px;height:38px;background:linear-gradient(135deg,var(--blue-light),var(--blue));border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;box-shadow:0 4px 14px rgba(58,141,255,0.4);}
.brand-name{font-size:1rem;font-weight:800;}
.admin-badge{font-size:0.6rem;background:var(--admin);color:#fff;padding:2px 8px;border-radius:20px;font-weight:700;margin-left:4px;letter-spacing:0.5px;}
.toggle-btn{background:none;border:none;color:rgba(255,255,255,0.6);cursor:pointer;font-size:1.1rem;padding:4px;transition:color 0.2s;}
.toggle-btn:hover{color:#fff;}

.nav-section{padding:12px 12px 0;font-size:0.68rem;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:1px;font-weight:600;margin-bottom:6px;}
.menu{padding:0 10px;display:flex;flex-direction:column;gap:2px;}
.menu-item{display:flex;align-items:center;gap:11px;padding:11px 13px;text-decoration:none;color:rgba(255,255,255,0.7);font-size:0.875rem;font-weight:500;border-radius:10px;transition:all 0.2s;cursor:pointer;border:none;background:transparent;width:100%;text-align:left;font-family:'Poppins',sans-serif;}
.menu-item:hover{background:rgba(255,255,255,0.08);color:#fff;}
.menu-item.active{background:linear-gradient(135deg,rgba(58,141,255,0.25),rgba(28,87,178,0.2));color:#fff;border-left:3px solid var(--blue-light);}
.menu-item.admin-active{background:linear-gradient(135deg,rgba(124,58,237,0.25),rgba(91,33,182,0.2));border-left-color:var(--admin-light);}
.menu-item i{font-size:1rem;width:18px;text-align:center;flex-shrink:0;}
.menu-badge{margin-left:auto;background:var(--red);color:#fff;font-size:0.65rem;padding:2px 7px;border-radius:20px;font-weight:700;}

.sidebar-user{padding:14px;margin:auto 0 0;border-top:1px solid rgba(255,255,255,0.08);}
.user-card{background:rgba(255,255,255,0.06);border-radius:10px;padding:12px;display:flex;align-items:center;gap:10px;}
.user-avatar{width:36px;height:36px;border-radius:50%;background:var(--admin);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1rem;flex-shrink:0;}
.user-info-side{flex:1;min-width:0;}
.user-info-side .name{font-size:0.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.user-info-side .role{font-size:0.72rem;color:var(--admin-light);}
.logout-btn{background:none;border:none;color:rgba(255,255,255,0.5);cursor:pointer;font-size:0.9rem;padding:4px;transition:color 0.2s;}
.logout-btn:hover{color:#fff;}

/* ── MAIN ── */
.main{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-width:0;transition:margin-left 0.3s;}
.main.expanded{margin-left:0;}

/* ── TOPBAR ── */
.topbar{background:#fff;padding:14px 28px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 12px rgba(0,0,0,0.07);position:sticky;top:0;z-index:50;}
.topbar-left{display:flex;align-items:center;gap:14px;}
.ham-btn{background:none;border:none;font-size:1.2rem;color:var(--muted);cursor:pointer;padding:6px;border-radius:8px;transition:all 0.2s;display:none;}
.ham-btn:hover{background:#f0f2f7;color:var(--text);}
.topbar h1{font-size:1.1rem;font-weight:700;color:var(--text);}
.topbar-right{display:flex;align-items:center;gap:12px;}
.topbar-avatar{width:36px;height:36px;border-radius:50%;background:var(--admin);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;}
.topbar-name{font-size:0.88rem;font-weight:600;}
.live-dot{width:8px;height:8px;background:var(--green);border-radius:50%;animation:pulse-dot 2s infinite;}

/* ── CONTENT ── */
.content{padding:28px;animation:fadeIn 0.4s ease;}

/* ── STATS ── */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:18px;margin-bottom:28px;}
.stat-card{background:#fff;border-radius:16px;padding:20px 22px;box-shadow:0 2px 12px rgba(0,0,0,0.06);display:flex;align-items:center;gap:16px;animation:fadeInUp 0.5s both;transition:transform 0.2s,box-shadow 0.2s;position:relative;overflow:hidden;}
.stat-card:nth-child(1){animation-delay:0.05s;}
.stat-card:nth-child(2){animation-delay:0.1s;}
.stat-card:nth-child(3){animation-delay:0.15s;}
.stat-card:nth-child(4){animation-delay:0.2s;}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 28px rgba(0,0,0,0.1);}
.stat-card::after{content:'';position:absolute;top:0;left:0;right:0;height:3px;}
.stat-card.blue::after{background:linear-gradient(90deg,var(--blue-light),var(--blue));}
.stat-card.red::after{background:linear-gradient(90deg,#ff6b6b,var(--red));}
.stat-card.green::after{background:linear-gradient(90deg,#68d391,var(--green));}
.stat-card.purple::after{background:linear-gradient(90deg,var(--admin-light),var(--admin));}
.stat-icon{width:50px;height:50px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;}
.stat-icon.blue{background:#ebf2ff;color:var(--blue);}
.stat-icon.red{background:#fff0f0;color:var(--red);}
.stat-icon.green{background:#f0fff4;color:var(--green);}
.stat-icon.purple{background:#f5f3ff;color:var(--admin);}
.stat-num{font-size:1.8rem;font-weight:800;color:var(--text);animation:countUp 0.6s both;}
.stat-label{font-size:0.78rem;color:var(--muted);}

/* ── PANEL ── */
.panel{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,0.06);animation:scaleIn 0.4s ease both;margin-bottom:24px;}
.panel-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px;}
.panel-title{font-size:1rem;font-weight:700;color:var(--text);display:flex;align-items:center;gap:8px;}
.panel-title i{color:var(--blue);}

/* ── TAB SYSTEM ── */
.tab-nav{display:flex;gap:0;background:#f0f2f7;border-radius:12px;padding:4px;margin-bottom:24px;}
.tab-nav-btn{flex:1;padding:10px 18px;border:none;background:transparent;border-radius:9px;font-size:0.87rem;font-weight:600;cursor:pointer;transition:all 0.25s;color:var(--muted);font-family:'Poppins',sans-serif;display:flex;align-items:center;justify-content:center;gap:7px;white-space:nowrap;}
.tab-nav-btn.active{background:#fff;color:var(--blue);box-shadow:0 2px 8px rgba(0,0,0,0.1);}
.tab-nav-btn i{font-size:0.85rem;}
.tab-panel{display:none;animation:fadeInForm 0.3s ease both;}
.tab-panel.active{display:block;}
@keyframes fadeInForm{from{opacity:0;transform:translateY(6px);}to{opacity:1;transform:translateY(0);}}

/* ── FILTERS ── */
.filter-bar{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:18px;align-items:center;}
.filter-bar input,.filter-bar select{padding:9px 13px;border:1.5px solid #e0e0e0;border-radius:9px;font-size:0.85rem;outline:none;font-family:'Poppins',sans-serif;transition:0.2s;background:#fafafa;}
.filter-bar input:focus,.filter-bar select:focus{border-color:var(--blue-light);background:#fff;}
.filter-bar input{flex:1;min-width:180px;}
.filter-bar select{min-width:140px;}

/* ── TABLES ── */
.table-wrap{overflow-x:auto;border-radius:10px;border:1px solid var(--border);}
table{width:100%;border-collapse:collapse;font-size:0.83rem;}
th{text-align:left;padding:11px 14px;background:#f8f9fc;color:#555;font-weight:600;border-bottom:2px solid var(--border);font-size:0.76rem;text-transform:uppercase;letter-spacing:0.5px;white-space:nowrap;}
td{padding:12px 14px;border-bottom:1px solid #f5f5f5;vertical-align:middle;}
tr:last-child td{border-bottom:none;}
tr:hover td{background:#fafbff;transition:background 0.15s;}

/* ── BADGES ── */
.badge{padding:3px 10px;border-radius:50px;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.4px;white-space:nowrap;}
.badge.dangerous{background:#fff0f0;color:var(--red);}
.badge.caution{background:#fff8f0;color:var(--orange);}
.badge.safe{background:#f0fff4;color:var(--green);}
.badge.archived{background:#f5f5f5;color:#999;}
.badge.admin{background:#f5f3ff;color:var(--admin);}
.badge.user{background:#ebf2ff;color:var(--blue);}
.badge.active{background:#f0fff4;color:var(--green);}

/* ── ACTION BUTTONS ── */
.act-btn{border:none;border-radius:7px;padding:5px 12px;font-size:0.77rem;cursor:pointer;font-weight:600;transition:all 0.2s;font-family:'Poppins',sans-serif;display:inline-flex;align-items:center;gap:5px;}
.act-btn.del{background:#fff0f0;color:var(--red);}
.act-btn.del:hover{background:var(--red);color:#fff;transform:scale(1.03);}
.act-btn.restore{background:#f0fff4;color:var(--green);}
.act-btn.restore:hover{background:var(--green);color:#fff;}
.act-btn.warn{background:#fff8f0;color:var(--orange);}
.act-btn.warn:hover{background:var(--orange);color:#fff;}

.empty{text-align:center;padding:50px 20px;color:#bbb;}
.empty i{font-size:2.5rem;display:block;margin-bottom:12px;}
.empty p{font-size:0.9rem;}
.loading{text-align:center;padding:40px;color:#888;}
.loading i{animation:spin 1s linear infinite;}

/* ── USER CARD ROW ── */
.user-avatar-sm{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--blue-light),var(--blue));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.9rem;flex-shrink:0;}
.user-avatar-sm.admin-av{background:linear-gradient(135deg,var(--admin-light),var(--admin));}

/* ── OVERLAY FOR MOBILE SIDEBAR ── */
.sidebar-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.4);z-index:99;}
.sidebar-overlay.show{display:block;}

/* ── MOBILE ── */
@media(max-width:900px){
  .sidebar{transform:translateX(calc(-1 * var(--sidebar-w)));}
  .sidebar.mobile-open{transform:translateX(0);}
  .main{margin-left:0;}
  .stats-row{grid-template-columns:1fr 1fr;}
  .ham-btn{display:flex;}
  .tab-nav-btn span{display:none;}
  .tab-nav-btn{flex:none;padding:10px 14px;}
  .topbar{padding:12px 16px;}
  .content{padding:16px;}
  .filter-bar{gap:8px;}
  .filter-bar input{min-width:140px;}
  td,th{padding:10px 10px;}
}
@media(max-width:600px){
  .stats-row{grid-template-columns:1fr;}
  .stat-card{padding:16px 18px;}
  .panel{padding:16px;}
  .tab-nav{overflow-x:auto;}
}
</style>
</head>
<body>

<div class="sidebar-overlay" id="overlay" onclick="closeSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="brand-row">
      <div class="brand-icon"><i class="fas fa-shield-halved"></i></div>
      <div>
        <span class="brand-name">HelpGuard</span>
        <span class="admin-badge">ADMIN</span>
      </div>
    </div>
    <button class="toggle-btn" onclick="closeSidebar()"><i class="fas fa-xmark"></i></button>
  </div>

  <div style="margin-top:12px;">
    <div class="nav-section">Navigation</div>
    <nav class="menu">
      <button class="menu-item active" id="navOverview" onclick="showTab('overview')">
        <i class="fas fa-gauge"></i> Overview
      </button>
      <button class="menu-item" id="navUsers" onclick="showTab('users')">
        <i class="fas fa-users"></i> Manage Users
        <span class="menu-badge" id="userCount">—</span>
      </button>
      <button class="menu-item" id="navPosts" onclick="showTab('posts')">
        <i class="fas fa-clipboard-list"></i> Manage Posts
      </button>
      <button class="menu-item" id="navLogs" onclick="showTab('logs')">
        <i class="fas fa-scroll"></i> Login Logs
      </button>
    </nav>
    <div class="nav-section" style="margin-top:16px;">Quick Links</div>
    <nav class="menu">
      <a href="dashboard.php" class="menu-item">
        <i class="fas fa-house"></i> Community Feed
      </a>
    </nav>
  </div>

  <div class="sidebar-user">
    <div class="user-card">
      <div class="user-avatar">A</div>
      <div class="user-info-side">
        <div class="name"><?= htmlspecialchars($first_name) ?></div>
        <div class="role"><i class="fas fa-circle" style="font-size:0.5rem;color:#a78bfa;margin-right:4px;"></i>Administrator</div>
      </div>
      <a href="logout.php" class="logout-btn" title="Log Out"><i class="fas fa-right-from-bracket"></i></a>
    </div>
  </div>
</aside>

<!-- MAIN -->
<div class="main" id="main">
  <div class="topbar">
    <div class="topbar-left">
      <button class="ham-btn" onclick="openSidebar()"><i class="fas fa-bars"></i></button>
      <h1 id="pageTitle"><i class="fas fa-gauge" style="color:var(--blue);margin-right:8px;"></i>Admin Dashboard</h1>
    </div>
    <div class="topbar-right">
      <div class="live-dot" title="Live"></div>
      <div class="topbar-avatar">A</div>
      <span class="topbar-name"><?= htmlspecialchars($first_name) ?></span>
    </div>
  </div>

  <div class="content">

    <!-- STATS (always visible) -->
    <div class="stats-row" id="statsRow">
      <div class="stat-card blue">
        <div class="stat-icon blue"><i class="fas fa-users"></i></div>
        <div>
          <div class="stat-num"><?= $total_users ?></div>
          <div class="stat-label">Total Users</div>
        </div>
      </div>
      <div class="stat-card green">
        <div class="stat-icon green"><i class="fas fa-clipboard-list"></i></div>
        <div>
          <div class="stat-num"><?= $total_reports ?></div>
          <div class="stat-label">Active Reports</div>
        </div>
      </div>
      <div class="stat-card red">
        <div class="stat-icon red"><i class="fas fa-circle-exclamation"></i></div>
        <div>
          <div class="stat-num"><?= $danger_count ?></div>
          <div class="stat-label">Danger Alerts</div>
        </div>
      </div>
      <div class="stat-card purple">
        <div class="stat-icon purple"><i class="fas fa-archive"></i></div>
        <div>
          <div class="stat-num"><?= $total_archived ?></div>
          <div class="stat-label">Archived Posts</div>
        </div>
      </div>
    </div>

    <!-- ── OVERVIEW TAB ── -->
    <div class="tab-panel active" id="tab-overview">
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title"><i class="fas fa-chart-bar"></i> Recent Activity</div>
          <span style="font-size:0.8rem;color:var(--muted);">Last 10 reports</span>
        </div>
        <div class="table-wrap">
          <div class="loading" id="overviewLoading"><i class="fas fa-spinner"></i> Loading...</div>
          <table id="overviewTable" style="display:none;">
            <thead><tr><th>#</th><th>Title</th><th>Status</th><th>Category</th><th>Location</th><th>Posted By</th><th>Date</th></tr></thead>
            <tbody id="overviewBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ── USERS TAB ── -->
    <div class="tab-panel" id="tab-users">
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title"><i class="fas fa-users"></i> User Accounts</div>
          <span id="userTotal" style="font-size:0.8rem;color:var(--muted);"></span>
        </div>
        <div class="filter-bar">
          <input type="text" id="userSearch" placeholder="Search name or email...">
          <select id="userRoleFilter">
            <option value="">All Roles</option>
            <option value="user">Members</option>
            <option value="admin">Admins</option>
          </select>
        </div>
        <div class="table-wrap">
          <div class="loading" id="usersLoading"><i class="fas fa-spinner"></i> Loading users...</div>
          <table id="usersTable" style="display:none;">
            <thead><tr><th>#</th><th>User</th><th>Email</th><th>Role</th><th>Joined</th><th>Reports</th><th>Actions</th></tr></thead>
            <tbody id="usersBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ── POSTS TAB ── -->
    <div class="tab-panel" id="tab-posts">
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title"><i class="fas fa-clipboard-list"></i> All Reports</div>
          <span style="font-size:0.8rem;color:var(--muted);">Manage & moderate posts</span>
        </div>
        <div class="filter-bar">
          <input type="text" id="postSearch" placeholder="Search title, city, poster...">
          <select id="postStatus">
            <option value="">All Statuses</option>
            <option value="dangerous">Dangerous</option>
            <option value="caution">Caution</option>
            <option value="safe">Safe</option>
          </select>
          <select id="postArchived">
            <option value="0">Active Only</option>
            <option value="1">Archived Only</option>
            <option value="">All</option>
          </select>
        </div>
        <div class="table-wrap">
          <div class="loading" id="postsLoading"><i class="fas fa-spinner"></i> Loading posts...</div>
          <table id="postsTable" style="display:none;">
            <thead><tr><th>#</th><th>Title</th><th>Status</th><th>Category</th><th>Location</th><th>Posted By</th><th>Votes</th><th>Date</th><th>Actions</th></tr></thead>
            <tbody id="postsBody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ── LOGS TAB ── -->
    <div class="tab-panel" id="tab-logs">
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title"><i class="fas fa-scroll"></i> Login Logs</div>
          <span style="font-size:0.8rem;color:var(--muted);">Recent authentication events</span>
        </div>
        <div class="table-wrap">
          <div class="loading" id="logsLoading"><i class="fas fa-spinner"></i> Loading logs...</div>
          <table id="logsTable" style="display:none;">
            <thead><tr><th>#</th><th>Email</th><th>Status</th><th>IP Address</th><th>Device</th><th>Date / Time</th></tr></thead>
            <tbody id="logsBody"></tbody>
          </table>
        </div>
      </div>
    </div>

  </div><!-- /content -->
</div><!-- /main -->

<script>
let allReports=[], allUsers=[], allLogs=[];
let currentTab='overview';

// ── Sidebar ──────────────────────────────────────────────────
function openSidebar(){
  document.getElementById('sidebar').classList.add('mobile-open');
  document.getElementById('overlay').classList.add('show');
}
function closeSidebar(){
  document.getElementById('sidebar').classList.remove('mobile-open');
  document.getElementById('overlay').classList.remove('show');
}

// ── Tab navigation ────────────────────────────────────────────
const tabTitles = {
  overview:'<i class="fas fa-gauge" style="color:var(--blue);margin-right:8px;"></i>Overview',
  users:'<i class="fas fa-users" style="color:var(--blue);margin-right:8px;"></i>Manage Users',
  posts:'<i class="fas fa-clipboard-list" style="color:var(--blue);margin-right:8px;"></i>Manage Posts',
  logs:'<i class="fas fa-scroll" style="color:var(--blue);margin-right:8px;"></i>Login Logs',
};
function showTab(name){
  currentTab=name;
  document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.menu-item').forEach(b=>b.classList.remove('active'));
  document.getElementById('tab-'+name).classList.add('active');
  document.getElementById('nav'+name.charAt(0).toUpperCase()+name.slice(1))?.classList.add('active');
  document.getElementById('pageTitle').innerHTML=tabTitles[name];
  // Lazy load
  if(name==='users' && allUsers.length===0) loadUsers();
  if(name==='posts' && allReports.length===0) loadReports();
  if(name==='logs' && allLogs.length===0) loadLogs();
  if(window.innerWidth<=900) closeSidebar();
}

// ── OVERVIEW ─────────────────────────────────────────────────
async function loadOverview(){
  const res=await fetch('api.php?action=admin_get_reports');
  const data=await res.json();
  if(data.status==='success'){
    allReports=data.reports;
    const body=document.getElementById('overviewBody');
    const top10=allReports.filter(r=>!r.is_archived).slice(0,10);
    body.innerHTML=top10.map(r=>{
      const date=new Date(r.created_at).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric'});
      return `<tr>
        <td style="color:#aaa;">${r.id}</td>
        <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${esc(r.title)}">${esc(r.title)}</td>
        <td><span class="badge ${r.status}">${r.status}</span></td>
        <td style="color:var(--muted);">${r.category}</td>
        <td>${esc(r.location_name)}<br><small style="color:#aaa;">${esc(r.city)}</small></td>
        <td>${esc(r.poster_name)}</td>
        <td style="color:#888;font-size:0.78rem;">${date}</td>
      </tr>`;
    }).join('');
    document.getElementById('overviewLoading').style.display='none';
    document.getElementById('overviewTable').style.display='table';
  }
}

// ── USERS ─────────────────────────────────────────────────────
async function loadUsers(){
  const res=await fetch('api.php?action=admin_get_users');
  const data=await res.json();
  if(data.status==='success'){
    allUsers=data.users;
    document.getElementById('userCount').textContent=allUsers.length+' accounts';
    document.getElementById('userTotal').textContent=allUsers.length+' total accounts';
    document.getElementById('userCount').textContent=allUsers.length;
    renderUsers();
    document.getElementById('usersLoading').style.display='none';
    document.getElementById('usersTable').style.display='table';
  }
}
function renderUsers(){
  const search=document.getElementById('userSearch').value.toLowerCase();
  const role=document.getElementById('userRoleFilter').value;
  let list=allUsers.filter(u=>{
    if(role && u.role!==role) return false;
    if(search){const hay=(u.first_name+' '+u.last_name+u.email).toLowerCase(); if(!hay.includes(search)) return false;}
    return true;
  });
  const body=document.getElementById('usersBody');
  if(list.length===0){body.innerHTML='<tr><td colspan="7" class="empty"><i class="fas fa-users-slash"></i><p>No users match your filters.</p></td></tr>';return;}
  body.innerHTML=list.map(u=>{
    const date=new Date(u.created_at).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric'});
    const initials=(u.first_name[0]||'?').toUpperCase();
    return `<tr id="urow_${u.id}">
      <td style="color:#aaa;">${u.id}</td>
      <td><div style="display:flex;align-items:center;gap:10px;">
        <div class="user-avatar-sm ${u.role==='admin'?'admin-av':''}">${initials}</div>
        <div><div style="font-weight:600;">${esc(u.first_name)} ${esc(u.last_name)}</div></div>
      </div></td>
      <td style="color:var(--muted);">${esc(u.email)}</td>
      <td><span class="badge ${u.role}">${u.role}</span></td>
      <td style="color:#888;font-size:0.78rem;">${date}</td>
      <td style="text-align:center;">${u.report_count}</td>
      <td>
        ${u.role!=='admin' ? `<button class="act-btn del" onclick="deleteUser(${u.id},'${esc(u.first_name)} ${esc(u.last_name)}')"><i class="fas fa-trash-can"></i> Remove</button>` : '<span style="color:#aaa;font-size:0.78rem;">Protected</span>'}
      </td>
    </tr>`;
  }).join('');
}
async function deleteUser(uid, name){
  if(!confirm(`Remove user "${name}"?\n\nThis will delete their account and all their reports. This cannot be undone.`)) return;
  const fd=new FormData(); fd.append('action','admin_delete_user'); fd.append('user_id',uid);
  const res=await fetch('api.php',{method:'POST',body:fd});
  const data=await res.json();
  if(data.status==='success'){
    allUsers=allUsers.filter(u=>u.id!=uid);
    renderUsers();
    // update stat
    document.querySelector('.stat-num').textContent=allUsers.length;
  } else alert(data.message||'Failed to delete user.');
}
document.getElementById('userSearch').addEventListener('input',renderUsers);
document.getElementById('userRoleFilter').addEventListener('change',renderUsers);

// ── POSTS ─────────────────────────────────────────────────────
async function loadReports(){
  if(allReports.length>0){renderPosts();document.getElementById('postsLoading').style.display='none';document.getElementById('postsTable').style.display='table';return;}
  const res=await fetch('api.php?action=admin_get_reports');
  const data=await res.json();
  if(data.status==='success'){
    allReports=data.reports;
    renderPosts();
    document.getElementById('postsLoading').style.display='none';
    document.getElementById('postsTable').style.display='table';
  }
}
function renderPosts(){
  const search=document.getElementById('postSearch').value.toLowerCase();
  const status=document.getElementById('postStatus').value;
  const arc=document.getElementById('postArchived').value;
  let list=allReports.filter(r=>{
    if(status && r.status!==status) return false;
    if(arc!=='' && r.is_archived!=parseInt(arc)) return false;
    if(search){const hay=(r.title+r.city+r.location_name+r.poster_name).toLowerCase(); if(!hay.includes(search)) return false;}
    return true;
  });
  const body=document.getElementById('postsBody');
  if(list.length===0){body.innerHTML='<tr><td colspan="9"><div class="empty"><i class="fas fa-binoculars"></i><p>No posts match current filters.</p></div></td></tr>';return;}
  body.innerHTML=list.map(r=>{
    const arc=r.is_archived==1;
    const date=new Date(r.created_at).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric'});
    return `<tr>
      <td style="color:#aaa;">${r.id}</td>
      <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${esc(r.title)}">${esc(r.title)}</td>
      <td><span class="badge ${arc?'archived':r.status}">${arc?'archived':r.status}</span></td>
      <td style="color:var(--muted);">${r.category}</td>
      <td>${esc(r.location_name)}<br><small style="color:#aaa;">${esc(r.city)}</small></td>
      <td>${esc(r.poster_name)}</td>
      <td><span style="color:var(--green);">▲${r.upvotes}</span> <span style="color:var(--red);">▼${r.downvotes}</span></td>
      <td style="color:#888;font-size:0.78rem;white-space:nowrap;">${date}</td>
      <td style="white-space:nowrap;">
        ${arc
          ? `<button class="act-btn restore" onclick="postAction(${r.id},'restore')"><i class="fas fa-rotate-left"></i> Restore</button>`
          : `<button class="act-btn del" onclick="postAction(${r.id},'delete')"><i class="fas fa-archive"></i> Archive</button>`
        }
      </td>
    </tr>`;
  }).join('');
}
async function postAction(id, type){
  if(type==='delete'&&!confirm('Archive this report?')) return;
  if(type==='restore'&&!confirm('Restore this report?')) return;
  const fd=new FormData();
  fd.append('action',type==='delete'?'delete_report':'restore_report');
  fd.append('report_id',id);
  const res=await fetch('api.php',{method:'POST',body:fd});
  const data=await res.json();
  if(data.status==='success'){
    const r=allReports.find(x=>x.id==id);
    if(r) r.is_archived=type==='delete'?1:0;
    renderPosts();
  } else alert(data.message||'Action failed.');
}
document.getElementById('postSearch').addEventListener('input',renderPosts);
document.getElementById('postStatus').addEventListener('change',renderPosts);
document.getElementById('postArchived').addEventListener('change',renderPosts);

// ── LOGS ─────────────────────────────────────────────────────
async function loadLogs(){
  const res=await fetch('api.php?action=admin_get_logs');
  const data=await res.json();
  if(data.status==='success'){
    allLogs=data.logs;
    const body=document.getElementById('logsBody');
    body.innerHTML=allLogs.map(l=>{
      const date=new Date(l.created_at).toLocaleString('en-PH');
      const ok=l.status==='Success';
      return `<tr>
        <td style="color:#aaa;">${l.id}</td>
        <td style="font-weight:500;">${esc(l.email)}</td>
        <td><span class="badge ${ok?'safe':'dangerous'}">${ok?'✓ Success':'✗ Failed'}</span></td>
        <td style="color:var(--muted);font-size:0.8rem;">${esc(l.ip_address||'—')}</td>
        <td style="color:var(--muted);font-size:0.75rem;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${esc(l.device||'')}">
          ${(l.device||'—').substring(0,40)}${l.device?.length>40?'…':''}
        </td>
        <td style="color:#888;font-size:0.78rem;white-space:nowrap;">${date}</td>
      </tr>`;
    }).join('');
    document.getElementById('logsLoading').style.display='none';
    document.getElementById('logsTable').style.display='table';
  }
}

function esc(s){if(!s)return'';return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}

// ── INIT ─────────────────────────────────────────────────────
loadOverview();
</script>
</body>
</html>
