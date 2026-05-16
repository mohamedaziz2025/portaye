<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Installation Portaye</title>
<style>
body{font-family:-apple-system,sans-serif;max-width:640px;margin:60px auto;padding:0 24px;color:#1d1d1f}
h1{font-size:28px;font-weight:700;margin-bottom:8px}
.card{background:#f5f5f7;border-radius:12px;padding:24px;margin:16px 0}
.ok{color:#30d158;font-weight:600} .err{color:#ff3b30;font-weight:600}
pre{background:#1c1c1e;color:#30d158;padding:16px;border-radius:8px;font-size:13px;overflow:auto}
input{width:100%;padding:10px 14px;border:1px solid #d2d2d7;border-radius:8px;font-size:15px;
      font-family:inherit;margin-top:6px;box-sizing:border-box}
label{font-size:13px;font-weight:500;color:#6e6e73;display:block;margin-top:14px}
button{margin-top:20px;background:#0071e3;color:#fff;border:none;padding:12px 28px;
       border-radius:980px;font-size:16px;cursor:pointer;font-family:inherit}
button:hover{background:#0077ed}
.warn{background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:12px 16px;
      font-size:14px;margin-top:16px;color:#856404}
</style>
</head>
<body>
<?php
require_once __DIR__ . '/config.php';

$step = $_POST['step'] ?? 'form';
$errors = [];
$success = [];

if ($step === 'install') {
    $admin_pass = trim($_POST['admin_pass'] ?? '');
    $admin_user = trim($_POST['admin_user'] ?? 'admin');
    $admin_email = trim($_POST['admin_email'] ?? '');

    if (strlen($admin_pass) < 8) $errors[] = 'Le mot de passe doit faire au moins 8 caractères.';
    if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email admin invalide.';

    if (empty($errors)) {
        try {
            $pdo = db();

            $pdo->exec("CREATE TABLE IF NOT EXISTS `availability_rules` (
                `id`           INT AUTO_INCREMENT PRIMARY KEY,
                `day_of_week`  TINYINT NOT NULL COMMENT '1=Lundi … 7=Dimanche',
                `open_time`    TIME NOT NULL,
                `close_time`   TIME NOT NULL,
                `is_active`    TINYINT(1) NOT NULL DEFAULT 1,
                UNIQUE KEY `uq_day` (`day_of_week`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $success[] = 'Table availability_rules ✓';

            $pdo->exec("CREATE TABLE IF NOT EXISTS `blocked_dates` (
                `id`         INT AUTO_INCREMENT PRIMARY KEY,
                `start_date` DATE NOT NULL,
                `end_date`   DATE NOT NULL,
                `reason`     VARCHAR(255) DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $success[] = 'Table blocked_dates ✓';

            $pdo->exec("CREATE TABLE IF NOT EXISTS `appointments` (
                `id`               INT AUTO_INCREMENT PRIMARY KEY,
                `name`             VARCHAR(255) NOT NULL,
                `email`            VARCHAR(255) NOT NULL,
                `phone`            VARCHAR(50)  DEFAULT NULL,
                `city`             VARCHAR(255) DEFAULT NULL,
                `door_type`        VARCHAR(50)  DEFAULT NULL,
                `appt_date`        DATE NOT NULL,
                `appt_time`        TIME NOT NULL,
                `status`           ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
                `cancel_token`     VARCHAR(64) NOT NULL,
                `admin_note`       TEXT DEFAULT NULL,
                `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_date` (`appt_date`),
                KEY `idx_status` (`status`),
                KEY `idx_token` (`cancel_token`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $success[] = 'Table appointments ✓';

            $pdo->exec("CREATE TABLE IF NOT EXISTS `settings` (
                `k` VARCHAR(64) PRIMARY KEY,
                `v` TEXT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $success[] = 'Table settings ✓';

            // Valeurs par défaut
            $defaults = [
                'slot_duration'   => '60',
                'advance_days'    => '60',
                'min_hours'       => '4',
                'admin_username'  => $admin_user,
                'admin_password'  => password_hash($admin_pass, PASSWORD_DEFAULT),
                'admin_email'     => $admin_email,
            ];
            $stmt = $pdo->prepare("INSERT INTO settings (k, v) VALUES (?, ?) ON DUPLICATE KEY UPDATE v = VALUES(v)");
            foreach ($defaults as $k => $v) $stmt->execute([$k, $v]);
            $success[] = 'Compte admin configuré ✓';

            // Disponibilités par défaut (Lun-Ven 9h-18h)
            $stmt = $pdo->prepare("INSERT IGNORE INTO availability_rules (day_of_week, open_time, close_time, is_active) VALUES (?,?,?,?)");
            for ($d = 1; $d <= 5; $d++) $stmt->execute([$d, '09:00:00', '18:00:00', 1]);
            $success[] = 'Disponibilités par défaut (Lun–Ven 9h–18h) ✓';

        } catch (Exception $e) {
            $errors[] = 'Erreur base de données : ' . $e->getMessage();
        }
    }
}
?>

<h1>⚙️ Installation Portaye</h1>
<p style="color:#6e6e73">Ce script crée les tables MySQL et configure votre compte administrateur.</p>

<?php if (!empty($errors)): ?>
<div class="card" style="background:#fff0f0">
    <?php foreach ($errors as $e): ?><p class="err">✗ <?= htmlspecialchars($e) ?></p><?php endforeach ?>
</div>
<?php endif ?>

<?php if (!empty($success) && empty($errors)): ?>
<div class="card">
    <?php foreach ($success as $s): ?><p class="ok">✓ <?= htmlspecialchars($s) ?></p><?php endforeach ?>
    <p style="margin-top:16px;font-weight:500">Installation terminée ! Connectez-vous :</p>
    <pre>URL admin : <?= SITE_URL ?>/admin/
Identifiant : <?= htmlspecialchars($_POST['admin_user'] ?? 'admin') ?>
Mot de passe : (celui saisi ci-dessous)</pre>
    <div class="warn">⚠️ Supprimez ce fichier <strong>install.php</strong> après installation.</div>
</div>

<?php else: ?>
<form method="POST" class="card">
    <input type="hidden" name="step" value="install">
    <label>Identifiant admin</label>
    <input type="text" name="admin_user" value="admin" required>
    <label>Mot de passe admin (min. 8 caractères)</label>
    <input type="password" name="admin_pass" required>
    <label>Email admin (notifications de réservation)</label>
    <input type="email" name="admin_email" placeholder="admin@portaye.fr" required>
    <button type="submit">Lancer l'installation</button>
</form>

<div style="margin-top:24px">
    <strong>Avant de continuer, éditez <code>config.php</code> avec vos identifiants MySQL et SMTP.</strong>
</div>
<?php endif ?>

</body>
</html>
