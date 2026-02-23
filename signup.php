<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit; }
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $first=trim($_POST['first_name']??''); $last=trim($_POST['last_name']??'');
    $email=trim($_POST['email']??''); $pw=$_POST['password']??''; $pw2=$_POST['confirm_password']??'';
    if(empty($first)||empty($last)||empty($email)||empty($pw)){echo json_encode(['status'=>'error','message'=>'All fields are required.']);exit;}
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)){echo json_encode(['status'=>'error','message'=>'Invalid email address.']);exit;}
    if(strlen($pw)<8){echo json_encode(['status'=>'error','message'=>'Password must be at least 8 characters.']);exit;}
    if($pw!==$pw2){echo json_encode(['status'=>'error','message'=>'Passwords do not match.']);exit;}
    $chk=$conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");$chk->bind_param("s",$email);$chk->execute();$chk->store_result();
    if($chk->num_rows>0){echo json_encode(['status'=>'error','message'=>'Email is already registered.']);exit;}
    $chk->close();
    $hash=password_hash($pw,PASSWORD_BCRYPT,['cost'=>12]);
    $ins=$conn->prepare("INSERT INTO users (first_name,last_name,email,password) VALUES (?,?,?,?)");
    $ins->bind_param("ssss",$first,$last,$email,$hash);
    if($ins->execute()){echo json_encode(['status'=>'success','message'=>'Account created! Redirecting...','redirect'=>'login.php']);}
    else{echo json_encode(['status'=>'error','message'=>'Registration failed.']);}
    $ins->close(); exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up – HelpGuard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--blue:#1c57b2;--blue-light:#3a8dff;--text:#1a1a2e;--muted:#666;}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Poppins',sans-serif;}
body{background:#0a0f1e;min-height:100vh;display:flex;overflow:hidden;}

.bg-canvas{position:fixed;inset:0;z-index:0;overflow:hidden;}
.bg-canvas::before{content:'';position:absolute;width:600px;height:600px;background:radial-gradient(circle,rgba(58,141,255,0.14) 0%,transparent 70%);top:-150px;right:-100px;animation:drift1 9s ease-in-out infinite alternate;}
.bg-canvas::after{content:'';position:absolute;width:400px;height:400px;background:radial-gradient(circle,rgba(56,161,105,0.1) 0%,transparent 70%);bottom:-100px;left:-100px;animation:drift2 11s ease-in-out infinite alternate;}
.particle{position:absolute;border-radius:50%;animation:float linear infinite;}
@keyframes drift1{from{transform:translate(0,0);}to{transform:translate(-30px,30px);}}
@keyframes drift2{from{transform:translate(0,0);}to{transform:translate(30px,-30px);}}
@keyframes float{0%{transform:translateY(100vh);opacity:0;}10%{opacity:1;}90%{opacity:1;}100%{transform:translateY(-100px);opacity:0;}}
@keyframes slideInLeft{from{opacity:0;transform:translateX(-40px);}to{opacity:1;transform:translateX(0);}}
@keyframes slideInRight{from{opacity:0;transform:translateX(40px);}to{opacity:1;transform:translateX(0);}}
@keyframes fadeInUp{from{opacity:0;transform:translateY(16px);}to{opacity:1;transform:translateY(0);}}

.left{flex:1;display:flex;flex-direction:column;justify-content:center;padding:60px 50px;position:relative;z-index:1;animation:slideInLeft 0.7s cubic-bezier(0.22,1,0.36,1) both;}
.brand{display:flex;align-items:center;gap:12px;margin-bottom:44px;text-decoration:none;}
.brand-icon{width:48px;height:48px;background:linear-gradient(135deg,var(--blue-light),var(--blue));border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#fff;box-shadow:0 8px 24px rgba(58,141,255,0.45);}
.brand-name{font-size:1.5rem;font-weight:800;color:#fff;}
.left h1{font-size:2.5rem;font-weight:800;color:#fff;line-height:1.2;margin-bottom:16px;letter-spacing:-0.5px;}
.left h1 .accent{color:var(--blue-light);}
.left p{font-size:0.95rem;color:rgba(255,255,255,0.65);line-height:1.8;max-width:360px;margin-bottom:32px;}
.steps{display:flex;flex-direction:column;gap:16px;}
.step{display:flex;align-items:center;gap:14px;animation:fadeInUp 0.6s both;}
.step:nth-child(1){animation-delay:0.3s;}
.step:nth-child(2){animation-delay:0.45s;}
.step:nth-child(3){animation-delay:0.6s;}
.step-num{width:36px;height:36px;background:rgba(255,255,255,0.12);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.88rem;border:1px solid rgba(255,255,255,0.2);flex-shrink:0;}
.step-text{font-size:0.87rem;color:rgba(255,255,255,0.75);font-weight:500;}

.right{flex:1.2;background:#fff;display:flex;flex-direction:column;justify-content:center;padding:48px 56px;overflow-y:auto;position:relative;z-index:1;animation:slideInRight 0.7s cubic-bezier(0.22,1,0.36,1) both;border-radius:28px 0 0 28px;box-shadow:-20px 0 60px rgba(0,0,0,0.3);}
.back{font-size:0.83rem;color:var(--blue);text-decoration:none;font-weight:600;margin-bottom:22px;display:inline-flex;align-items:center;gap:6px;transition:gap 0.2s;}
.back:hover{gap:10px;}
.section-title{font-size:1.5rem;font-weight:800;color:var(--text);margin-bottom:4px;letter-spacing:-0.5px;}
.section-sub{font-size:0.88rem;color:var(--muted);margin-bottom:22px;}

.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.form-group{margin-bottom:14px;position:relative;}
.form-group label{display:block;font-size:0.82rem;font-weight:600;color:#444;margin-bottom:5px;}
.form-group input{width:100%;padding:11px 15px;border:1.5px solid #e0e0e0;border-radius:10px;font-size:0.93rem;transition:all 0.2s;outline:none;font-family:'Poppins',sans-serif;background:#fafafa;}
.form-group input:focus{border-color:var(--blue-light);background:#fff;box-shadow:0 0 0 4px rgba(58,141,255,0.1);}
.show-btn{position:absolute;right:13px;bottom:12px;font-size:0.72rem;color:var(--blue);cursor:pointer;font-weight:700;user-select:none;}
.pw-rules{font-size:0.76rem;color:#aaa;margin-top:4px;}

.btn-primary{width:100%;background:linear-gradient(135deg,var(--blue-light),var(--blue));color:#fff;border:none;padding:13px;border-radius:10px;font-size:0.96rem;font-weight:700;cursor:pointer;transition:all 0.25s;font-family:'Poppins',sans-serif;margin-top:4px;}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(28,87,178,0.4);}
.btn-primary:disabled{opacity:0.6;cursor:not-allowed;transform:none;}
.login-link{text-align:center;margin-top:18px;font-size:0.87rem;color:var(--muted);}
.login-link a{color:var(--blue);font-weight:700;text-decoration:none;}
.login-link a:hover{text-decoration:underline;}
.msg{padding:11px 16px;border-radius:9px;font-size:0.85rem;margin-bottom:14px;display:none;text-align:center;font-weight:500;}
.msg.success{background:#e8f5e9;color:#2e7d32;}
.msg.error{background:#ffebee;color:#c62828;}

/* Password strength */
.pw-strength{height:4px;border-radius:4px;margin-top:6px;background:#eee;overflow:hidden;}
.pw-strength-bar{height:100%;border-radius:4px;transition:all 0.3s;}

@media(max-width:900px){
  body{flex-direction:column;overflow:auto;}
  .bg-canvas{display:none;}
  .left{padding:36px 28px 28px;animation:none;background:linear-gradient(160deg,#1c57b2,#3a8dff);}
  .left h1{font-size:1.9rem;}
  .left p,.steps{display:none;}
  .right{border-radius:24px 24px 0 0;padding:36px 24px 48px;animation:none;box-shadow:none;flex:none;min-height:60vh;}
}
@media(max-width:480px){
  .left{padding:28px 20px 24px;}
  .right{padding:28px 18px 40px;}
  .form-row{grid-template-columns:1fr;}
  .section-title{font-size:1.3rem;}
}
</style>
</head>
<body>

<div class="bg-canvas">
  <div id="particles" style="position:absolute;inset:0;overflow:hidden;"></div>
</div>

<div class="left">
  <a href="index.php" class="brand">
    <div class="brand-icon"><i class="fas fa-shield-halved"></i></div>
    <span class="brand-name">HelpGuard</span>
  </a>
  <h1>Join the <span class="accent">Safety</span><br>Network</h1>
  <p>Sign up in seconds and start contributing to a safer community for everyone around you.</p>
  <div class="steps">
    <div class="step"><div class="step-num">1</div><span class="step-text">Create your free account</span></div>
    <div class="step"><div class="step-num">2</div><span class="step-text">Post your first safety report</span></div>
    <div class="step"><div class="step-num">3</div><span class="step-text">Help protect your neighborhood</span></div>
  </div>
</div>

<div class="right">
  <a href="index.php" class="back"><i class="fas fa-arrow-left"></i> Back to Home</a>
  <div class="section-title">Create Account</div>
  <p class="section-sub">Join HelpGuard and make your community safer</p>

  <div id="msg" class="msg"></div>
  <form id="signupForm" novalidate>
    <div class="form-row">
      <div class="form-group"><label>First Name *</label><input type="text" id="first_name" name="first_name" placeholder="Juan" required></div>
      <div class="form-group"><label>Last Name *</label><input type="text" id="last_name" name="last_name" placeholder="dela Cruz" required></div>
    </div>
    <div class="form-group"><label>Email Address *</label><input type="email" id="email" name="email" placeholder="you@example.com" required></div>
    <div class="form-group">
      <label>Password *</label>
      <input type="password" id="password" name="password" placeholder="Min. 8 characters" required oninput="checkStrength(this.value)">
      <span class="show-btn" onclick="togglePw('password',this)">SHOW</span>
      <div class="pw-strength"><div class="pw-strength-bar" id="pwBar"></div></div>
      <div class="pw-rules">Use at least 8 characters with letters and numbers.</div>
    </div>
    <div class="form-group">
      <label>Confirm Password *</label>
      <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat your password" required>
      <span class="show-btn" onclick="togglePw('confirm_password',this)">SHOW</span>
    </div>
    <button class="btn-primary" type="submit" id="signupBtn"><i class="fas fa-user-plus"></i> Create Account</button>
    <div class="login-link">Already have an account? <a href="login.php">Sign In</a></div>
  </form>
</div>

<script>
(function(){
  const c=document.getElementById('particles');
  for(let i=0;i<40;i++){
    const p=document.createElement('div');
    p.className='particle';
    p.style.cssText=`left:${Math.random()*100}%;animation-duration:${7+Math.random()*10}s;animation-delay:${-Math.random()*17}s;width:${1+Math.random()*2}px;height:${1+Math.random()*2}px;background:rgba(255,255,255,${0.2+Math.random()*0.4});`;
    c.appendChild(p);
  }
})();

function togglePw(id,btn){const el=document.getElementById(id);const s=el.type==='text';el.type=s?'password':'text';btn.textContent=s?'SHOW':'HIDE';}

function checkStrength(val){
  const bar=document.getElementById('pwBar');
  let score=0;
  if(val.length>=8)score++;
  if(/[A-Z]/.test(val))score++;
  if(/[0-9]/.test(val))score++;
  if(/[^A-Za-z0-9]/.test(val))score++;
  const pct=[0,30,55,80,100][score];
  const clr=['','#e53e3e','#dd6b20','#3a8dff','#38a169'][score]||'#eee';
  bar.style.width=pct+'%';
  bar.style.background=clr;
}

document.getElementById('signupForm').addEventListener('submit',async function(e){
  e.preventDefault();
  const btn=document.getElementById('signupBtn');
  const msgEl=document.getElementById('msg');
  const fd=new FormData(this);
  const orig=btn.innerHTML;
  btn.disabled=true;
  btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Creating account...';
  try{
    const res=await fetch('signup.php',{method:'POST',body:fd});
    const data=await res.json();
    if(data.status==='success'){
      msgEl.className='msg success';msgEl.textContent=data.message;msgEl.style.display='block';
      setTimeout(()=>{window.location.href=data.redirect;},1200);
    }else{
      msgEl.className='msg error';msgEl.textContent=data.message;msgEl.style.display='block';
      btn.disabled=false;btn.innerHTML=orig;
    }
  }catch{
    msgEl.className='msg error';msgEl.textContent='Something went wrong.';msgEl.style.display='block';
    btn.disabled=false;btn.innerHTML=orig;
  }
});
</script>
</body>
</html>
