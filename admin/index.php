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

/* ─── RESPONSIVE ─── */
@media(max-width:768px){
  .sidebar { transform:translateX(-100%);transition:transform .25s; }
  .sidebar.open { transform:translateX(0); }
  .main { margin-left:0; }
  .stats-row { grid-template-columns:repeat(2,1fr); }
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

<!-- ─── DATES BLOQUÉES ─── */
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
  const data = await apiFetch('?a=appointments&status=all');
  if (data.stats) el('s-email').value = '<?= htmlspecialchars(ADMIN_EMAIL) ?>';
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

// ─── Helpers ──────────────────────────────────────────────────────────────────
function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Init
loadDashboard();
loadAvailability();
</script>

<?php endif; ?>
</body>
</html>
