<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HelpGuard – Community Safety Network</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
:root{--blue:#1c57b2;--blue-dark:#0e3d8c;--blue-light:#3a8dff;--text:#1a1a2e;--muted:#666;}
*{box-sizing:border-box;margin:0;padding:0;font-family:'Poppins',sans-serif;}
html{scroll-behavior:smooth;}
body{background:#fff;min-height:100vh;display:flex;flex-direction:column;overflow-x:hidden;}

/* ── ANIMATIONS ── */
@keyframes fadeInDown{from{opacity:0;transform:translateY(-20px);}to{opacity:1;transform:translateY(0);}}
@keyframes fadeInUp{from{opacity:0;transform:translateY(30px);}to{opacity:1;transform:translateY(0);}}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
@keyframes heroFloat{0%,100%{transform:translateY(0);}50%{transform:translateY(-12px);}}
@keyframes pulse-ring{0%{box-shadow:0 0 0 0 rgba(58,141,255,0.4);}70%{box-shadow:0 0 0 16px rgba(58,141,255,0);}100%{box-shadow:0 0 0 0 rgba(58,141,255,0);}}
@keyframes gradientShift{0%,100%{background-position:0% 50%;}50%{background-position:100% 50%;}  }
@keyframes orbit{from{transform:rotate(0deg) translateX(120px) rotate(0deg);}to{transform:rotate(360deg) translateX(120px) rotate(-360deg);}}
@keyframes counterUp{from{opacity:0;}to{opacity:1;}}
@keyframes slideInLeft{from{opacity:0;transform:translateX(-30px);}to{opacity:1;transform:translateX(0);}}
@keyframes slideInRight{from{opacity:0;transform:translateX(30px);}to{opacity:1;transform:translateX(0);}}
@keyframes scaleIn{from{opacity:0;transform:scale(0.9);}to{opacity:1;transform:scale(1);}}

/* ── NAV ── */
nav{background:rgba(255,255,255,0.95);padding:16px 40px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;backdrop-filter:blur(12px);border-bottom:1px solid rgba(0,0,0,0.06);animation:fadeInDown 0.5s ease;}
.nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--blue);}
.nav-brand-icon{width:38px;height:38px;background:linear-gradient(135deg,var(--blue-light),var(--blue));border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;color:#fff;box-shadow:0 4px 12px rgba(58,141,255,0.35);}
.nav-brand-name{font-size:1.2rem;font-weight:800;letter-spacing:-0.3px;}
.nav-links{display:flex;gap:10px;align-items:center;}
.nav-links a{text-decoration:none;padding:9px 20px;border-radius:9px;font-weight:600;font-size:0.88rem;transition:all 0.2s;}
.btn-outline{color:var(--blue);border:2px solid var(--blue);}
.btn-outline:hover{background:var(--blue);color:#fff;}
.btn-solid{background:linear-gradient(135deg,var(--blue-light),var(--blue));color:#fff;box-shadow:0 4px 14px rgba(28,87,178,0.35);}
.btn-solid:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(28,87,178,0.45);}
.ham-nav{display:none;background:none;border:none;font-size:1.3rem;color:var(--blue);cursor:pointer;}

/* ── HERO ── */
.hero{flex:1;display:flex;align-items:center;justify-content:center;padding:80px 60px;background:linear-gradient(135deg,#0a0f1e 0%,#0e3d8c 40%,#1c57b2 70%,#0a0f1e 100%);background-size:300% 300%;animation:gradientShift 10s ease infinite;position:relative;overflow:hidden;min-height:80vh;}
.hero-bg-effects{position:absolute;inset:0;overflow:hidden;}
.orb-h{position:absolute;border-radius:50%;filter:blur(60px);animation:heroFloat ease-in-out infinite;}
.orb-h-1{width:400px;height:400px;background:rgba(58,141,255,0.15);top:-100px;left:-100px;animation-duration:7s;}
.orb-h-2{width:300px;height:300px;background:rgba(124,58,237,0.12);bottom:-100px;right:-50px;animation-duration:9s;animation-delay:-3s;}
.orb-h-3{width:200px;height:200px;background:rgba(56,161,105,0.1);top:40%;left:40%;animation-duration:11s;animation-delay:-5s;}
.grid-lines{position:absolute;inset:0;background-image:linear-gradient(rgba(58,141,255,0.05) 1px,transparent 1px),linear-gradient(90deg,rgba(58,141,255,0.05) 1px,transparent 1px);background-size:60px 60px;}

.hero-inner{position:relative;z-index:1;text-align:center;max-width:760px;}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(58,141,255,0.15);border:1px solid rgba(58,141,255,0.3);border-radius:50px;padding:8px 18px;font-size:0.82rem;color:rgba(255,255,255,0.85);margin-bottom:28px;font-weight:500;animation:fadeIn 0.6s 0.2s both;backdrop-filter:blur(4px);}
.hero-badge .dot{width:7px;height:7px;background:#38a169;border-radius:50%;animation:pulse-ring 2s infinite;}
.hero h1{font-size:3.8rem;font-weight:800;color:#fff;line-height:1.1;margin-bottom:22px;letter-spacing:-1.5px;animation:fadeInUp 0.7s 0.3s both;}
.hero h1 .accent{background:linear-gradient(135deg,#3a8dff,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.hero p{font-size:1.1rem;color:rgba(255,255,255,0.7);line-height:1.8;max-width:560px;margin:0 auto 36px;animation:fadeInUp 0.7s 0.45s both;}
.hero-ctas{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;animation:fadeInUp 0.7s 0.6s both;}
.cta-primary{background:linear-gradient(135deg,var(--blue-light),var(--blue));color:#fff;padding:15px 34px;border-radius:12px;text-decoration:none;font-weight:700;font-size:1rem;transition:all 0.25s;box-shadow:0 8px 28px rgba(28,87,178,0.5);display:flex;align-items:center;gap:9px;}
.cta-primary:hover{transform:translateY(-3px);box-shadow:0 12px 36px rgba(28,87,178,0.6);}
.cta-secondary{background:rgba(255,255,255,0.1);color:#fff;padding:15px 34px;border-radius:12px;text-decoration:none;font-weight:600;font-size:1rem;transition:all 0.25s;border:1px solid rgba(255,255,255,0.2);backdrop-filter:blur(4px);}
.cta-secondary:hover{background:rgba(255,255,255,0.18);transform:translateY(-2px);}
.hero-stats{display:flex;gap:40px;justify-content:center;margin-top:50px;animation:fadeInUp 0.7s 0.75s both;}
.hero-stat{text-align:center;}
.hero-stat .num{font-size:2rem;font-weight:800;color:#fff;display:block;letter-spacing:-1px;}
.hero-stat .lbl{font-size:0.78rem;color:rgba(255,255,255,0.55);text-transform:uppercase;letter-spacing:1px;}

/* ── FEATURES ── */
.features{padding:90px 60px;background:#fff;}
.section-header{text-align:center;margin-bottom:60px;}
.section-header h2{font-size:2.4rem;font-weight:800;color:var(--text);letter-spacing:-0.5px;margin-bottom:12px;}
.section-header p{font-size:1rem;color:var(--muted);max-width:520px;margin:0 auto;}
.features-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;max-width:1100px;margin:0 auto;}
.feat-card{background:#fafafa;border-radius:18px;padding:32px;border:1px solid #f0f0f0;transition:all 0.3s;position:relative;overflow:hidden;}
.feat-card::before{content:'';position:absolute;inset:0;background:linear-gradient(135deg,transparent 60%,rgba(58,141,255,0.04));opacity:0;transition:0.3s;}
.feat-card:hover{transform:translateY(-6px);box-shadow:0 20px 50px rgba(0,0,0,0.1);border-color:rgba(58,141,255,0.2);}
.feat-card:hover::before{opacity:1;}
.feat-card-icon{width:54px;height:54px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;margin-bottom:20px;}
.feat-card-icon.red{background:#fff0f0;color:#e53e3e;}
.feat-card-icon.blue{background:#ebf2ff;color:var(--blue);}
.feat-card-icon.green{background:#f0fff4;color:#38a169;}
.feat-card-icon.orange{background:#fff8f0;color:#dd6b20;}
.feat-card-icon.purple{background:#f5f3ff;color:#7c3aed;}
.feat-card-icon.teal{background:#e6fffa;color:#319795;}
.feat-card h3{font-size:1.05rem;font-weight:700;color:var(--text);margin-bottom:10px;}
.feat-card p{font-size:0.88rem;color:var(--muted);line-height:1.7;}

/* ── CTA BAND ── */
.cta-band{background:linear-gradient(135deg,#0e3d8c,#1c57b2,#3a8dff);padding:80px 60px;text-align:center;}
.cta-band h2{font-size:2.4rem;font-weight:800;color:#fff;margin-bottom:16px;letter-spacing:-0.5px;}
.cta-band p{font-size:1rem;color:rgba(255,255,255,0.75);max-width:500px;margin:0 auto 32px;}
.cta-band a{display:inline-flex;align-items:center;gap:9px;background:#fff;color:var(--blue);padding:15px 36px;border-radius:12px;text-decoration:none;font-weight:700;font-size:1rem;transition:all 0.2s;box-shadow:0 8px 24px rgba(0,0,0,0.2);}
.cta-band a:hover{transform:translateY(-3px);box-shadow:0 12px 36px rgba(0,0,0,0.3);}

/* ── FOOTER ── */
footer{background:#0a0f1e;color:rgba(255,255,255,0.55);padding:32px 60px;text-align:center;font-size:0.85rem;}
footer .brand-f{display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:14px;color:#fff;font-weight:700;font-size:1rem;}

/* ── MOBILE NAV ── */
.mobile-nav{display:none;flex-direction:column;gap:10px;position:absolute;top:100%;left:0;right:0;background:#fff;padding:20px;box-shadow:0 8px 24px rgba(0,0,0,0.12);z-index:200;}
.mobile-nav.open{display:flex;}
.mobile-nav a{text-decoration:none;padding:12px 16px;border-radius:10px;font-weight:600;color:var(--blue);font-size:0.95rem;border:1.5px solid #e0e0e0;text-align:center;}
.mobile-nav a.solid{background:var(--blue);color:#fff;border-color:var(--blue);}

/* ── RESPONSIVE ── */
@media(max-width:960px){
  .features-grid{grid-template-columns:1fr 1fr;}
  .hero h1{font-size:2.8rem;}
  nav{padding:14px 24px;}
  .nav-links{display:none;}
  .ham-nav{display:block;}
  .hero{padding:60px 30px;}
  .features,.cta-band{padding:60px 28px;}
}
@media(max-width:640px){
  .hero h1{font-size:2.1rem;}
  .hero p{font-size:0.95rem;}
  .features-grid{grid-template-columns:1fr;}
  .hero-stats{gap:24px;flex-wrap:wrap;}
  .hero-stat .num{font-size:1.6rem;}
  .hero-ctas{gap:10px;}
  .cta-primary,.cta-secondary{padding:13px 24px;font-size:0.9rem;}
  footer{padding:24px 20px;}
}
</style>
</head>
<body>

<nav id="mainNav" style="position:relative;">
  <a href="#" class="nav-brand">
    <div class="nav-brand-icon"><i class="fas fa-shield-halved"></i></div>
    <span class="nav-brand-name">HelpGuard</span>
  </a>
  <div class="nav-links">
    <a href="login.php" class="btn-outline">Log In</a>
    <a href="signup.php" class="btn-solid">Get Started</a>
  </div>
  <button class="ham-nav" onclick="toggleMobileNav()"><i class="fas fa-bars" id="hamIcon"></i></button>
  <div class="mobile-nav" id="mobileNav">
    <a href="login.php">Log In</a>
    <a href="signup.php" class="solid">Get Started</a>
  </div>
</nav>

<section class="hero">
  <div class="hero-bg-effects">
    <div class="orb-h orb-h-1"></div>
    <div class="orb-h orb-h-2"></div>
    <div class="orb-h orb-h-3"></div>
    <div class="grid-lines"></div>
  </div>
  <div class="hero-inner">
    <div class="hero-badge">
      <div class="dot"></div>
      Community-powered safety network
    </div>
    <h1>Keep Your Community <span class="accent">Safe.</span></h1>
    <p>Report incidents, view real-time alerts, and help protect your neighborhood with HelpGuard's community-driven safety platform.</p>
    <div class="hero-ctas">
      <a href="signup.php" class="cta-primary"><i class="fas fa-shield-halved"></i> Join HelpGuard Free</a>
      <a href="login.php" class="cta-secondary"><i class="fas fa-right-to-bracket"></i> Sign In</a>
    </div>
    <div class="hero-stats">
      <div class="hero-stat"><span class="num" id="s1">0</span><span class="lbl">Reports Filed</span></div>
      <div class="hero-stat"><span class="num" id="s2">0</span><span class="lbl">Areas Covered</span></div>
      <div class="hero-stat"><span class="num" id="s3">0</span><span class="lbl">Community Members</span></div>
    </div>
  </div>
</section>

<section class="features">
  <div class="section-header">
    <h2>Everything You Need<br>to Stay Safe</h2>
    <p>HelpGuard combines real-time reporting with community verification to give you accurate local safety information.</p>
  </div>
  <div class="features-grid">
    <div class="feat-card">
      <div class="feat-card-icon red"><i class="fas fa-circle-exclamation"></i></div>
      <h3>Real-Time Alerts</h3>
      <p>Receive instant notifications about dangerous situations in your area reported by verified community members.</p>
    </div>
    <div class="feat-card">
      <div class="feat-card-icon blue"><i class="fas fa-map-location-dot"></i></div>
      <h3>Location-Tagged Reports</h3>
      <p>Every report is tagged to a specific location, making it easy to see exactly where incidents are occurring.</p>
    </div>
    <div class="feat-card">
      <div class="feat-card-icon green"><i class="fas fa-thumbs-up"></i></div>
      <h3>Community Verification</h3>
      <p>Reports are upvoted and downvoted by the community, surfacing the most accurate and relevant information.</p>
    </div>
    <div class="feat-card">
      <div class="feat-card-icon orange"><i class="fas fa-layer-group"></i></div>
      <h3>Category Filtering</h3>
      <p>Filter reports by crime, flooding, fire, accidents, health risks, and more to focus on what matters to you.</p>
    </div>
    <div class="feat-card">
      <div class="feat-card-icon purple"><i class="fas fa-gauge"></i></div>
      <h3>Admin Moderation</h3>
      <p>Dedicated admin panel ensures quality control and lets moderators manage the community responsibly.</p>
    </div>
    <div class="feat-card">
      <div class="feat-card-icon teal"><i class="fas fa-mobile-screen-button"></i></div>
      <h3>Works on Any Device</h3>
      <p>Fully responsive design that works beautifully on mobile phones, tablets, and desktop computers.</p>
    </div>
  </div>
</section>

<section class="cta-band">
  <h2>Ready to Make a Difference?</h2>
  <p>Join your community today and help build a safer neighborhood for everyone around you.</p>
  <a href="signup.php"><i class="fas fa-shield-halved"></i> Create Free Account</a>
</section>

<footer>
  <div class="brand-f"><i class="fas fa-shield-halved" style="color:#3a8dff;"></i> HelpGuard</div>
  <p>Community Safety Network &copy; <?= date('Y') ?> · Built to keep communities safe</p>
</footer>

<script>
// Mobile nav
function toggleMobileNav(){
  const nav=document.getElementById('mobileNav');
  const icon=document.getElementById('hamIcon');
  const open=nav.classList.toggle('open');
  icon.className=open?'fas fa-xmark':'fas fa-bars';
}
document.addEventListener('click',function(e){
  if(!e.target.closest('#mainNav'))document.getElementById('mobileNav').classList.remove('open');
});

// Animated counter
function animCount(el,target,suffix){
  let start=0; const dur=2000; const step=16;
  const timer=setInterval(()=>{
    start+=Math.ceil(target/(dur/step));
    if(start>=target){start=target;clearInterval(timer);}
    el.textContent=start.toLocaleString()+suffix;
  },step);
}

// Intersection observer for counter
const obs=new IntersectionObserver((entries)=>{
  entries.forEach(e=>{
    if(e.isIntersecting){
      animCount(document.getElementById('s1'),2400,'+');
      animCount(document.getElementById('s2'),180,'+');
      animCount(document.getElementById('s3'),850,'+');
      obs.disconnect();
    }
  });
},{threshold:0.5});
obs.observe(document.querySelector('.hero-stats'));

// Scroll-based feature card animations
const featObs=new IntersectionObserver((entries)=>{
  entries.forEach((e,i)=>{
    if(e.isIntersecting){
      e.target.style.animation=`scaleIn 0.5s ease ${i*0.08}s both`;
      e.target.style.opacity='1';
    }
  });
},{threshold:0.15});
document.querySelectorAll('.feat-card').forEach(c=>{c.style.opacity='0';featObs.observe(c);});
</script>
</body>
</html>
