<?php
require_once __DIR__ . '/config.php';
session_start();

$slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($_GET['slug'] ?? ''));
if (!$slug) { http_response_code(404); die('Page introuvable'); }

$pages = jdb_read(JSON_PAGES);
$page = null;
foreach ($pages as $pg) {
    if ($pg['slug'] === $slug) { $page = $pg; break; }
}

if (!$page || (!$page['is_active'] && !is_admin())) {
    http_response_code(404);
    die('<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Page introuvable</title>
<style>body{font-family:-apple-system,sans-serif;text-align:center;padding:80px 24px;color:#1d1d1f}h1{font-size:28px;font-weight:700}a{color:#0071e3}</style>
</head><body><h1>404 — Page introuvable</h1><p><a href="/">← Retour à l\'accueil</a></p></body></html>');
}

$title = htmlspecialchars($page['title']);
$meta  = htmlspecialchars($page['meta_desc'] ?? '');
$content = $page['content'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $title ?> — Portaye</title>
<?php if ($meta): ?><meta name="description" content="<?= $meta ?>"><?php endif; ?>
<link rel="icon" type="image/svg+xml" href="/favicon.svg">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{--blue:#0071e3;--black:#1d1d1f;--mid:#6e6e73;--light:#f5f5f7;--sf:-apple-system,'Helvetica Neue',sans-serif}
body{font-family:var(--sf);color:var(--black);background:#fff;-webkit-font-smoothing:antialiased}
nav{position:sticky;top:0;z-index:100;background:rgba(255,255,255,.9);backdrop-filter:blur(20px);border-bottom:1px solid rgba(0,0,0,.08);height:52px;display:flex;align-items:center;padding:0 24px;gap:20px}
.nav-logo{font-size:20px;font-weight:600;text-decoration:none;color:var(--black);flex:1}
nav a{font-size:13px;color:var(--black);text-decoration:none;opacity:.8}nav a:hover{opacity:1}
.page-wrap{max-width:860px;margin:0 auto;padding:60px 24px 80px}
.page-title{font-size:clamp(28px,5vw,48px);font-weight:700;letter-spacing:-.02em;margin-bottom:32px}
.page-content{font-size:17px;line-height:1.75;color:var(--black)}
.page-content h1,.page-content h2,.page-content h3{font-weight:700;letter-spacing:-.01em;margin:32px 0 12px}
.page-content h2{font-size:28px}.page-content h3{font-size:20px}
.page-content p{margin-bottom:16px;color:var(--mid)}
.page-content ul,.page-content ol{margin:0 0 16px 24px;color:var(--mid)}
.page-content a{color:var(--blue)}
.page-content img{max-width:100%;border-radius:12px;margin:16px 0}
.page-content strong{color:var(--black)}
<?php if (!$page['is_active']): ?>
.preview-bar{background:#ff9500;color:#fff;text-align:center;padding:10px;font-size:13px;font-weight:500}
<?php endif; ?>
</style>
</head>
<body>
<?php if (!$page['is_active']): ?>
<div class="preview-bar">Aperçu — cette page est désactivée (non visible par les visiteurs)</div>
<?php endif; ?>
<nav>
  <a href="/" class="nav-logo">Portaye</a>
  <a href="/#features">Produit</a>
  <a href="/#booking" style="color:var(--blue)">Réserver</a>
</nav>
<div class="page-wrap">
  <h1 class="page-title"><?= $title ?></h1>
  <div class="page-content"><?= $content ?></div>
</div>
</body>
</html>
