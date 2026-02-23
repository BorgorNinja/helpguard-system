<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit; }
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $mode     = trim($_POST['mode'] ?? 'user');
    $ip       = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $device   = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    if (empty($email) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']); exit;
    }

    // Hardcoded admin credentials
    if ($mode === 'admin' && $email === 'admin' && $password === 'admin') {
        session_regenerate_id(true);
        $_SESSION['user_id']    = 0;
        $_SESSION['first_name'] = 'Admin';
        $_SESSION['last_name']  = '';
        $_SESSION['role']       = 'admin';
        echo json_encode(['status' => 'success', 'message' => 'Welcome, Admin!', 'redirect' => 'admin.php']);
        exit;
    }

    $response    = ['status' => 'error', 'message' => 'Invalid credentials.'];
    $log_status  = 'Failed';
    $log_user_id = null;

    $stmt = $conn->prepare("SELECT id, first_name, last_name, password, role FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $first_name, $last_name, $hashed, $role);
        $stmt->fetch();
        if (password_verify($password, $hashed)) {
            session_regenerate_id(true);
            $_SESSION['user_id']    = $id;
            $_SESSION['first_name'] = htmlspecialchars($first_name, ENT_QUOTES, 'UTF-8');
            $_SESSION['last_name']  = htmlspecialchars($last_name, ENT_QUOTES, 'UTF-8');
            $_SESSION['role']       = $role;
            $log_status  = 'Success';
            $log_user_id = $id;
            $redirect = ($role === 'admin') ? 'admin.php' : 'dashboard.php';
            $response = ['status' => 'success', 'message' => 'Welcome back, ' . htmlspecialchars($first_name) . '!', 'redirect' => $redirect];
        }
    }
    $stmt->close();

    $log = $conn->prepare("INSERT INTO login_logs (user_id, email, ip_address, device, status) VALUES (?, ?, ?, ?, ?)");
    $log->bind_param("issss", $log_user_id, $email, $ip, $device, $log_status);
    $log->execute();
    $log->close();

    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login – HelpGuard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root {
  --blue:#1c57b2;--blue-dark:#0e3d8c;--blue-light:#3a8dff;
  --admin:#7c3aed;--admin-light:#a78bfa;--admin-dark:#5b21b6;
  --text:#1a1a2e;--muted:#666;
}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Poppins',sans-serif;}
body{background:#0a0f1e;min-height:100vh;display:flex;position:relative;overflow:hidden;}

.bg-canvas{position:fixed;inset:0;z-index:0;overflow:hidden;}
.bg-canvas::before{content:'';position:absolute;width:600px;height:600px;background:radial-gradient(circle,rgba(58,141,255,0.15) 0%,transparent 70%);top:-200px;left:-100px;animation:drift1 8s ease-in-out infinite alternate;}
.bg-canvas::after{content:'';position:absolute;width:500px;height:500px;background:radial-gradient(circle,rgba(124,58,237,0.12) 0%,transparent 70%);bottom:-150px;right:-100px;animation:drift2 10s ease-in-out infinite alternate;}
.orb{position:absolute;border-radius:50%;filter:blur(60px);animation:drift3 12s ease-in-out infinite;}
.orb-1{width:300px;height:300px;background:rgba(58,141,255,0.08);top:30%;left:20%;animation-delay:-4s;}
.orb-2{width:200px;height:200px;background:rgba(124,58,237,0.1);top:60%;left:60%;animation-delay:-8s;}
.particle{position:absolute;border-radius:50%;animation:float linear infinite;}
@keyframes drift1{from{transform:translate(0,0) scale(1);}to{transform:translate(40px,30px) scale(1.1);}}
@keyframes drift2{from{transform:translate(0,0) scale(1);}to{transform:translate(-30px,-40px) scale(1.15);}}
@keyframes drift3{0%,100%{transform:translate(0,0);}50%{transform:translate(20px,-20px);}}
@keyframes float{0%{transform:translateY(100vh) scale(0);opacity:0;}10%{opacity:1;}90%{opacity:1;}100%{transform:translateY(-100px) scale(1);opacity:0;}}
@keyframes slideInLeft{from{opacity:0;transform:translateX(-40px);}to{opacity:1;transform:translateX(0);}}
@keyframes slideInRight{from{opacity:0;transform:translateX(40px);}to{opacity:1;transform:translateX(0);}}
@keyframes fadeInUp{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}
@keyframes fadeInForm{from{opacity:0;transform:translateY(8px);}to{opacity:1;transform:translateY(0);}}
@keyframes pulse-icon{0%,100%{box-shadow:0 8px 24px rgba(58,141,255,0.4);}50%{box-shadow:0 8px 36px rgba(58,141,255,0.75);}}

.left{flex:1.1;display:flex;flex-direction:column;justify-content:center;padding:60px 56px;position:relative;z-index:1;animation:slideInLeft 0.7s cubic-bezier(0.22,1,0.36,1) both;}
.brand{display:flex;align-items:center;gap:12px;margin-bottom:48px;text-decoration:none;}
.brand-icon{width:48px;height:48px;background:linear-gradient(135deg,var(--blue-light),var(--blue));border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#fff;animation:pulse-icon 3s ease-in-out infinite;}
.brand-name{font-size:1.5rem;font-weight:800;color:#fff;letter-spacing:-0.5px;}
.left h1{font-size:2.8rem;font-weight:800;color:#fff;line-height:1.15;margin-bottom:20px;letter-spacing:-1px;}
.left h1 .accent{color:var(--blue-light);}
.left p{font-size:1rem;color:rgba(255,255,255,0.65);line-height:1.8;max-width:360px;margin-bottom:36px;}
.features{display:flex;flex-direction:column;gap:16px;}
.feature{display:flex;align-items:center;gap:14px;animation:fadeInUp 0.6s both;}
.feature:nth-child(1){animation-delay:0.3s;}
.feature:nth-child(2){animation-delay:0.45s;}
.feature:nth-child(3){animation-delay:0.6s;}
.feat-icon{width:40px;height:40px;background:rgba(255,255,255,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;color:var(--blue-light);flex-shrink:0;border:1px solid rgba(255,255,255,0.1);}
.feat-text{font-size:0.88rem;color:rgba(255,255,255,0.75);font-weight:500;}

.right{flex:1;background:#fff;display:flex;flex-direction:column;justify-content:center;padding:50px 56px;overflow-y:auto;position:relative;z-index:1;animation:slideInRight 0.7s cubic-bezier(0.22,1,0.36,1) both;border-radius:28px 0 0 28px;box-shadow:-20px 0 60px rgba(0,0,0,0.3);}
.back{font-size:0.83rem;color:var(--blue);text-decoration:none;font-weight:600;margin-bottom:24px;display:inline-flex;align-items:center;gap:6px;transition:gap 0.2s;}
.back:hover{gap:10px;}

.login-tabs{display:flex;margin-bottom:28px;background:#f0f2f7;border-radius:12px;padding:4px;}
.tab-btn{flex:1;padding:10px 16px;border:none;background:transparent;border-radius:9px;font-size:0.88rem;font-weight:600;cursor:pointer;transition:all 0.25s;color:var(--muted);font-family:'Poppins',sans-serif;display:flex;align-items:center;justify-content:center;gap:7px;}
.tab-btn.active-user{background:#fff;color:var(--blue);box-shadow:0 2px 8px rgba(0,0,0,0.1);}
.tab-btn.active-admin{background:var(--admin);color:#fff;box-shadow:0 2px 12px rgba(124,58,237,0.45);}
.tab-btn i{font-size:0.9rem;}

.form-section{display:none;animation:fadeInForm 0.35s ease both;}
.form-section.active{display:block;}
.section-title{font-size:1.5rem;font-weight:800;color:var(--text);margin-bottom:4px;letter-spacing:-0.5px;}
.section-sub{font-size:0.88rem;color:var(--muted);margin-bottom:24px;}

.admin-header{background:linear-gradient(135deg,#f5f3ff,#ede9fe);border:1px solid #ddd6fe;border-radius:12px;padding:16px 18px;display:flex;align-items:center;gap:12px;margin-bottom:22px;}
.admin-shield{width:42px;height:42px;background:var(--admin);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem;box-shadow:0 4px 12px rgba(124,58,237,0.35);}
.admin-header h4{font-size:0.92rem;font-weight:700;color:var(--admin-dark);}
.admin-header p{font-size:0.78rem;color:#7c3aed88;}

.form-group{margin-bottom:16px;position:relative;}
.form-group label{display:block;font-size:0.82rem;font-weight:600;color:#444;margin-bottom:6px;}
.form-group input{width:100%;padding:12px 16px;border:1.5px solid #e0e0e0;border-radius:10px;font-size:0.93rem;transition:all 0.2s;outline:none;font-family:'Poppins',sans-serif;background:#fafafa;}
.form-group input:focus{border-color:var(--blue-light);background:#fff;box-shadow:0 0 0 4px rgba(58,141,255,0.1);}
.admin-mode .form-group input:focus{border-color:var(--admin-light);box-shadow:0 0 0 4px rgba(124,58,237,0.1);}
.show-btn{position:absolute;right:13px;bottom:12px;font-size:0.72rem;color:var(--blue);cursor:pointer;font-weight:700;user-select:none;letter-spacing:0.5px;}
.forgot{text-align:right;margin-top:-8px;margin-bottom:18px;}
.forgot a{font-size:0.82rem;color:var(--blue);text-decoration:none;font-weight:600;}
.forgot a:hover{text-decoration:underline;}

.btn-primary{width:100%;background:linear-gradient(135deg,var(--blue-light),var(--blue));color:#fff;border:none;padding:13px;border-radius:10px;font-size:0.96rem;font-weight:700;cursor:pointer;transition:all 0.25s;font-family:'Poppins',sans-serif;position:relative;overflow:hidden;}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(28,87,178,0.4);}
.btn-primary:active{transform:translateY(0);}
.btn-primary:disabled{opacity:0.6;cursor:not-allowed;transform:none;}
.btn-admin{background:linear-gradient(135deg,var(--admin-light),var(--admin));}
.btn-admin:hover{box-shadow:0 8px 24px rgba(124,58,237,0.45);}

.signup-link{text-align:center;margin-top:18px;font-size:0.86rem;color:var(--muted);}
.signup-link a{color:var(--blue);font-weight:700;text-decoration:none;}
.signup-link a:hover{text-decoration:underline;}
.msg{padding:11px 16px;border-radius:9px;font-size:0.86rem;margin-bottom:14px;display:none;text-align:center;font-weight:500;}
.msg.success{background:#e8f5e9;color:#2e7d32;}
.msg.error{background:#ffebee;color:#c62828;}

@media(max-width:900px){
  body{flex-direction:column;overflow:auto;background:#0a0f1e;}
  .bg-canvas{display:none;}
  .left{padding:36px 28px 28px;animation:none;background:linear-gradient(160deg,#1c57b2,#3a8dff);}
  .left h1{font-size:1.9rem;}
  .left p,.features{display:none;}
  .right{border-radius:24px 24px 0 0;padding:36px 24px 48px;animation:none;box-shadow:none;flex:none;min-height:62vh;}
}
@media(max-width:480px){
  .left{padding:28px 20px 24px;}
  .right{padding:32px 20px 40px;}
  .section-title{font-size:1.3rem;}
  .tab-btn{font-size:0.82rem;padding:9px 10px;}
}
</style>
</head>
<body>

<div class="bg-canvas">
  <div id="particles" style="position:absolute;inset:0;overflow:hidden;"></div>
  <div class="orb orb-1"></div>
  <div class="orb orb-2"></div>
</div>

<div class="left">
  <a href="index.php" class="brand">
    <div class="brand-icon"><i class="fas fa-shield-halved"></i></div>
    <span class="brand-name">HelpGuard</span>
  </a>
  <h1>Protect Your <span class="accent">Community.</span><br>Together.</h1>
  <p>Join thousands reporting safety incidents and helping their neighborhoods stay informed and safe.</p>
  <div class="features">
    <div class="feature">
      <div class="feat-icon"><i class="fas fa-map-location-dot"></i></div>
      <span class="feat-text">Location-tagged safety alerts in real time</span>
    </div>
    <div class="feature">
      <div class="feat-icon"><i class="fas fa-users"></i></div>
      <span class="feat-text">Verified community-sourced reports</span>
    </div>
    <div class="feature">
      <div class="feat-icon"><i class="fas fa-bell"></i></div>
      <span class="feat-text">Instant area status updates near you</span>
    </div>
  </div>
</div>

<div class="right">
  <a href="index.php" class="back"><i class="fas fa-arrow-left"></i> Back to Home</a>

  <div class="login-tabs">
    <button class="tab-btn active-user" id="tabUser" onclick="switchTab('user')">
      <i class="fas fa-user"></i> Member Login
    </button>
    <button class="tab-btn" id="tabAdmin" onclick="switchTab('admin')">
      <i class="fas fa-shield-halved"></i> Admin Portal
    </button>
  </div>

  <!-- USER LOGIN -->
  <div class="form-section active" id="userSection">
    <div class="section-title">Welcome Back</div>
    <p class="section-sub">Sign in to your HelpGuard account</p>
    <div id="userMsg" class="msg"></div>
    <form id="userForm" novalidate>
      <input type="hidden" name="mode" value="user">
      <div class="form-group">
        <label>Email Address</label>
        <input type="email" id="email" name="email" placeholder="you@example.com" required autocomplete="email">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" id="password" name="password" placeholder="Your password" required>
        <span class="show-btn" onclick="togglePw('password',this)">SHOW</span>
      </div>
      <div class="forgot"><a href="#">Forgot Password?</a></div>
      <button class="btn-primary" type="submit" id="loginBtn"><i class="fas fa-right-to-bracket"></i> Sign In</button>
      <div class="signup-link">Don't have an account? <a href="signup.php">Create one</a></div>
    </form>
  </div>

  <!-- ADMIN LOGIN -->
  <div class="form-section admin-mode" id="adminSection">
    <div class="section-title">Admin Portal</div>
    <p class="section-sub">Restricted access — authorized personnel only</p>
    <div class="admin-header">
      <div class="admin-shield"><i class="fas fa-lock"></i></div>
      <div>
        <h4>Moderator / Admin Access</h4>
        <p>Use your admin credentials to continue</p>
      </div>
    </div>
    <div id="adminMsg" class="msg"></div>
    <form id="adminForm" novalidate>
      <input type="hidden" name="mode" value="admin">
      <div class="form-group">
        <label>Admin Username / Email</label>
        <input type="text" id="adminEmail" name="email" placeholder="admin" required>
      </div>
      <div class="form-group">
        <label>Admin Password</label>
        <input type="password" id="adminPassword" name="password" placeholder="••••••••" required>
        <span class="show-btn" onclick="togglePw('adminPassword',this)">SHOW</span>
      </div>
      <button class="btn-primary btn-admin" type="submit" id="adminBtn">
        <i class="fas fa-shield-halved"></i> Access Admin Panel
      </button>
    </form>
  </div>
</div>

<script>
(function(){
  const c=document.getElementById('particles');
  for(let i=0;i<50;i++){
    const p=document.createElement('div');
    p.className='particle';
    p.style.cssText=`left:${Math.random()*100}%;animation-duration:${6+Math.random()*10}s;animation-delay:${-Math.random()*16}s;width:${1+Math.random()*2}px;height:${1+Math.random()*2}px;opacity:${0.2+Math.random()*0.5};background:rgba(255,255,255,${0.3+Math.random()*0.4});`;
    c.appendChild(p);
  }
})();

function switchTab(mode){
  const isAdmin=mode==='admin';
  document.getElementById('userSection').classList.toggle('active',!isAdmin);
  document.getElementById('adminSection').classList.toggle('active',isAdmin);
  document.getElementById('tabUser').className='tab-btn'+((!isAdmin)?' active-user':'');
  document.getElementById('tabAdmin').className='tab-btn'+((isAdmin)?' active-admin':'');
}
function togglePw(id,btn){
  const el=document.getElementById(id);
  const s=el.type==='text';
  el.type=s?'password':'text';
  btn.textContent=s?'SHOW':'HIDE';
}
async function handleLogin(formId,btnId,msgId){
  const btn=document.getElementById(btnId);
  const msgEl=document.getElementById(msgId);
  const form=document.getElementById(formId);
  const fd=new FormData(form);
  const email=fd.get('email')?.trim();
  const password=fd.get('password')?.trim();
  if(!email||!password){show(msgEl,'error','Please fill in all fields.');return;}
  const orig=btn.innerHTML;
  btn.disabled=true;
  btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Signing in...';
  try{
    const res=await fetch('login.php',{method:'POST',body:fd});
    const data=await res.json();
    if(data.status==='success'){
      show(msgEl,'success',data.message);
      setTimeout(()=>{window.location.href=data.redirect;},800);
    }else{
      show(msgEl,'error',data.message);
      btn.disabled=false;btn.innerHTML=orig;
    }
  }catch{
    show(msgEl,'error','Something went wrong. Please try again.');
    btn.disabled=false;btn.innerHTML=orig;
  }
}
document.getElementById('userForm').addEventListener('submit',e=>{e.preventDefault();handleLogin('userForm','loginBtn','userMsg');});
document.getElementById('adminForm').addEventListener('submit',e=>{e.preventDefault();handleLogin('adminForm','adminBtn','adminMsg');});
function show(el,type,text){el.className='msg '+type;el.textContent=text;el.style.display='block';}
</script>
</body>
</html>
