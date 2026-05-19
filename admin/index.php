<?php
require_once dirname(__DIR__) . '/config.php';
session_start();

// Déconnexion
if (isset($_GET['logout'])) { session_destroy(); header('Location: index.php'); exit; }

$is_logged = is_admin();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portaye Admin</title>
<link rel="icon" type="image/svg+xml" href="../favicon.svg">
<style>
*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
:root {
  --sf: -apple-system,'Helvetica Neue',sans-serif;
  --black: #1d1d1f; --mid: #6e6e73; --light: #f5f5f7; --white: #fff;
  --blue: #0071e3; --blue-h: #0077ed;
  --green: #30d158; --red: #ff3b30; --orange: #ff9500;
  --sidebar: 220px;
}
body { font-family:var(--sf);background:var(--light);color:var(--black);-webkit-font-smoothing:antialiased;min-height:100vh; }

/* ─── LOGIN ─── */
.login-wrap { min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px; }
.login-card { background:var(--white);border-radius:20px;padding:48px 40px;width:100%;max-width:380px;box-shadow:0 2px 24px rgba(0,0,0,.06); }
.login-logo { font-size:26px;font-weight:700;text-align:center;margin-bottom:6px; }
.login-sub { font-size:14px;color:var(--mid);text-align:center;margin-bottom:36px; }
.f-row { margin-bottom:14px; }
.f-label { display:block;font-size:12px;font-weight:500;color:var(--mid);letter-spacing:.5px;text-transform:uppercase;margin-bottom:6px; }
.f-input { width:100%;background:var(--light);border:1px solid #d2d2d7;border-radius:10px;padding:12px 14px;font-size:15px;color:var(--black);font-family:var(--sf);outline:none;transition:border-color .2s; }
.f-input:focus { border-color:var(--blue);background:var(--white); }
.btn-primary { width:100%;background:var(--blue);color:#fff;border:none;padding:14px;border-radius:980px;font-size:16px;cursor:pointer;font-family:var(--sf);transition:background .15s;margin-top:8px; }
.btn-primary:hover { background:var(--blue-h); }
.alert-err { background:#fff0f0;border:1px solid #ffc0c0;color:#c00;padding:12px 16px;border-radius:10px;font-size:14px;margin-bottom:18px; }

/* ─── LAYOUT ─── */
.app { display:flex;min-height:100vh; }
.sidebar {
  width:var(--sidebar);background:#000;position:fixed;top:0;left:0;bottom:0;
  display:flex;flex-direction:column;padding:24px 0;z-index:50;
  overflow-y:auto;
}
.sb-logo { font-size:22px;font-weight:700;color:#f5f5f7;padding:0 22px;margin-bottom:32px; }
.sb-nav { flex:1; }
.sb-link {
  display:flex;align-items:center;gap:12px;padding:11px 22px;
  font-size:14px;color:#ebebf599;cursor:pointer;transition:color .15s;border:none;background:none;width:100%;text-align:left;
}
.sb-link svg { width:18px;height:18px;flex-shrink:0; }
.sb-link:hover { color:#f5f5f7; }
.sb-link.active { color:#f5f5f7;background:rgba(255,255,255,.08);border-radius:8px;margin:0 10px;padding:11px 12px;width:calc(100% - 20px); }
.sb-bottom { padding:16px 22px; }
.sb-logout { font-size:13px;color:#86868b;text-decoration:none;display:block; }
.sb-logout:hover { color:#f5f5f7; }

.main { margin-left:var(--sidebar);flex:1;padding:32px;max-width:calc(100vw - var(--sidebar)); }
.page { display:none; }
.page.active { display:block; }

/* ─── TOPBAR ─── */
.topbar { display:flex;align-items:center;justify-content:space-between;margin-bottom:32px; }
.page-title { font-size:26px;font-weight:700;letter-spacing:-.01em; }
.topbar-right { display:flex;gap:10px;align-items:center; }

/* ─── CARDS STATS ─── */
.stats-row { display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px; }
.stat-card { background:var(--white);border-radius:16px;padding:22px 24px; }
.stat-card .n { font-size:32px;font-weight:700;letter-spacing:-1px; }
.stat-card .l { font-size:13px;color:var(--mid);margin-top:4px; }
.stat-card.blue .n { color:var(--blue); }
.stat-card.green .n { color:var(--green); }
.stat-card.orange .n { color:var(--orange); }
.stat-card.red .n { color:var(--red); }

/* ─── TABLE ─── */
.card { background:var(--white);border-radius:16px;padding:0;overflow:hidden; }
.card-header { padding:20px 24px;border-bottom:1px solid #e5e5ea;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap; }
.card-title { font-size:17px;font-weight:600; }
.filter-row { display:flex;gap:8px;flex-wrap:wrap; }
.flt { padding:7px 14px;border:1px solid #d2d2d7;border-radius:980px;font-size:13px;cursor:pointer;background:var(--white);font-family:var(--sf);transition:all .15s;color:var(--black); }
.flt:hover,.flt.active { background:var(--black);color:#fff;border-color:var(--black); }
table { width:100%;border-collapse:collapse; }
th { font-size:11px;font-weight:600;color:var(--mid);letter-spacing:.5px;text-transform:uppercase;padding:13px 20px;text-align:left;border-bottom:1px solid #e5e5ea;background:var(--white); }
td { padding:14px 20px;font-size:14px;border-bottom:1px solid #f0f0f5;vertical-align:middle; }
tr:last-child td { border-bottom:none; }
tr:hover td { background:#fafafa; }
.badge { display:inline-block;padding:3px 10px;border-radius:980px;font-size:11px;font-weight:500; }
.badge-pending { background:#fff3cd;color:#856404; }
.badge-confirmed { background:#e8fdf0;color:#1d6336; }
.badge-cancelled { background:#fde8e8;color:#c00; }

/* ─── ACTIONS ─── */
.btn-sm { padding:6px 14px;border-radius:980px;font-size:12px;border:1px solid #d2d2d7;cursor:pointer;font-family:var(--sf);background:var(--white);transition:all .15s;font-weight:500; }
.btn-sm:hover { border-color:var(--black); }
.btn-confirm { border-color:var(--green);color:var(--green); }
.btn-confirm:hover { background:var(--green);color:#fff;border-color:var(--green); }
.btn-cancel { border-color:var(--red);color:var(--red); }
.btn-cancel:hover { background:var(--red);color:#fff;border-color:var(--red); }
.btn-delete { border-color:#d2d2d7;color:var(--mid); }
.btn-delete:hover { background:var(--red);color:#fff;border-color:var(--red); }

/* ─── CALENDAR VIEW ─── */
.cal-view { background:var(--white);border-radius:16px;overflow:hidden; }
.cal-nav-bar { display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid #e5e5ea; }
.cal-nav-bar h3 { font-size:17px;font-weight:600; }
.cal-grid-v { display:grid;grid-template-columns:repeat(7,1fr);gap:0; }
.cal-dow-v { font-size:11px;font-weight:600;color:var(--mid);text-align:center;padding:12px 0;background:#fafafa;border-bottom:1px solid #e5e5ea;border-right:1px solid #e5e5ea; }
.cal-dow-v:last-child{border-right:none}
.cal-cell-v {
  min-height:80px;padding:8px;border-bottom:1px solid #e5e5ea;border-right:1px solid #e5e5ea;
  vertical-align:top;font-size:13px;
}
.cal-cell-v:last-child{border-right:none}
.cal-cell-v.other-month { background:#fafafa; }
.cal-cell-v.today { background:#fff9e8; }
.cal-day-num { font-size:13px;font-weight:500;color:var(--mid);margin-bottom:4px; }
.cal-day-num.today-num { color:var(--blue);font-weight:700; }
.cal-appt-dot { font-size:11px;padding:2px 6px;border-radius:4px;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
.dot-pending { background:#fff3cd;color:#856404; }
.dot-confirmed { background:#e8fdf0;color:#1d6336; }
.dot-cancelled { background:#f0f0f5;color:var(--mid); }

/* ─── AVAILABILITY ─── */
.avail-day { display:flex;align-items:center;gap:16px;padding:18px 0;border-bottom:1px solid #e5e5ea; }
.avail-day:last-child { border-bottom:none; }
.avail-toggle { position:relative;width:44px;height:26px;flex-shrink:0; }
.avail-toggle input { opacity:0;width:0;height:0;position:absolute; }
.avail-slider { position:absolute;inset:0;border-radius:13px;background:#d2d2d7;cursor:pointer;transition:.2s; }
.avail-slider::before { content:'';position:absolute;width:20px;height:20px;border-radius:50%;background:#fff;top:3px;left:3px;transition:.2s; }
.avail-toggle input:checked + .avail-slider { background:var(--green); }
.avail-toggle input:checked + .avail-slider::before { transform:translateX(18px); }
.avail-label { font-size:15px;font-weight:500;width:90px;flex-shrink:0; }
.avail-times { display:flex;align-items:center;gap:8px;flex:1;flex-wrap:wrap; }
.time-input { padding:8px 12px;border:1px solid #d2d2d7;border-radius:8px;font-size:14px;font-family:var(--sf);background:var(--light);width:110px; }
.time-input:disabled { opacity:.35; }

/* ─── BLOCKED DATES ─── */
.blocked-list { padding:0 24px 24px; }
.blocked-item { display:flex;align-items:center;gap:12px;padding:14px 0;border-bottom:1px solid #e5e5ea; }
.blocked-item:last-child { border-bottom:none; }
.blocked-range { font-size:15px;font-weight:500;flex:1; }
.blocked-reason { font-size:13px;color:var(--mid); }
.add-form { background:var(--light);border-radius:12px;padding:20px;margin:20px 24px 0; }
.add-form-row { display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end; }
.add-form-row input,.add-form-row .f-input-s {
  padding:10px 12px;border:1px solid #d2d2d7;border-radius:8px;
  font-size:14px;font-family:var(--sf);background:var(--white);
}

/* ─── SETTINGS ─── */
.settings-section { background:var(--white);border-radius:16px;padding:28px;margin-bottom:20px; }
.settings-section h3 { font-size:17px;font-weight:600;margin-bottom:20px; }
.setting-row { display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid #e5e5ea;gap:16px;flex-wrap:wrap; }
.setting-row:last-of-type { border-bottom:none; }
.setting-info .label { font-size:15px;font-weight:500; }
.setting-info .desc { font-size:13px;color:var(--mid);margin-top:3px; }
.setting-control { flex-shrink:0; }
.select-input { padding:8px 12px;border:1px solid #d2d2d7;border-radius:8px;font-size:14px;font-family:var(--sf);background:var(--light); }

/* ─── CONTENT EDITOR ─── */
.cms-tabs { display:flex;gap:6px;flex-wrap:wrap;margin-bottom:24px; }
.cms-tab { padding:8px 18px;border-radius:980px;font-size:13px;border:1px solid #d2d2d7;cursor:pointer;background:var(--white);font-family:var(--sf);font-weight:500;transition:all .15s; }
.cms-tab.active { background:var(--black);color:#fff;border-color:var(--black); }
.cms-panel { display:none; }
.cms-panel.active { display:block; }
.cms-field { margin-bottom:20px; }
.cms-field label { display:block;font-size:12px;font-weight:600;color:var(--mid);letter-spacing:.5px;text-transform:uppercase;margin-bottom:8px; }
.cms-input { width:100%;background:var(--light);border:1px solid #d2d2d7;border-radius:10px;padding:10px 14px;font-size:15px;color:var(--black);font-family:var(--sf);outline:none;transition:border-color .2s; }
.cms-input:focus { border-color:var(--blue);background:var(--white); }
textarea.cms-input { min-height:90px;resize:vertical; }
.cms-color-row { display:flex;align-items:center;gap:12px; }
.cms-color-row input[type=color] { width:44px;height:44px;border:1px solid #d2d2d7;border-radius:8px;cursor:pointer;padding:2px;background:var(--white); }
.cms-array-list { display:flex;flex-direction:column;gap:12px; }
.cms-array-item { background:var(--light);border-radius:12px;padding:16px;position:relative; }
.cms-array-item .item-head { display:flex;align-items:center;justify-content:space-between;margin-bottom:12px; }
.cms-array-item .item-title { font-size:14px;font-weight:600; }
.cms-item-grid { display:grid;grid-template-columns:1fr 1fr;gap:10px; }
.cms-item-grid .full { grid-column:1/-1; }
.img-upload-wrap { display:flex;align-items:center;gap:12px;flex-wrap:wrap; }
.img-preview { width:80px;height:60px;object-fit:cover;border-radius:8px;border:1px solid #d2d2d7;display:none; }
.img-preview.show { display:block; }
.upload-btn { padding:8px 16px;border:1px dashed #d2d2d7;border-radius:8px;font-size:13px;cursor:pointer;background:var(--white);font-family:var(--sf); }
.upload-btn:hover { border-color:var(--blue);color:var(--blue); }
.cms-save-bar { position:sticky;bottom:0;background:var(--white);border-top:1px solid #e5e5ea;padding:16px 0;margin-top:28px;display:flex;align-items:center;gap:12px; }

/* ─── TECHNICIANS ─── */
.tech-grid { display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px; }
.tech-card { background:var(--white);border-radius:16px;padding:22px;border:1px solid #e5e5ea;position:relative; }
.tech-card .tc-name { font-size:17px;font-weight:600;margin-bottom:4px; }
.tech-card .tc-city { font-size:13px;color:var(--mid);margin-bottom:12px; }
.tech-card .tc-meta { font-size:12px;color:var(--mid); }
.tech-badge-active { display:inline-block;padding:3px 10px;border-radius:980px;font-size:11px;background:#e8fdf0;color:#1d6336;margin-bottom:10px; }
.tech-badge-inactive { display:inline-block;padding:3px 10px;border-radius:980px;font-size:11px;background:#f0f0f5;color:var(--mid);margin-bottom:10px; }
.tc-actions { display:flex;gap:8px;margin-top:14px; }
.tech-form-wrap { background:var(--white);border-radius:16px;padding:28px;margin-bottom:24px; }
.tech-form-wrap h3 { font-size:17px;font-weight:600;margin-bottom:20px; }
.tf-grid { display:grid;grid-template-columns:1fr 1fr;gap:14px; }
.tf-grid .tf-full { grid-column:1/-1; }

/* ─── PAGES ─── */
.pages-list { display:flex;flex-direction:column;gap:12px; }
.page-item { background:var(--white);border-radius:14px;padding:18px 22px;display:flex;align-items:center;gap:16px;flex-wrap:wrap; }
.pi-info { flex:1;min-width:180px; }
.pi-title { font-size:16px;font-weight:600;margin-bottom:4px; }
.pi-slug { font-size:12px;color:var(--mid); }
.pi-actions { display:flex;gap:8px;flex-wrap:wrap; }
.page-editor-wrap { background:var(--white);border-radius:16px;padding:28px;margin-bottom:24px; }
.page-editor-wrap h3 { font-size:17px;font-weight:600;margin-bottom:20px; }
.pe-grid { display:grid;grid-template-columns:1fr 1fr;gap:14px; }
.pe-full { grid-column:1/-1; }
.pe-editor { width:100%;min-height:280px;padding:14px;border:1px solid #d2d2d7;border-radius:10px;font-size:14px;font-family:var(--sf);background:var(--light);resize:vertical;line-height:1.6; }
.pe-editor:focus { border-color:var(--blue);background:var(--white);outline:none; }
.pe-toolbar { display:flex;gap:6px;flex-wrap:wrap;margin-bottom:8px; }
.pe-tool { padding:5px 12px;border:1px solid #d2d2d7;border-radius:6px;font-size:12px;cursor:pointer;background:var(--white);font-family:var(--sf);font-weight:500; }
.pe-tool:hover { background:var(--black);color:#fff;border-color:var(--black); }

/* ─── TOAST ─── */
.toast {
  position:fixed;bottom:24px;right:24px;z-index:999;
  background:#1c1c1e;color:#fff;padding:12px 20px;border-radius:12px;
  font-size:14px;font-weight:500;display:none;box-shadow:0 8px 24px rgba(0,0,0,.2);
}
.toast.show { display:block;animation:fadeUp .25s ease; }
.toast.err { background:#c00; }

/* ─── MODAL ─── */
.modal-overlay {
  position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:200;
  display:none;align-items:center;justify-content:center;
}
.modal-overlay.open { display:flex; }
.modal-box { background:var(--white);border-radius:20px;padding:36px;max-width:440px;width:90%;box-shadow:0 16px 48px rgba(0,0,0,.15); }
.modal-title { font-size:18px;font-weight:700;margin-bottom:10px; }
.modal-sub { font-size:14px;color:var(--mid);line-height:1.6;margin-bottom:24px; }
.modal-actions { display:flex;gap:10px;justify-content:flex-end; }

/* ─── MOBILE BAR ─── */
.mobile-bar {
  display:none;position:fixed;top:0;left:0;right:0;height:54px;background:#000;z-index:49;
  align-items:center;padding:0 16px;gap:12px;
}
.mobile-bar-logo { color:#f5f5f7;font-size:18px;font-weight:700;flex:1; }
.hamburger { background:none;border:none;cursor:pointer;padding:6px;display:flex;flex-direction:column;gap:5px; }
.hamburger span { display:block;width:22px;height:2px;background:#f5f5f7;border-radius:2px;transition:.2s; }
.sidebar-overlay { display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:48; }
.sidebar-overlay.open { display:block; }

/* ─── RESPONSIVE ─── */
@media(max-width:768px){
  .mobile-bar { display:flex; }
  .sidebar { transform:translateX(-100%);transition:transform .25s; }
  .sidebar.open { transform:translateX(0); }
  .main { margin-left:0;padding:70px 16px 32px;max-width:100vw; }
  .stats-row { grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:20px; }
  .page-title { font-size:20px; }
  .topbar { flex-wrap:wrap;gap:8px;margin-bottom:20px; }
  .topbar-right { flex-wrap:wrap;gap:8px; }
  .filter-row { width:100%; }
  .card-header { flex-direction:column;align-items:flex-start; }
  .cal-nav-bar { flex-wrap:wrap;gap:8px; }
  .cal-view { overflow-x:auto; }
  .cal-grid-v { min-width:500px; }
  .setting-row { flex-direction:column;align-items:flex-start;gap:10px; }
  .setting-control { width:100%; }
  #s-email,#s-pass { width:100%!important; }
  .add-form-row { flex-direction:column;gap:12px; }
  #b-reason { width:100%!important; }
  .avail-day { flex-wrap:wrap;row-gap:10px; }
  .avail-times { width:100%; }
  .time-input { flex:1;min-width:90px;width:auto; }
}
@media(max-width:480px){
  .stat-card .n { font-size:26px; }
  .stats-row { gap:10px; }
}
@keyframes fadeUp { from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)} }
</style>
</head>
<body>

<?php if (!$is_logged): ?>
<!-- ═══════════════════════════════ LOGIN PAGE ═══════════════════════════════ -->
<div class="login-wrap">
<div class="login-card">
  <div class="login-logo">Portaye</div>
  <div class="login-sub">Administration</div>
  <div id="login-err" class="alert-err" style="display:none"></div>
  <div class="f-row">
    <label class="f-label">Identifiant</label>
    <input id="l-user" type="text" class="f-input" placeholder="admin" autocomplete="username">
  </div>
  <div class="f-row">
    <label class="f-label">Mot de passe</label>
    <input id="l-pass" type="password" class="f-input" placeholder="••••••••" autocomplete="current-password">
  </div>
  <button class="btn-primary" id="l-btn">Se connecter</button>
</div>
</div>
<script>
(function(){
  function tryLogin(){
    const user=$('l-user').value.trim(), pass=$('l-pass').value;
    if(!user||!pass)return;
    $('l-btn').disabled=true;$('l-btn').textContent='Connexion…';
    fetch('../api.php?a=login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({username:user,password:pass})})
      .then(r=>r.json()).then(d=>{
        if(d.success){location.reload();}
        else{$('login-err').textContent=d.error||'Identifiants incorrects';$('login-err').style.display='block';$('l-btn').disabled=false;$('l-btn').textContent='Se connecter';}
      }).catch(()=>{$('login-err').textContent='Erreur réseau';$('login-err').style.display='block';$('l-btn').disabled=false;$('l-btn').textContent='Se connecter';});
  }
  function $(id){return document.getElementById(id);}
  $('l-btn').addEventListener('click',tryLogin);
  $('l-pass').addEventListener('keydown',e=>{if(e.key==='Enter')tryLogin();});
})();
</script>

<?php else: ?>
<!-- ════════════════════════════ ADMIN PANEL ══════════════════════════════════ -->

<!-- Mobile bar -->
<div class="mobile-bar" id="mobile-bar">
  <button class="hamburger" id="hamburger" aria-label="Menu"><span></span><span></span><span></span></button>
  <span class="mobile-bar-logo">Portaye</span>
</div>
<div class="sidebar-overlay" id="sidebar-overlay"></div>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <div class="sb-logo">Portaye</div>
  <nav class="sb-nav">
    <button class="sb-link active" data-page="dashboard">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Tableau de bord
    </button>
    <button class="sb-link" data-page="appointments">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Rendez-vous
    </button>
    <button class="sb-link" data-page="calview">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><polyline points="12 7 12 12 15 15"/></svg>
      Calendrier
    </button>
    <button class="sb-link" data-page="availability">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
      Disponibilités
    </button>
    <button class="sb-link" data-page="blocked">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
      Dates bloquées
    </button>
    <button class="sb-link" data-page="content">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      Contenu du site
    </button>
    <button class="sb-link" data-page="technicians">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Techniciens
    </button>
    <button class="sb-link" data-page="pages">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
      Pages
    </button>
    <button class="sb-link" data-page="settings">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
      Paramètres
    </button>
  </nav>
  <div class="sb-bottom">
    <a href="?logout" class="sb-logout">Déconnexion →</a>
  </div>
</div>

<!-- Main -->
<div class="main">

<!-- ─── DASHBOARD ─── -->
<div class="page active" id="page-dashboard">
  <div class="topbar">
    <div class="page-title">Tableau de bord</div>
    <div class="topbar-right">
      <a href="../" style="font-size:13px;color:var(--blue);text-decoration:none">← Voir le site</a>
    </div>
  </div>
  <div class="stats-row" id="stats-row">
    <div class="stat-card"><div class="n" id="st-total">—</div><div class="l">Réservations totales</div></div>
    <div class="stat-card orange"><div class="n" id="st-pending">—</div><div class="l">En attente</div></div>
    <div class="stat-card green"><div class="n" id="st-confirmed">—</div><div class="l">Confirmées</div></div>
    <div class="stat-card blue"><div class="n" id="st-today">—</div><div class="l">Aujourd'hui</div></div>
  </div>
  <div class="card">
    <div class="card-header"><span class="card-title">Prochains rendez-vous</span></div>
    <div id="upcoming-list">
      <div style="padding:40px;text-align:center;color:var(--mid);font-size:14px">Chargement…</div>
    </div>
  </div>
</div>

<!-- ─── RENDEZ-VOUS ─── -->
<div class="page" id="page-appointments">
  <div class="topbar">
    <div class="page-title">Rendez-vous</div>
    <div class="filter-row" id="appt-filters">
      <button class="flt active" data-status="">Tous</button>
      <button class="flt" data-status="pending">En attente</button>
      <button class="flt" data-status="confirmed">Confirmés</button>
      <button class="flt" data-status="cancelled">Annulés</button>
    </div>
  </div>
  <div class="card">
    <div style="overflow-x:auto">
      <table id="appt-table">
        <thead><tr>
          <th>Client</th><th>Date & Heure</th><th>Ville</th><th>Porte</th><th>Statut</th><th>Actions</th>
        </tr></thead>
        <tbody id="appt-tbody">
          <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--mid)">Chargement…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ─── CALENDRIER ─── -->
<div class="page" id="page-calview">
  <div class="topbar">
    <div class="page-title">Calendrier</div>
    <div class="topbar-right">
      <button class="flt" id="calv-prev">‹ Mois précédent</button>
      <span id="calv-label" style="font-weight:600;font-size:15px;padding:0 8px"></span>
      <button class="flt" id="calv-next">Mois suivant ›</button>
    </div>
  </div>
  <div class="cal-view card" id="calv-grid"></div>
</div>

<!-- ─── DISPONIBILITÉS ─── -->
<div class="page" id="page-availability">
  <div class="topbar"><div class="page-title">Disponibilités</div></div>
  <div class="settings-section">
    <h3>Horaires hebdomadaires</h3>
    <div id="avail-rows">
      <div style="padding:20px;text-align:center;color:var(--mid)">Chargement…</div>
    </div>
    <div style="margin-top:20px;border-top:1px solid #e5e5ea;padding-top:20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
      <div>
        <label class="f-label" style="margin-bottom:6px">Durée d'un créneau</label>
        <select id="slot-dur" class="select-input">
          <option value="30">30 minutes</option>
          <option value="60" selected>1 heure</option>
          <option value="90">1h30</option>
          <option value="120">2 heures</option>
        </select>
      </div>
      <button class="btn-primary" style="width:auto;margin-top:22px;padding:12px 28px" id="save-avail">Enregistrer les disponibilités</button>
    </div>
  </div>
</div>

<!-- ─── DATES BLOQUÉES ─── -->
<div class="page" id="page-blocked">
  <div class="topbar"><div class="page-title">Dates bloquées</div></div>
  <div class="card">
    <div class="card-header"><span class="card-title">Périodes indisponibles</span></div>
    <div class="add-form">
      <p style="font-size:14px;font-weight:600;margin-bottom:14px">Ajouter une période bloquée</p>
      <div class="add-form-row">
        <div><label class="f-label">Du</label><input type="date" id="b-start" class="f-input" style="width:auto"></div>
        <div><label class="f-label">Au</label><input type="date" id="b-end" class="f-input" style="width:auto"></div>
        <div><label class="f-label">Raison (optionnel)</label><input type="text" id="b-reason" class="f-input" placeholder="Vacances, congés…" style="width:200px"></div>
        <button class="btn-primary" style="width:auto;padding:10px 24px;margin-top:20px" id="add-blocked">Ajouter</button>
      </div>
    </div>
    <div class="blocked-list" id="blocked-list">
      <div style="padding:20px;text-align:center;color:var(--mid)">Chargement…</div>
    </div>
  </div>
</div>

<!-- ─── PARAMÈTRES ─── -->
<div class="page" id="page-settings">
  <div class="topbar"><div class="page-title">Paramètres</div></div>
  <div class="settings-section">
    <h3>Compte administrateur</h3>
    <div class="setting-row">
      <div class="setting-info"><div class="label">Email de notification</div><div class="desc">Adresse où sont envoyées les nouvelles réservations</div></div>
      <div class="setting-control"><input type="email" id="s-email" class="f-input" style="width:260px"></div>
    </div>
    <div class="setting-row">
      <div class="setting-info"><div class="label">Nouveau mot de passe</div><div class="desc">Laisser vide pour ne pas changer</div></div>
      <div class="setting-control"><input type="password" id="s-pass" class="f-input" placeholder="Nouveau mot de passe" style="width:260px"></div>
    </div>
    <button class="btn-primary" style="width:auto;padding:12px 28px;margin-top:8px" id="save-settings">Enregistrer</button>
  </div>
  <div class="settings-section">
    <h3>Test SMTP</h3>
    <p style="font-size:14px;color:var(--mid);margin-bottom:18px">Envoie un email de test à l'adresse admin pour vérifier la configuration SMTP.</p>
    <button class="btn-primary" style="width:auto;padding:12px 28px;background:var(--black)" id="test-smtp">Envoyer un email de test</button>
    <span id="smtp-result" style="font-size:13px;margin-left:12px;display:none"></span>
  </div>
</div>

<!-- ─── CONTENU DU SITE ─── -->
<div class="page" id="page-content">
  <div class="topbar">
    <div class="page-title">Contenu du site</div>
    <div class="topbar-right">
      <a href="../" target="_blank" style="font-size:13px;color:var(--blue);text-decoration:none">Voir le site ↗</a>
    </div>
  </div>
  <div class="cms-tabs">
    <button class="cms-tab active" data-tab="tab-texts">Textes & Prix</button>
    <button class="cms-tab" data-tab="tab-colors">Couleurs</button>
    <button class="cms-tab" data-tab="tab-features">Fonctionnalités</button>
    <button class="cms-tab" data-tab="tab-reviews">Avis clients</button>
    <button class="cms-tab" data-tab="tab-faq">FAQ</button>
  </div>

  <!-- Textes & Prix -->
  <div class="settings-section cms-panel active" id="tab-texts">
    <h3>Textes principaux</h3>
    <div class="cms-field">
      <label>Accroche hero (petite ligne)</label>
      <input type="text" class="cms-input" id="c-hero-eyebrow">
    </div>
    <div class="cms-field">
      <label>Titre hero</label>
      <input type="text" class="cms-input" id="c-hero-title">
    </div>
    <div class="cms-field">
      <label>Mot mis en évidence dans le titre (en bleu)</label>
      <input type="text" class="cms-input" id="c-hero-highlight">
    </div>
    <div class="cms-field">
      <label>Sous-titre hero</label>
      <textarea class="cms-input" id="c-hero-sub"></textarea>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:20px">
      <div class="cms-field" style="margin:0">
        <label>Texte du bouton principal</label>
        <input type="text" class="cms-input" id="c-cta-primary">
      </div>
      <div class="cms-field" style="margin:0">
        <label>Texte du lien secondaire</label>
        <input type="text" class="cms-input" id="c-cta-secondary">
      </div>
    </div>
    <h3 style="margin-top:8px;margin-bottom:20px">Prix</h3>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
      <div class="cms-field" style="margin:0">
        <label>Prix (chiffre seul, sans €)</label>
        <input type="number" class="cms-input" id="c-price" min="1">
      </div>
      <div class="cms-field" style="margin:0">
        <label>Note sous le prix</label>
        <input type="text" class="cms-input" id="c-price-note">
      </div>
    </div>
    <div class="cms-save-bar">
      <button class="btn-primary" style="width:auto;padding:12px 28px" id="save-content-texts">Enregistrer</button>
      <span id="save-texts-msg" style="font-size:13px;display:none"></span>
    </div>
  </div>

  <!-- Couleurs -->
  <div class="settings-section cms-panel" id="tab-colors">
    <h3>Couleurs principales</h3>
    <p style="font-size:14px;color:var(--mid);margin-bottom:24px">Ces couleurs s'appliquent aux boutons, liens et accents sur tout le site.</p>
    <div class="setting-row">
      <div class="setting-info"><div class="label">Couleur principale</div><div class="desc">Boutons, liens, badges</div></div>
      <div class="setting-control cms-color-row">
        <input type="color" id="c-color-primary" value="#0071e3">
        <input type="text" class="cms-input" id="c-color-primary-hex" style="width:120px" placeholder="#0071e3">
      </div>
    </div>
    <div class="setting-row">
      <div class="setting-info"><div class="label">Couleur au survol</div><div class="desc">Boutons au passage de la souris</div></div>
      <div class="setting-control cms-color-row">
        <input type="color" id="c-color-hover" value="#0077ed">
        <input type="text" class="cms-input" id="c-color-hover-hex" style="width:120px" placeholder="#0077ed">
      </div>
    </div>
    <div class="cms-save-bar">
      <button class="btn-primary" style="width:auto;padding:12px 28px" id="save-content-colors">Enregistrer les couleurs</button>
    </div>
  </div>

  <!-- Fonctionnalités -->
  <div class="settings-section cms-panel" id="tab-features">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <h3 style="margin:0">Cartes fonctionnalités</h3>
      <button class="btn-sm" id="add-feature">+ Ajouter</button>
    </div>
    <div class="cms-array-list" id="features-list"></div>
    <div class="cms-save-bar">
      <button class="btn-primary" style="width:auto;padding:12px 28px" id="save-content-features">Enregistrer</button>
    </div>
  </div>

  <!-- Avis -->
  <div class="settings-section cms-panel" id="tab-reviews">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <h3 style="margin:0">Avis clients</h3>
      <button class="btn-sm" id="add-review">+ Ajouter</button>
    </div>
    <div class="cms-array-list" id="reviews-list"></div>
    <div class="cms-save-bar">
      <button class="btn-primary" style="width:auto;padding:12px 28px" id="save-content-reviews">Enregistrer</button>
    </div>
  </div>

  <!-- FAQ -->
  <div class="settings-section cms-panel" id="tab-faq">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
      <h3 style="margin:0">Questions fréquentes</h3>
      <button class="btn-sm" id="add-faq">+ Ajouter</button>
    </div>
    <div class="cms-array-list" id="faq-list"></div>
    <div class="cms-save-bar">
      <button class="btn-primary" style="width:auto;padding:12px 28px" id="save-content-faq">Enregistrer</button>
    </div>
  </div>
</div>

<!-- ─── TECHNICIENS ─── -->
<div class="page" id="page-technicians">
  <div class="topbar">
    <div class="page-title">Techniciens installateurs</div>
    <div class="topbar-right">
      <button class="btn-primary" style="width:auto;padding:10px 22px;font-size:14px" id="btn-add-tech">+ Nouveau technicien</button>
    </div>
  </div>
  <!-- Formulaire -->
  <div class="tech-form-wrap" id="tech-form-wrap" style="display:none">
    <h3 id="tech-form-title">Nouveau technicien</h3>
    <input type="hidden" id="tech-id">
    <div class="tf-grid">
      <div class="cms-field" style="margin:0">
        <label>Nom complet *</label>
        <input type="text" class="cms-input" id="tech-name" placeholder="Jean Martin">
      </div>
      <div class="cms-field" style="margin:0">
        <label>Ville *</label>
        <input type="text" class="cms-input" id="tech-city" placeholder="Paris">
      </div>
      <div class="cms-field" style="margin:0">
        <label>Email</label>
        <input type="email" class="cms-input" id="tech-email" placeholder="jean@example.com">
      </div>
      <div class="cms-field" style="margin:0">
        <label>Téléphone</label>
        <input type="tel" class="cms-input" id="tech-phone" placeholder="06 00 00 00 00">
      </div>
      <div class="cms-field tf-full" style="margin:0">
        <label>Zones d'intervention (villes séparées par des virgules)</label>
        <input type="text" class="cms-input" id="tech-zones" placeholder="Paris, Boulogne, Versailles…">
      </div>
      <div class="cms-field tf-full" style="margin:0">
        <label>Note interne</label>
        <textarea class="cms-input" id="tech-note" style="min-height:70px" placeholder="Disponibilités, spécialités…"></textarea>
      </div>
      <div class="cms-field" style="margin:0;display:flex;align-items:center;gap:10px">
        <label class="avail-toggle" style="margin:0">
          <input type="checkbox" id="tech-active" checked>
          <span class="avail-slider"></span>
        </label>
        <span style="font-size:14px;font-weight:500">Actif (visible dans les affectations)</span>
      </div>
    </div>
    <div style="display:flex;gap:10px;margin-top:22px">
      <button class="btn-primary" style="width:auto;padding:12px 28px" id="save-tech">Enregistrer</button>
      <button class="btn-sm" id="cancel-tech-form" style="padding:12px 20px">Annuler</button>
    </div>
  </div>
  <!-- Liste -->
  <div class="tech-grid" id="tech-list">
    <div style="padding:40px;text-align:center;color:var(--mid);font-size:14px">Chargement…</div>
  </div>
</div>

<!-- ─── PAGES ─── -->
<div class="page" id="page-pages">
  <div class="topbar">
    <div class="page-title">Gestion des pages</div>
    <div class="topbar-right">
      <button class="btn-primary" style="width:auto;padding:10px 22px;font-size:14px" id="btn-new-page">+ Nouvelle page</button>
    </div>
  </div>
  <!-- Éditeur -->
  <div class="page-editor-wrap" id="page-editor-wrap" style="display:none">
    <h3 id="page-editor-title">Nouvelle page</h3>
    <input type="hidden" id="pe-id">
    <div class="pe-grid">
      <div class="cms-field" style="margin:0">
        <label>Titre de la page *</label>
        <input type="text" class="cms-input" id="pe-title" placeholder="Page influenceur">
      </div>
      <div class="cms-field" style="margin:0">
        <label>Slug (URL) *</label>
        <input type="text" class="cms-input" id="pe-slug" placeholder="influenceur" style="font-family:monospace">
        <span style="font-size:11px;color:var(--mid);margin-top:4px;display:block">Accessible sur : /page.php?slug=<span id="pe-slug-preview">…</span></span>
      </div>
      <div class="cms-field" style="margin:0">
        <label>Description SEO</label>
        <input type="text" class="cms-input" id="pe-meta" placeholder="Courte description pour Google">
      </div>
      <div class="cms-field" style="margin:0;display:flex;align-items:center;gap:10px;padding-top:20px">
        <label class="avail-toggle" style="margin:0">
          <input type="checkbox" id="pe-active">
          <span class="avail-slider"></span>
        </label>
        <span style="font-size:14px;font-weight:500">Page active (visible par les visiteurs)</span>
      </div>
      <div class="cms-field pe-full" style="margin:0">
        <label>Contenu de la page (HTML supporté)</label>
        <div class="pe-toolbar">
          <button class="pe-tool" data-cmd="bold"><b>G</b></button>
          <button class="pe-tool" data-cmd="italic"><i>I</i></button>
          <button class="pe-tool" data-cmd="h2">H2</button>
          <button class="pe-tool" data-cmd="h3">H3</button>
          <button class="pe-tool" data-cmd="ul">Liste</button>
          <button class="pe-tool" data-cmd="link">Lien</button>
          <button class="pe-tool" data-cmd="img">Image</button>
        </div>
        <textarea class="pe-editor" id="pe-content" placeholder="Écrivez votre contenu ici. Vous pouvez utiliser du HTML : <h2>, <p>, <ul>, <strong>, <a href=&quot;...&quot;>…"></textarea>
      </div>
    </div>
    <div style="display:flex;gap:10px;margin-top:22px">
      <button class="btn-primary" style="width:auto;padding:12px 28px" id="save-page">Enregistrer</button>
      <button class="btn-sm" id="cancel-page-editor" style="padding:12px 20px">Annuler</button>
    </div>
  </div>
  <!-- Liste des pages -->
  <div class="card">
    <div class="card-header"><span class="card-title">Pages créées</span></div>
    <div class="pages-list" id="pages-list" style="padding:0 24px 24px">
      <div style="padding:40px;text-align:center;color:var(--mid);font-size:14px">Chargement…</div>
    </div>
  </div>
</div>

</div><!-- /main -->

<!-- Toast -->
<div class="toast" id="toast"></div>

<!-- Modal confirmation -->
<div class="modal-overlay" id="modal">
  <div class="modal-box">
    <div class="modal-title" id="modal-title">Confirmer l'action</div>
    <div class="modal-sub" id="modal-sub"></div>
    <div class="modal-actions">
      <button class="btn-sm" id="modal-cancel">Annuler</button>
      <button class="btn-sm" id="modal-ok" style="background:var(--red);color:#fff;border-color:var(--red)">Confirmer</button>
    </div>
  </div>
</div>

<script>
const API = '../api.php';
const MONTHS = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

// ─── Utils ────────────────────────────────────────────────────────────────────
function qs(sel, ctx) { return (ctx||document).querySelector(sel); }
function qa(sel, ctx) { return (ctx||document).querySelectorAll(sel); }
function el(id) { return document.getElementById(id); }
function toast(msg, err) {
  const t = el('toast'); t.textContent = msg;
  t.className = 'toast show' + (err ? ' err' : '');
  setTimeout(() => t.className = 'toast', 3000);
}
function apiFetch(url, opts) {
  return fetch(API + url, opts).then(r => r.json());
}
function formatDateFr(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr + 'T12:00:00');
  return d.toLocaleDateString('fr-FR', { weekday:'short', day:'numeric', month:'short', year:'numeric' });
}
function statusBadge(s) {
  const map = { pending:'badge-pending', confirmed:'badge-confirmed', cancelled:'badge-cancelled' };
  const label = { pending:'En attente', confirmed:'Confirmé', cancelled:'Annulé' };
  return `<span class="badge ${map[s]||''}">${label[s]||s}</span>`;
}

// ─── Navigation ───────────────────────────────────────────────────────────────
let currentPage = 'dashboard';
qa('.sb-link').forEach(btn => {
  btn.addEventListener('click', () => {
    const page = btn.dataset.page;
    qa('.sb-link').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    qa('.page').forEach(p => p.classList.remove('active'));
    el('page-' + page).classList.add('active');
    currentPage = page;
    if (page === 'dashboard') loadDashboard();
    if (page === 'appointments') loadAppointments();
    if (page === 'calview') loadCalView();
    if (page === 'availability') loadAvailability();
    if (page === 'blocked') loadBlocked();
    if (page === 'settings') loadSettings();
  });
});

// ─── Modal ────────────────────────────────────────────────────────────────────
let modalCb = null;
function showModal(title, sub, cb, dangerLabel) {
  el('modal-title').textContent = title;
  el('modal-sub').textContent = sub;
  el('modal-ok').textContent = dangerLabel || 'Confirmer';
  modalCb = cb;
  el('modal').classList.add('open');
}
el('modal-cancel').addEventListener('click', () => el('modal').classList.remove('open'));
el('modal-ok').addEventListener('click', () => { el('modal').classList.remove('open'); if (modalCb) modalCb(); });

// ─── DASHBOARD ────────────────────────────────────────────────────────────────
async function loadDashboard() {
  const data = await apiFetch('?a=appointments&status=confirmed&date_from=' + new Date().toISOString().slice(0,10) + '&date_to=' + new Date(Date.now() + 14*864e5).toISOString().slice(0,10));
  if (data.stats) {
    el('st-total').textContent     = data.stats.total || 0;
    el('st-pending').textContent   = data.stats.pending || 0;
    el('st-confirmed').textContent = data.stats.confirmed || 0;
    el('st-today').textContent     = data.stats.today || 0;
  }
  const rows = data.appointments || [];
  if (!rows.length) {
    el('upcoming-list').innerHTML = '<div style="padding:40px;text-align:center;color:var(--mid);font-size:14px">Aucun rendez-vous confirmé dans les 14 prochains jours.</div>';
    return;
  }
  el('upcoming-list').innerHTML = `<table style="width:100%;border-collapse:collapse">
    <thead><tr>
      <th style="padding:12px 20px;font-size:11px;color:var(--mid);text-align:left;border-bottom:1px solid #e5e5ea">Client</th>
      <th style="padding:12px 20px;font-size:11px;color:var(--mid);text-align:left;border-bottom:1px solid #e5e5ea">Date & Heure</th>
      <th style="padding:12px 20px;font-size:11px;color:var(--mid);text-align:left;border-bottom:1px solid #e5e5ea">Ville</th>
    </tr></thead>
    <tbody>
    ${rows.map(r => `<tr>
      <td style="padding:14px 20px;font-size:14px;border-bottom:1px solid #f0f0f5"><strong>${esc(r.name)}</strong><br><span style="color:var(--mid);font-size:13px">${esc(r.email)}</span></td>
      <td style="padding:14px 20px;font-size:14px;border-bottom:1px solid #f0f0f5;white-space:nowrap">${formatDateFr(r.appt_date)}<br><strong style="color:var(--blue)">${r.appt_time.slice(0,5)}</strong></td>
      <td style="padding:14px 20px;font-size:14px;border-bottom:1px solid #f0f0f5;color:var(--mid)">${esc(r.city||'—')}</td>
    </tr>`).join('')}
    </tbody></table>`;
}

// ─── APPOINTMENTS ─────────────────────────────────────────────────────────────
let apptStatus = '';
async function loadAppointments(status) {
  if (status !== undefined) apptStatus = status;
  qa('#appt-filters .flt').forEach(b => b.classList.toggle('active', b.dataset.status === apptStatus));
  el('appt-tbody').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;color:var(--mid)">Chargement…</td></tr>';
  const data = await apiFetch('?a=appointments&status=' + apptStatus);
  const rows = data.appointments || [];
  if (!rows.length) {
    el('appt-tbody').innerHTML = '<tr><td colspan="6" style="text-align:center;padding:40px;color:var(--mid)">Aucun rendez-vous trouvé.</td></tr>';
    return;
  }
  el('appt-tbody').innerHTML = rows.map(r => `
    <tr data-id="${r.id}">
      <td><strong>${esc(r.name)}</strong><br><span style="color:var(--mid);font-size:12px">${esc(r.email)}</span><br><span style="color:var(--mid);font-size:12px">${esc(r.phone||'')}</span></td>
      <td style="white-space:nowrap"><strong>${r.appt_date}</strong><br><span style="color:var(--blue);font-weight:600">${r.appt_time.slice(0,5)}</span></td>
      <td>${esc(r.city||'—')}</td>
      <td>${esc(r.door_type||'—')}</td>
      <td>${statusBadge(r.status)}</td>
      <td style="white-space:nowrap;display:flex;gap:6px;flex-wrap:wrap">
        ${r.status !== 'confirmed'  ? `<button class="btn-sm btn-confirm" onclick="updateAppt(${r.id},'confirmed')">✓ Confirmer</button>` : ''}
        ${r.status !== 'cancelled'  ? `<button class="btn-sm btn-cancel"  onclick="updateAppt(${r.id},'cancelled')">✕ Annuler</button>` : ''}
        <button class="btn-sm btn-delete" onclick="deleteAppt(${r.id})">🗑</button>
      </td>
    </tr>`).join('');
}
qa('#appt-filters .flt').forEach(b => b.addEventListener('click', () => loadAppointments(b.dataset.status)));

async function updateAppt(id, status) {
  const label = status === 'confirmed' ? 'confirmer' : 'annuler';
  const name  = qs(`[data-id="${id}"] td strong`)?.textContent || '';
  showModal(`${status==='confirmed'?'Confirmer':'Annuler'} ce rendez-vous ?`,
    `Êtes-vous sûr de vouloir ${label} le rendez-vous de ${name} ? Un email sera envoyé automatiquement.`,
    async () => {
      await apiFetch('?a=appointment_update', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id, status}) });
      toast(status === 'confirmed' ? '✓ Rendez-vous confirmé — email envoyé' : '✕ Rendez-vous annulé — email envoyé');
      loadAppointments();
    }, status === 'confirmed' ? 'Confirmer' : 'Annuler');
}
async function deleteAppt(id) {
  showModal('Supprimer ce rendez-vous ?', 'Cette action est irréversible. Aucun email ne sera envoyé.', async () => {
    await apiFetch('?a=appointment_delete', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) });
    toast('Rendez-vous supprimé');
    loadAppointments();
  }, 'Supprimer');
}

// ─── CALENDRIER VISUEL ────────────────────────────────────────────────────────
let calvYear, calvMonth;
async function loadCalView() {
  if (!calvYear) { const n = new Date(); calvYear = n.getFullYear(); calvMonth = n.getMonth(); }
  renderCalView();
}
el('calv-prev').addEventListener('click', () => { calvMonth--; if (calvMonth < 0) { calvMonth=11; calvYear--; } renderCalView(); });
el('calv-next').addEventListener('click', () => { calvMonth++; if (calvMonth > 11) { calvMonth=0; calvYear++; } renderCalView(); });

async function renderCalView() {
  const label = `${MONTHS[calvMonth]} ${calvYear}`;
  el('calv-label').textContent = label;
  const monthKey = `${calvYear}-${String(calvMonth+1).padStart(2,'0')}`;
  const data = await apiFetch(`?a=appointments&date_from=${monthKey}-01&date_to=${monthKey}-31`);
  const appts = data.appointments || [];
  const byDate = {};
  appts.forEach(a => { if (!byDate[a.appt_date]) byDate[a.appt_date] = []; byDate[a.appt_date].push(a); });

  const firstDay = new Date(calvYear, calvMonth, 1).getDay();
  const offset = firstDay === 0 ? 6 : firstDay - 1;
  const daysInMonth = new Date(calvYear, calvMonth+1, 0).getDate();
  const todayStr = new Date().toISOString().slice(0,10);
  const DAYS = ['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'];

  let html = `<div class="cal-grid-v">${DAYS.map(d=>`<div class="cal-dow-v">${d}</div>`).join('')}`;
  for (let i=0;i<offset;i++) html += `<div class="cal-cell-v other-month"></div>`;
  for (let d=1;d<=daysInMonth;d++) {
    const ds = `${calvYear}-${String(calvMonth+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
    const isToday = ds === todayStr;
    const dayAppts = byDate[ds] || [];
    html += `<div class="cal-cell-v${isToday?' today':''}">
      <div class="cal-day-num${isToday?' today-num':''}">${d}</div>
      ${dayAppts.map(a=>`<div class="cal-appt-dot dot-${a.status}" title="${esc(a.name)}">${a.appt_time.slice(0,5)} ${esc(a.name)}</div>`).join('')}
    </div>`;
  }
  html += '</div>';
  el('calv-grid').innerHTML = html;
}

// ─── DISPONIBILITÉS ───────────────────────────────────────────────────────────
const JOURS = ['','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
async function loadAvailability() {
  const data = await apiFetch('?a=availability');
  const rules = data.rules || [];
  const map = {};
  rules.forEach(r => map[r.day_of_week] = r);

  let html = '';
  for (let d=1;d<=7;d++) {
    const r = map[d] || { day_of_week:d, open_time:'09:00:00', close_time:'18:00:00', is_active:0 };
    const active = !!+r.is_active;
    html += `<div class="avail-day">
      <label class="avail-toggle">
        <input type="checkbox" data-dow="${d}" class="avail-chk" ${active?'checked':''}>
        <span class="avail-slider"></span>
      </label>
      <span class="avail-label">${JOURS[d]}</span>
      <div class="avail-times">
        <span style="font-size:13px;color:var(--mid)">De</span>
        <input type="time" class="time-input avail-open" data-dow="${d}" value="${r.open_time.slice(0,5)}" ${active?'':'disabled'}>
        <span style="font-size:13px;color:var(--mid)">à</span>
        <input type="time" class="time-input avail-close" data-dow="${d}" value="${r.close_time.slice(0,5)}" ${active?'':'disabled'}>
      </div>
    </div>`;
  }
  el('avail-rows').innerHTML = html;

  qa('.avail-chk').forEach(chk => {
    chk.addEventListener('change', () => {
      const dow = chk.dataset.dow;
      qs(`.avail-open[data-dow="${dow}"]`).disabled  = !chk.checked;
      qs(`.avail-close[data-dow="${dow}"]`).disabled = !chk.checked;
    });
  });
}

el('save-avail').addEventListener('click', async () => {
  const rules = [];
  for (let d=1;d<=7;d++) {
    const chk   = qs(`.avail-chk[data-dow="${d}"]`);
    const open  = qs(`.avail-open[data-dow="${d}"]`);
    const close = qs(`.avail-close[data-dow="${d}"]`);
    rules.push({ day_of_week:d, open_time: open.value, close_time: close.value, is_active: chk.checked ? 1 : 0 });
  }
  const slot_duration = parseInt(el('slot-dur').value);
  const data = await apiFetch('?a=availability_save', {
    method:'POST', headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ rules, slot_duration })
  });
  toast(data.success ? '✓ Disponibilités enregistrées' : 'Erreur : ' + data.error, !data.success);
});

// ─── DATES BLOQUÉES ───────────────────────────────────────────────────────────
async function loadBlocked() {
  const data = await apiFetch('?a=blocked');
  const rows = data.blocked || [];
  if (!rows.length) { el('blocked-list').innerHTML = '<p style="padding:20px 0;font-size:14px;color:var(--mid)">Aucune date bloquée.</p>'; return; }
  el('blocked-list').innerHTML = rows.map(r => `
    <div class="blocked-item">
      <div style="flex:1">
        <div class="blocked-range">${r.start_date === r.end_date ? formatDateFr(r.start_date) : formatDateFr(r.start_date) + ' → ' + formatDateFr(r.end_date)}</div>
        ${r.reason ? `<div class="blocked-reason">${esc(r.reason)}</div>` : ''}
      </div>
      <button class="btn-sm btn-delete" onclick="removeBlocked(${r.id})">Supprimer</button>
    </div>`).join('');
}
el('add-blocked').addEventListener('click', async () => {
  const start = el('b-start').value, end = el('b-end').value, reason = el('b-reason').value.trim();
  if (!start || !end) return toast('Sélectionnez une période', true);
  if (end < start) return toast('La date de fin doit être après la date de début', true);
  const data = await apiFetch('?a=blocked_add', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({start_date:start, end_date:end, reason}) });
  if (data.success) { toast('✓ Période bloquée ajoutée'); loadBlocked(); el('b-start').value=el('b-end').value=el('b-reason').value=''; }
  else toast('Erreur : ' + data.error, true);
});
async function removeBlocked(id) {
  showModal('Supprimer cette période bloquée ?', 'Les créneaux seront à nouveau disponibles.', async () => {
    await apiFetch('?a=blocked_remove', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) });
    toast('✓ Période supprimée'); loadBlocked();
  });
}

// ─── PARAMÈTRES ───────────────────────────────────────────────────────────────
async function loadSettings() {
  const data = await apiFetch('?a=settings');
  if (data.admin_email !== undefined) el('s-email').value = data.admin_email;
}
el('save-settings').addEventListener('click', async () => {
  const payload = { admin_email: el('s-email').value.trim() };
  const pass = el('s-pass').value;
  if (pass) { if (pass.length < 8) return toast('Mot de passe min. 8 caractères', true); payload.new_password = pass; }
  const data = await apiFetch('?a=settings_save', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
  toast(data.success ? '✓ Paramètres enregistrés' : 'Erreur', !data.success);
  el('s-pass').value = '';
});
el('test-smtp').addEventListener('click', async () => {
  el('test-smtp').disabled = true; el('test-smtp').textContent = 'Envoi…';
  const res = el('smtp-result');
  try {
    const data = await apiFetch('?a=smtp_test', { method:'POST' });
    res.style.display='inline';
    if (data.success) { res.textContent='✓ Email envoyé à ' + data.to; res.style.color='var(--green)'; }
    else { res.textContent='✕ Échec de l\'envoi'; res.style.color='var(--red)'; }
  } catch { res.style.display='inline'; res.textContent='✕ Erreur réseau'; res.style.color='var(--red)'; }
  el('test-smtp').disabled=false; el('test-smtp').textContent='Envoyer un email de test';
});

// ─── MOBILE SIDEBAR ───────────────────────────────────────────────────────────
(function(){
  const sidebar = el('sidebar');
  const hamburger = el('hamburger');
  const overlay = el('sidebar-overlay');
  if (!hamburger) return;
  function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); }
  hamburger.addEventListener('click', () => {
    const open = sidebar.classList.toggle('open');
    overlay.classList.toggle('open', open);
  });
  overlay.addEventListener('click', closeSidebar);
  qa('.sb-link').forEach(btn => btn.addEventListener('click', () => {
    if (window.innerWidth <= 768) closeSidebar();
  }));
})();

// ─── NAVIGATION ÉTENDUE ───────────────────────────────────────────────────────
const pageLoaders = {
  content: loadContent, technicians: loadTechnicians, pages: loadPages
};
qa('.sb-link').forEach(btn => {
  btn.addEventListener('click', () => {
    const page = btn.dataset.page;
    if (pageLoaders[page]) pageLoaders[page]();
  });
});

// ─── CONTENU DU SITE ─────────────────────────────────────────────────────────
let cmsData = {};

async function loadContent() {
  cmsData = await apiFetch('?a=content_get');
  // Textes
  el('c-hero-eyebrow').value  = cmsData.hero_eyebrow || '';
  el('c-hero-title').value    = cmsData.hero_title || '';
  el('c-hero-highlight').value= cmsData.hero_title_highlight || '';
  el('c-hero-sub').value      = cmsData.hero_sub || '';
  el('c-cta-primary').value   = cmsData.hero_cta_primary || '';
  el('c-cta-secondary').value = cmsData.hero_cta_secondary || '';
  el('c-price').value         = cmsData.price || '';
  el('c-price-note').value    = cmsData.price_note || '';
  // Couleurs
  const primary = (cmsData.colors||{}).primary || '#0071e3';
  const hover   = (cmsData.colors||{}).primary_hover || '#0077ed';
  el('c-color-primary').value     = primary;
  el('c-color-primary-hex').value = primary;
  el('c-color-hover').value       = hover;
  el('c-color-hover-hex').value   = hover;
  // Listes
  renderFeatures(cmsData.features || []);
  renderReviews(cmsData.reviews || []);
  renderFaqList(cmsData.faq || []);
}

// Tabs
qa('.cms-tab').forEach(t => t.addEventListener('click', () => {
  qa('.cms-tab').forEach(x => x.classList.remove('active'));
  qa('.cms-panel').forEach(x => x.classList.remove('active'));
  t.classList.add('active');
  el(t.dataset.tab).classList.add('active');
}));

// Sync color picker ↔ text input
function syncColor(pickerId, hexId) {
  el(pickerId).addEventListener('input', () => el(hexId).value = el(pickerId).value);
  el(hexId).addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(el(hexId).value)) el(pickerId).value = el(hexId).value; });
}
syncColor('c-color-primary', 'c-color-primary-hex');
syncColor('c-color-hover',   'c-color-hover-hex');

// Save textes
el('save-content-texts').addEventListener('click', async () => {
  const payload = {
    hero_eyebrow: el('c-hero-eyebrow').value.trim(),
    hero_title:   el('c-hero-title').value.trim(),
    hero_title_highlight: el('c-hero-highlight').value.trim(),
    hero_sub:     el('c-hero-sub').value.trim(),
    hero_cta_primary:   el('c-cta-primary').value.trim(),
    hero_cta_secondary: el('c-cta-secondary').value.trim(),
    price:      el('c-price').value.trim(),
    price_note: el('c-price-note').value.trim(),
  };
  const d = await apiFetch('?a=content_save', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
  const msg = el('save-texts-msg');
  msg.style.display = 'inline';
  if (d.success) { msg.textContent = '✓ Enregistré'; msg.style.color = 'var(--green)'; }
  else { msg.textContent = 'Erreur'; msg.style.color = 'var(--red)'; }
  setTimeout(() => msg.style.display = 'none', 3000);
});

// Save couleurs
el('save-content-colors').addEventListener('click', async () => {
  const payload = { colors: { primary: el('c-color-primary-hex').value, primary_hover: el('c-color-hover-hex').value } };
  const d = await apiFetch('?a=content_save', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
  toast(d.success ? '✓ Couleurs enregistrées' : 'Erreur', !d.success);
});

// ── FEATURES ──
function renderFeatures(arr) {
  const ICONS = ['📷','🤖','📱','🔐','☁️','🔋','🛡️','⚡','💬','🏆','📡','🔑'];
  const COLORS = ['ic-blue','ic-purple','ic-green','ic-orange','ic-teal','ic-red'];
  el('features-list').innerHTML = arr.map((f,i) => `
    <div class="cms-array-item" data-idx="${i}">
      <div class="item-head">
        <span class="item-title">#${i+1} ${esc(f.title||'Fonctionnalité')}</span>
        <div style="display:flex;gap:6px">
          ${i>0?`<button class="btn-sm" onclick="moveFeat(${i},-1)">↑</button>`:''}
          ${i<arr.length-1?`<button class="btn-sm" onclick="moveFeat(${i},1)">↓</button>`:''}
          <button class="btn-sm btn-delete" onclick="removeFeat(${i})">Supprimer</button>
        </div>
      </div>
      <div class="cms-item-grid">
        <div class="cms-field" style="margin:0">
          <label>Icône</label>
          <select class="cms-input feat-icon" data-idx="${i}">
            ${ICONS.map(ic=>`<option ${f.icon===ic?'selected':''}>${ic}</option>`).join('')}
          </select>
        </div>
        <div class="cms-field" style="margin:0">
          <label>Couleur fond</label>
          <select class="cms-input feat-color" data-idx="${i}">
            ${COLORS.map(c=>`<option value="${c}" ${f.color===c?'selected':''}>${c}</option>`).join('')}
          </select>
        </div>
        <div class="cms-field full" style="margin:0">
          <label>Titre</label>
          <input type="text" class="cms-input feat-title" data-idx="${i}" value="${esc(f.title||'')}">
        </div>
        <div class="cms-field full" style="margin:0">
          <label>Description</label>
          <textarea class="cms-input feat-desc" data-idx="${i}" style="min-height:70px">${esc(f.desc||'')}</textarea>
        </div>
        <div class="cms-field full" style="margin:0">
          <label>Pill / Badge</label>
          <input type="text" class="cms-input feat-pill" data-idx="${i}" value="${esc(f.pill||'')}">
        </div>
      </div>
    </div>`).join('');
}
function collectFeatures() {
  return Array.from(qa('.cms-array-item', el('features-list'))).map(item => {
    const i = item.dataset.idx;
    return {
      icon: qs(`.feat-icon[data-idx="${i}"]`, item).value,
      color: qs(`.feat-color[data-idx="${i}"]`, item).value,
      title: qs(`.feat-title[data-idx="${i}"]`, item).value.trim(),
      desc: qs(`.feat-desc[data-idx="${i}"]`, item).value.trim(),
      pill: qs(`.feat-pill[data-idx="${i}"]`, item).value.trim(),
    };
  });
}
window.moveFeat = function(idx, dir) {
  const arr = collectFeatures(); const tmp = arr[idx]; arr[idx] = arr[idx+dir]; arr[idx+dir] = tmp; renderFeatures(arr);
};
window.removeFeat = function(idx) {
  const arr = collectFeatures(); arr.splice(idx,1); renderFeatures(arr);
};
el('add-feature').addEventListener('click', () => {
  const arr = collectFeatures();
  arr.push({ icon:'📷', color:'ic-blue', title:'Nouvelle fonctionnalité', desc:'Description à modifier.', pill:'' });
  renderFeatures(arr);
});
el('save-content-features').addEventListener('click', async () => {
  const d = await apiFetch('?a=content_save', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ features: collectFeatures() }) });
  toast(d.success ? '✓ Fonctionnalités enregistrées' : 'Erreur', !d.success);
});

// ── REVIEWS ──
function renderReviews(arr) {
  el('reviews-list').innerHTML = arr.map((r,i) => `
    <div class="cms-array-item" data-ridx="${i}">
      <div class="item-head">
        <span class="item-title">#${i+1} ${esc(r.name||'Avis')}</span>
        <div style="display:flex;gap:6px">
          ${i>0?`<button class="btn-sm" onclick="moveRv(${i},-1)">↑</button>`:''}
          ${i<arr.length-1?`<button class="btn-sm" onclick="moveRv(${i},1)">↓</button>`:''}
          <button class="btn-sm btn-delete" onclick="removeRv(${i})">Supprimer</button>
        </div>
      </div>
      <div class="cms-item-grid">
        <div class="cms-field" style="margin:0">
          <label>Nom</label>
          <input type="text" class="cms-input rv-name" data-ridx="${i}" value="${esc(r.name||'')}">
        </div>
        <div class="cms-field" style="margin:0">
          <label>Ville / Localisation</label>
          <input type="text" class="cms-input rv-location" data-ridx="${i}" value="${esc(r.location||'')}">
        </div>
        <div class="cms-field" style="margin:0">
          <label>Initiales (avatar)</label>
          <input type="text" class="cms-input rv-initials" data-ridx="${i}" value="${esc(r.initials||'')}" maxlength="3" style="width:90px">
        </div>
        <div class="cms-field" style="margin:0">
          <label>Couleur avatar</label>
          <input type="color" class="rv-color" data-ridx="${i}" value="${r.color||'#0071e3'}" style="width:44px;height:44px;border:1px solid #d2d2d7;border-radius:8px;cursor:pointer;padding:2px">
        </div>
        <div class="cms-field" style="margin:0">
          <label>Note (1–5)</label>
          <select class="cms-input rv-stars" data-ridx="${i}">
            ${[1,2,3,4,5].map(n=>`<option value="${n}" ${r.stars==n?'selected':''}>${n} étoile${n>1?'s':''}</option>`).join('')}
          </select>
        </div>
        <div class="cms-field" style="margin:0">
          <label>Date affichée</label>
          <input type="text" class="cms-input rv-date" data-ridx="${i}" value="${esc(r.date||'')}" placeholder="il y a 3 mois">
        </div>
        <div class="cms-field full" style="margin:0">
          <label>Texte du témoignage</label>
          <textarea class="cms-input rv-text" data-ridx="${i}" style="min-height:80px">${esc(r.text||'')}</textarea>
        </div>
      </div>
    </div>`).join('');
}
function collectReviews() {
  return Array.from(qa('.cms-array-item', el('reviews-list'))).map(item => {
    const i = item.dataset.ridx;
    return {
      name: qs(`.rv-name[data-ridx="${i}"]`, item).value.trim(),
      location: qs(`.rv-location[data-ridx="${i}"]`, item).value.trim(),
      initials: qs(`.rv-initials[data-ridx="${i}"]`, item).value.trim(),
      color: qs(`.rv-color[data-ridx="${i}"]`, item).value,
      stars: parseInt(qs(`.rv-stars[data-ridx="${i}"]`, item).value),
      date: qs(`.rv-date[data-ridx="${i}"]`, item).value.trim(),
      text: qs(`.rv-text[data-ridx="${i}"]`, item).value.trim(),
    };
  });
}
window.moveRv = function(idx, dir) { const arr=collectReviews(); const tmp=arr[idx]; arr[idx]=arr[idx+dir]; arr[idx+dir]=tmp; renderReviews(arr); };
window.removeRv = function(idx) { const arr=collectReviews(); arr.splice(idx,1); renderReviews(arr); };
el('add-review').addEventListener('click', () => {
  const arr=collectReviews();
  arr.push({ name:'Nouveau client', location:'Paris', initials:'NC', color:'#0071e3', stars:5, date:'il y a 1 mois', text:'Témoignage à modifier.' });
  renderReviews(arr);
});
el('save-content-reviews').addEventListener('click', async () => {
  const d = await apiFetch('?a=content_save', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ reviews: collectReviews() }) });
  toast(d.success ? '✓ Avis enregistrés' : 'Erreur', !d.success);
});

// ── FAQ ──
function renderFaqList(arr) {
  el('faq-list').innerHTML = arr.map((f,i) => `
    <div class="cms-array-item" data-fidx="${i}">
      <div class="item-head">
        <span class="item-title">#${i+1} ${esc(f.q||'Question')}</span>
        <div style="display:flex;gap:6px">
          ${i>0?`<button class="btn-sm" onclick="moveFaq(${i},-1)">↑</button>`:''}
          ${i<arr.length-1?`<button class="btn-sm" onclick="moveFaq(${i},1)">↓</button>`:''}
          <button class="btn-sm btn-delete" onclick="removeFaq(${i})">Supprimer</button>
        </div>
      </div>
      <div class="cms-field" style="margin:0 0 12px">
        <label>Question</label>
        <input type="text" class="cms-input faq-q" data-fidx="${i}" value="${esc(f.q||'')}">
      </div>
      <div class="cms-field" style="margin:0">
        <label>Réponse</label>
        <textarea class="cms-input faq-a" data-fidx="${i}" style="min-height:80px">${esc(f.a||'')}</textarea>
      </div>
    </div>`).join('');
}
function collectFaq() {
  return Array.from(qa('.cms-array-item', el('faq-list'))).map(item => {
    const i = item.dataset.fidx;
    return { q: qs(`.faq-q[data-fidx="${i}"]`, item).value.trim(), a: qs(`.faq-a[data-fidx="${i}"]`, item).value.trim() };
  });
}
window.moveFaq = function(idx, dir) { const arr=collectFaq(); const tmp=arr[idx]; arr[idx]=arr[idx+dir]; arr[idx+dir]=tmp; renderFaqList(arr); };
window.removeFaq = function(idx) { const arr=collectFaq(); arr.splice(idx,1); renderFaqList(arr); };
el('add-faq').addEventListener('click', () => { const arr=collectFaq(); arr.push({q:'Nouvelle question ?', a:'Réponse à compléter.'}); renderFaqList(arr); });
el('save-content-faq').addEventListener('click', async () => {
  const d = await apiFetch('?a=content_save', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ faq: collectFaq() }) });
  toast(d.success ? '✓ FAQ enregistrée' : 'Erreur', !d.success);
});

// ─── TECHNICIENS ─────────────────────────────────────────────────────────────
async function loadTechnicians() {
  el('tech-form-wrap').style.display = 'none';
  const data = await apiFetch('?a=technicians');
  const techs = data.technicians || [];
  if (!techs.length) {
    el('tech-list').innerHTML = '<div style="padding:40px;text-align:center;color:var(--mid);font-size:14px;grid-column:1/-1">Aucun technicien. Cliquez sur « Nouveau technicien » pour commencer.</div>';
    return;
  }
  el('tech-list').innerHTML = techs.map(t => `
    <div class="tech-card">
      <span class="${t.is_active ? 'tech-badge-active' : 'tech-badge-inactive'}">${t.is_active ? 'Actif' : 'Désactivé'}</span>
      <div class="tc-name">${esc(t.name)}</div>
      <div class="tc-city">${esc(t.city)}</div>
      ${t.zones ? `<div class="tc-meta" style="margin-bottom:6px">📍 ${esc(t.zones)}</div>` : ''}
      ${t.email ? `<div class="tc-meta">✉️ ${esc(t.email)}</div>` : ''}
      ${t.phone ? `<div class="tc-meta">📞 ${esc(t.phone)}</div>` : ''}
      ${t.note  ? `<div class="tc-meta" style="margin-top:8px;font-style:italic;color:var(--mid)">${esc(t.note)}</div>` : ''}
      <div class="tc-actions">
        <button class="btn-sm" onclick="editTech(${t.id})">Modifier</button>
        <button class="btn-sm btn-delete" onclick="deleteTech(${t.id})">Supprimer</button>
      </div>
    </div>`).join('');
}
let techsCache = [];
el('btn-add-tech').addEventListener('click', () => { openTechForm(null); });
function openTechForm(t) {
  el('tech-form-wrap').style.display = 'block';
  el('tech-form-title').textContent = t ? 'Modifier le technicien' : 'Nouveau technicien';
  el('tech-id').value     = t ? t.id : '';
  el('tech-name').value   = t ? t.name : '';
  el('tech-city').value   = t ? t.city : '';
  el('tech-email').value  = t ? (t.email||'') : '';
  el('tech-phone').value  = t ? (t.phone||'') : '';
  el('tech-zones').value  = t ? (t.zones||'') : '';
  el('tech-note').value   = t ? (t.note||'') : '';
  el('tech-active').checked = t ? !!t.is_active : true;
  el('tech-form-wrap').scrollIntoView({ behavior:'smooth' });
}
window.editTech = async function(id) {
  const data = await apiFetch('?a=technicians');
  const t = (data.technicians||[]).find(x => x.id===id);
  if (t) openTechForm(t);
};
window.deleteTech = function(id) {
  showModal('Supprimer ce technicien ?', 'Cette action est irréversible.', async () => {
    await apiFetch('?a=technician_delete', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id}) });
    toast('Technicien supprimé'); loadTechnicians();
  }, 'Supprimer');
};
el('cancel-tech-form').addEventListener('click', () => el('tech-form-wrap').style.display='none');
el('save-tech').addEventListener('click', async () => {
  const payload = {
    id:        el('tech-id').value ? parseInt(el('tech-id').value) : 0,
    name:      el('tech-name').value.trim(),
    city:      el('tech-city').value.trim(),
    email:     el('tech-email').value.trim(),
    phone:     el('tech-phone').value.trim(),
    zones:     el('tech-zones').value.trim(),
    note:      el('tech-note').value.trim(),
    is_active: el('tech-active').checked,
  };
  if (!payload.name || !payload.city) return toast('Nom et ville requis', true);
  const d = await apiFetch('?a=technician_save', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
  if (d.success) { toast('✓ Technicien enregistré'); loadTechnicians(); el('tech-form-wrap').style.display='none'; }
  else toast('Erreur : ' + (d.error||''), true);
});

// ─── PAGES ───────────────────────────────────────────────────────────────────
async function loadPages() {
  el('page-editor-wrap').style.display = 'none';
  const data = await apiFetch('?a=pages');
  const pages = data.pages || [];
  if (!pages.length) {
    el('pages-list').innerHTML = '<div style="padding:40px;text-align:center;color:var(--mid);font-size:14px">Aucune page. Cliquez sur « Nouvelle page » pour commencer.</div>';
    return;
  }
  el('pages-list').innerHTML = pages.map(p => `
    <div class="page-item">
      <div class="pi-info">
        <div class="pi-title">${esc(p.title)} ${p.is_active ? '<span class="badge badge-confirmed">Active</span>' : '<span class="badge" style="background:#f0f0f5;color:var(--mid)">Désactivée</span>'}</div>
        <div class="pi-slug">/page.php?slug=${esc(p.slug)}</div>
      </div>
      <div class="pi-actions">
        <a href="../page.php?slug=${esc(p.slug)}" target="_blank" class="btn-sm" style="text-decoration:none">Aperçu ↗</a>
        <button class="btn-sm" onclick="editPage(${p.id})">Modifier</button>
        <button class="btn-sm" onclick="duplicatePage(${p.id})">Dupliquer</button>
        <button class="btn-sm btn-delete" onclick="deletePage(${p.id})">Supprimer</button>
      </div>
    </div>`).join('');
}
el('btn-new-page').addEventListener('click', () => openPageEditor(null));
function openPageEditor(p) {
  el('page-editor-wrap').style.display = 'block';
  el('page-editor-title').textContent = p ? 'Modifier la page' : 'Nouvelle page';
  el('pe-id').value      = p ? p.id : '';
  el('pe-title').value   = p ? p.title : '';
  el('pe-slug').value    = p ? p.slug : '';
  el('pe-meta').value    = p ? (p.meta_desc||'') : '';
  el('pe-active').checked = p ? !!p.is_active : false;
  el('pe-content').value = p ? (p.content||'') : '';
  el('pe-slug-preview').textContent = p ? p.slug : '…';
  el('page-editor-wrap').scrollIntoView({ behavior:'smooth' });
}
el('pe-slug').addEventListener('input', () => {
  const val = el('pe-slug').value.toLowerCase().replace(/[^a-z0-9_-]/g,'');
  el('pe-slug').value = val;
  el('pe-slug-preview').textContent = val || '…';
});
el('pe-title').addEventListener('input', () => {
  if (!el('pe-id').value) { // Only auto-slugify for new pages
    const slug = el('pe-title').value.toLowerCase()
      .normalize('NFD').replace(/[̀-ͯ]/g,'')
      .replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
    el('pe-slug').value = slug;
    el('pe-slug-preview').textContent = slug || '…';
  }
});
// Toolbar
qa('.pe-tool').forEach(btn => {
  btn.addEventListener('click', () => {
    const ta = el('pe-content');
    const cmd = btn.dataset.cmd;
    const sel = ta.value.substring(ta.selectionStart, ta.selectionEnd);
    const map = {
      bold:   `<strong>${sel||'texte gras'}</strong>`,
      italic: `<em>${sel||'texte italique'}</em>`,
      h2:     `<h2>${sel||'Titre'}</h2>`,
      h3:     `<h3>${sel||'Sous-titre'}</h3>`,
      ul:     `<ul>\n  <li>${sel||'Élément'}</li>\n</ul>`,
      link:   `<a href="URL">${sel||'Texte du lien'}</a>`,
      img:    `<img src="URL" alt="${sel||'description'}">`,
    };
    const insert = map[cmd] || sel;
    const start = ta.selectionStart, end = ta.selectionEnd;
    ta.value = ta.value.substring(0,start) + insert + ta.value.substring(end);
    ta.focus(); ta.selectionStart = ta.selectionEnd = start + insert.length;
  });
});
el('cancel-page-editor').addEventListener('click', () => el('page-editor-wrap').style.display='none');
el('save-page').addEventListener('click', async () => {
  const payload = {
    id:       el('pe-id').value ? parseInt(el('pe-id').value) : 0,
    title:    el('pe-title').value.trim(),
    slug:     el('pe-slug').value.trim(),
    meta_desc: el('pe-meta').value.trim(),
    is_active: el('pe-active').checked,
    content:  el('pe-content').value,
  };
  if (!payload.title || !payload.slug) return toast('Titre et slug requis', true);
  const d = await apiFetch('?a=page_save', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
  if (d.success) { toast('✓ Page enregistrée'); loadPages(); el('page-editor-wrap').style.display='none'; }
  else toast('Erreur : ' + (d.error||''), true);
});
window.editPage = async function(id) {
  const data = await apiFetch('?a=pages');
  const p = (data.pages||[]).find(x => x.id===id);
  if (p) openPageEditor(p);
};
window.duplicatePage = function(id) {
  showModal('Dupliquer cette page ?', 'Une copie désactivée sera créée avec le slug "-copie".',
    async () => {
      const d = await apiFetch('?a=page_duplicate', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id}) });
      if (d.success) { toast('✓ Page dupliquée'); loadPages(); }
      else toast('Erreur', true);
    }, 'Dupliquer');
};
window.deletePage = function(id) {
  showModal('Supprimer cette page ?', 'Cette action est irréversible.',
    async () => {
      await apiFetch('?a=page_delete', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id}) });
      toast('Page supprimée'); loadPages();
    }, 'Supprimer');
};

// ─── INIT ─────────────────────────────────────────────────────────────────────
function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

loadDashboard();
setInterval(() => { if (currentPage === 'dashboard') loadDashboard(); }, 30000);
</script>

<?php endif; ?>
</body>
</html>
