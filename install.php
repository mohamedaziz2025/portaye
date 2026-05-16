<?php
/**
 * install.php — Initialise les fichiers JSON de données.
 * À exécuter une seule fois après upload, puis supprimer ce fichier.
 */
require_once __DIR__ . '/config.php';

$errors = [];

// Créer le dossier data/
if (!is_dir(DATA_DIR)) {
    if (!mkdir(DATA_DIR, 0750, true)) {
        $errors[] = 'Impossible de créer le dossier data/';
    }
}

// Créer .htaccess pour protéger data/
$htaccess = DATA_DIR . '/.htaccess';
if (!file_exists($htaccess)) {
    $htContent = "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
               . "<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n";
    file_put_contents($htaccess, $htContent);
}

$defaultPassword = 'portaye2025';
$passwordHash    = password_hash($defaultPassword, PASSWORD_DEFAULT);

$existingSettings = jdb_read(JSON_SETTINGS);
$needsPasswordReset = empty($existingSettings) || str_contains($existingSettings['admin_password'] ?? '', 'PLACEHOLDER');

if ($needsPasswordReset) {
    jdb_write(JSON_SETTINGS, array_merge([
        'admin_username' => ADMIN_USERNAME,
        'admin_email'    => ADMIN_EMAIL,
        'slot_duration'  => (string)SLOT_DURATION,
        'advance_days'   => (string)ADVANCE_DAYS_MAX,
        'min_hours'      => (string)MIN_BOOKING_HOURS,
    ], $existingSettings, ['admin_password' => $passwordHash]));
}

if (!file_exists(JSON_AVAILABILITY)) {
    $rules = [];
    for ($day = 1; $day <= 6; $day++) {
        $rules[] = ['id' => $day, 'day_of_week' => $day, 'open_time' => '09:00:00', 'close_time' => '18:00:00', 'is_active' => 1];
    }
    $rules[] = ['id' => 7, 'day_of_week' => 7, 'open_time' => '09:00:00', 'close_time' => '18:00:00', 'is_active' => 0];
    jdb_write(JSON_AVAILABILITY, $rules);
}

if (!file_exists(JSON_APPOINTMENTS)) {
    jdb_write(JSON_APPOINTMENTS, []);
}

if (!file_exists(JSON_BLOCKED)) {
    jdb_write(JSON_BLOCKED, []);
}

?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Installation Portaye</title>
<style>
body{font-family:-apple-system,sans-serif;max-width:600px;margin:60px auto;padding:0 24px;color:#1d1d1f}
h1{font-size:26px}
.ok{color:#1a8a1a}
.err{color:#d00}
.box{background:#f5f5f7;border-radius:12px;padding:20px 24px;margin-top:24px;font-size:15px}
code{background:#e8e8ed;padding:2px 6px;border-radius:4px}
.warn{background:#fff3cd;border-radius:8px;padding:12px 16px;margin-top:16px;font-size:14px}
</style>
</head>
<body>
<h1>Installation Portaye</h1>
<?php if ($errors): ?>
    <?php foreach ($errors as $e): ?><p class="err">&#10007; <?= htmlspecialchars($e) ?></p><?php endforeach ?>
<?php else: ?>
    <p class="ok">&#10003; Fichiers JSON créés dans <code>data/</code></p>
    <div class="box">
        <strong>Connexion admin</strong><br><br>
        URL&nbsp;: <code><?= SITE_URL ?>/admin/</code><br>
        Identifiant&nbsp;: <code><?= ADMIN_USERNAME ?></code><br>
        Mot de passe&nbsp;: <code><?= htmlspecialchars($defaultPassword) ?></code><br><br>
        &#9888; Changez ce mot de passe dès la première connexion.
    </div>
    <div class="warn">&#128274; <strong>Supprimez ce fichier</strong> après installation&nbsp;: <code>install.php</code></div>
<?php endif ?>
</body>
</html>
