<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$action = $_GET['a'] ?? $_POST['a'] ?? (json_decode(file_get_contents('php://input'), true)['a'] ?? '');

// Lire le body JSON si besoin
$json = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    $raw = file_get_contents('php://input');
    if ($raw) $json = json_decode($raw, true) ?? [];
}
function p(string $key, $default = ''): string {
    global $json;
    $v = $_POST[$key] ?? $json[$key] ?? $default;
    return is_string($v) ? trim($v) : (string)$default;
}

// ─── ROUTES PUBLIQUES ────────────────────────────────────────────────────────

switch ($action) {

// Jours disponibles dans un mois (pour colorier le calendrier)
case 'calendar':
    $month = preg_replace('/[^0-9-]/', '', $_GET['month'] ?? date('Y-m'));
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) json_response(['error' => 'Format de mois invalide'], 400);

    [$year, $mon] = explode('-', $month);
    $daysInMonth = (int)date('t', mktime(0, 0, 0, $mon, 1, $year));
    $today = date('Y-m-d');
    $maxDate = date('Y-m-d', strtotime('+' . ADVANCE_DAYS_MAX . ' days'));
    $available = [];

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $date = "$year-" . str_pad($mon, 2, '0', STR_PAD_LEFT) . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
        if ($date < $today || $date > $maxDate) continue;
        if (count(get_available_slots($date)) > 0) $available[] = $date;
    }
    json_response(['available' => $available]);
    break;

// Créneaux disponibles pour une date
case 'slots':
    $date = preg_replace('/[^0-9-]/', '', $_GET['date'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) json_response(['error' => 'Date invalide'], 400);
    $slots = get_available_slots($date);
    json_response(['slots' => $slots, 'date' => $date]);
    break;

// Créer une réservation
case 'book':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'POST requis'], 405);

    $name      = p('name');
    $email     = p('email');
    $phone     = p('phone');
    $city      = p('city');
    $door_type = p('door_type');
    $date      = preg_replace('/[^0-9-]/', '', p('date'));
    $time      = preg_replace('/[^0-9:]/', '', p('time'));

    if (!$name || !$email || !$date || !$time) json_response(['error' => 'Champs obligatoires manquants'], 400);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_response(['error' => 'Email invalide'], 400);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) json_response(['error' => 'Date invalide'], 400);
    if (!preg_match('/^\d{2}:\d{2}$/', $time)) json_response(['error' => 'Heure invalide'], 400);

    // Vérifier que le créneau est disponible
    $slots = get_available_slots($date);
    if (!in_array($time, $slots)) json_response(['error' => 'Ce créneau n\'est plus disponible'], 409);

    // Vérifier doublon email+date
    $existing = db()->prepare("SELECT id FROM appointments WHERE email=? AND appt_date=? AND status != 'cancelled'");
    $existing->execute([$email, $date]);
    if ($existing->fetch()) json_response(['error' => 'Vous avez déjà un rendez-vous ce jour'], 409);

    $token = bin2hex(random_bytes(24));
    $stmt  = db()->prepare("INSERT INTO appointments (name, email, phone, city, door_type, appt_date, appt_time, status, cancel_token)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
    $stmt->execute([$name, $email, $phone, $city, $door_type, $date, $time . ':00', $token]);
    $id = (int)db()->lastInsertId();

    $appt = compact('name', 'email', 'phone', 'city', 'door_type') + ['id' => $id, 'cancel_token' => $token, 'appt_date' => $date, 'appt_time' => $time . ':00'];

    mail_client_pending($appt);
    mail_admin_new_booking($appt);

    json_response(['success' => true, 'id' => $id, 'token' => $token]);
    break;

// Annulation par le client (via lien email)
case 'cancel_client':
    $token = preg_replace('/[^a-f0-9]/', '', $_GET['token'] ?? '');
    if (!$token) { http_response_code(400); die('Token invalide'); }

    $stmt = db()->prepare("SELECT * FROM appointments WHERE cancel_token=?");
    $stmt->execute([$token]);
    $appt = $stmt->fetch();
    if (!$appt) { http_response_code(404); die('Réservation introuvable'); }
    if ($appt['status'] === 'cancelled') { die('Ce rendez-vous est déjà annulé.'); }

    db()->prepare("UPDATE appointments SET status='cancelled' WHERE cancel_token=?")->execute([$token]);
    mail_client_cancelled($appt);

    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><title>Annulation confirmée</title>
<style>body{font-family:-apple-system,sans-serif;text-align:center;padding:80px 24px;color:#1d1d1f}
h1{font-size:28px;font-weight:700;margin-bottom:12px}p{color:#6e6e73;font-size:17px}
a{color:#0071e3;text-decoration:none}
</style></head><body>
<h1>Rendez-vous annulé</h1>
<p>Votre rendez-vous a été annulé avec succès.<br>Vous pouvez <a href="' . SITE_URL . '/#booking">réserver un nouveau créneau</a>.</p>
</body></html>';
    exit;

// ─── ROUTES ADMIN ─────────────────────────────────────────────────────────────

case 'login':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'POST requis'], 405);
    $username = p('username');
    $password = p('password');

    $settings = get_settings();
    $hash = $settings['admin_password'] ?? ADMIN_PASSWORD_HASH;
    $user = $settings['admin_username'] ?? ADMIN_USERNAME;

    if ($username === $user && password_verify($password, $hash)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        json_response(['success' => true]);
    } else {
        sleep(1);
        json_response(['error' => 'Identifiants incorrects'], 401);
    }
    break;

case 'logout':
    session_destroy();
    json_response(['success' => true]);
    break;

case 'check_auth':
    json_response(['authenticated' => is_admin()]);
    break;

case 'appointments':
    require_admin();
    $status    = $_GET['status'] ?? '';
    $date_from = preg_replace('/[^0-9-]/', '', $_GET['date_from'] ?? '');
    $date_to   = preg_replace('/[^0-9-]/', '', $_GET['date_to'] ?? '');
    $page      = max(1, (int)($_GET['page'] ?? 1));
    $per_page  = 20;
    $offset    = ($page - 1) * $per_page;

    $where = []; $params = [];
    if ($status && in_array($status, ['pending', 'confirmed', 'cancelled'])) {
        $where[] = 'status = ?'; $params[] = $status;
    }
    if ($date_from) { $where[] = 'appt_date >= ?'; $params[] = $date_from; }
    if ($date_to)   { $where[] = 'appt_date <= ?'; $params[] = $date_to; }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $total = db()->prepare("SELECT COUNT(*) FROM appointments $whereClause");
    $total->execute($params);
    $total = (int)$total->fetchColumn();

    $stmt = db()->prepare("SELECT * FROM appointments $whereClause ORDER BY appt_date DESC, appt_time DESC LIMIT $per_page OFFSET $offset");
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Stats globales
    $stats = db()->query("SELECT
        COUNT(*) as total,
        SUM(status='pending') as pending,
        SUM(status='confirmed') as confirmed,
        SUM(status='cancelled') as cancelled,
        SUM(appt_date = CURDATE() AND status != 'cancelled') as today
    FROM appointments")->fetch();

    json_response(['appointments' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $per_page, 'stats' => $stats]);
    break;

case 'appointment_update':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'POST requis'], 405);
    $id     = (int)p('id');
    $status = p('status');
    $note   = p('note');

    if (!$id || !in_array($status, ['pending', 'confirmed', 'cancelled'])) json_response(['error' => 'Paramètres invalides'], 400);

    $stmt = db()->prepare("SELECT * FROM appointments WHERE id=?");
    $stmt->execute([$id]);
    $appt = $stmt->fetch();
    if (!$appt) json_response(['error' => 'Rendez-vous introuvable'], 404);

    db()->prepare("UPDATE appointments SET status=?, admin_note=? WHERE id=?")->execute([$status, $note, $id]);

    if ($status === 'confirmed' && $appt['status'] !== 'confirmed') mail_client_confirmed($appt);
    if ($status === 'cancelled' && $appt['status'] !== 'cancelled') mail_client_cancelled($appt);

    json_response(['success' => true]);
    break;

case 'appointment_delete':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'POST requis'], 405);
    $id = (int)p('id');
    if (!$id) json_response(['error' => 'ID invalide'], 400);
    db()->prepare("DELETE FROM appointments WHERE id=?")->execute([$id]);
    json_response(['success' => true]);
    break;

case 'availability':
    require_admin();
    $stmt = db()->query("SELECT * FROM availability_rules ORDER BY day_of_week");
    json_response(['rules' => $stmt->fetchAll()]);
    break;

case 'availability_save':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'POST requis'], 405);
    $rules = $json['rules'] ?? $_POST['rules'] ?? [];
    if (!is_array($rules)) json_response(['error' => 'Format invalide'], 400);

    db()->exec("DELETE FROM availability_rules");
    $stmt = db()->prepare("INSERT INTO availability_rules (day_of_week, open_time, close_time, is_active) VALUES (?,?,?,?)");
    foreach ($rules as $r) {
        $day   = (int)($r['day_of_week'] ?? 0);
        $open  = preg_replace('/[^0-9:]/', '', $r['open_time'] ?? '09:00');
        $close = preg_replace('/[^0-9:]/', '', $r['close_time'] ?? '18:00');
        $active = isset($r['is_active']) ? (int)(bool)$r['is_active'] : 1;
        if ($day >= 1 && $day <= 7) $stmt->execute([$day, $open . ':00', $close . ':00', $active]);
    }

    // Durée des créneaux
    if (isset($json['slot_duration'])) {
        $dur = max(15, min(240, (int)$json['slot_duration']));
        db()->prepare("INSERT INTO settings (k,v) VALUES ('slot_duration',?) ON DUPLICATE KEY UPDATE v=?")->execute([$dur, $dur]);
    }

    json_response(['success' => true]);
    break;

case 'blocked':
    require_admin();
    $rows = db()->query("SELECT * FROM blocked_dates ORDER BY start_date")->fetchAll();
    json_response(['blocked' => $rows]);
    break;

case 'blocked_add':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'POST requis'], 405);
    $start  = preg_replace('/[^0-9-]/', '', p('start_date'));
    $end    = preg_replace('/[^0-9-]/', '', p('end_date'));
    $reason = p('reason');
    if (!$start || !$end || $end < $start) json_response(['error' => 'Dates invalides'], 400);
    db()->prepare("INSERT INTO blocked_dates (start_date, end_date, reason) VALUES (?,?,?)")->execute([$start, $end, $reason]);
    json_response(['success' => true, 'id' => db()->lastInsertId()]);
    break;

case 'blocked_remove':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'POST requis'], 405);
    $id = (int)p('id');
    db()->prepare("DELETE FROM blocked_dates WHERE id=?")->execute([$id]);
    json_response(['success' => true]);
    break;

case 'settings_save':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'POST requis'], 405);
    $allowed = ['admin_email', 'advance_days', 'min_hours'];
    $stmt = db()->prepare("INSERT INTO settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=?");
    foreach ($allowed as $k) {
        if (isset($json[$k])) $stmt->execute([$k, $json[$k], $json[$k]]);
    }
    // Changement de mot de passe
    if (!empty($json['new_password']) && strlen($json['new_password']) >= 8) {
        $hash = password_hash($json['new_password'], PASSWORD_DEFAULT);
        $stmt->execute(['admin_password', $hash, $hash]);
    }
    json_response(['success' => true]);
    break;

case 'smtp_test':
    require_admin();
    $to = get_settings()['admin_email'] ?? ADMIN_EMAIL;
    $ok = SMTPMailer::send($to, 'Admin Portaye', 'Test SMTP Portaye', email_wrap('Test', '<p style="font-size:16px;color:#1d1d1f">L\'envoi d\'email fonctionne correctement.</p>'));
    json_response(['success' => $ok, 'to' => $to]);
    break;

default:
    json_response(['error' => 'Action inconnue'], 404);
}

// ─── FONCTIONS UTILITAIRES ────────────────────────────────────────────────────

function get_settings(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    try {
        $rows = db()->query("SELECT k, v FROM settings")->fetchAll();
        $cache = array_column($rows, 'v', 'k');
    } catch (Exception $e) {
        $cache = [];
    }
    return $cache;
}

function get_available_slots(string $date): array {
    $today = date('Y-m-d');
    if ($date < $today) return [];
    if ($date > date('Y-m-d', strtotime('+' . ADVANCE_DAYS_MAX . ' days'))) return [];

    // Vérifier si bloqué
    $blocked = db()->prepare("SELECT id FROM blocked_dates WHERE start_date <= ? AND end_date >= ?");
    $blocked->execute([$date, $date]);
    if ($blocked->fetch()) return [];

    // Jour de la semaine (1=Lun, 7=Dim)
    $dow = (int)date('N', strtotime($date));
    $rule = db()->prepare("SELECT * FROM availability_rules WHERE day_of_week=? AND is_active=1");
    $rule->execute([$dow]);
    $r = $rule->fetch();
    if (!$r) return [];

    // Durée depuis settings
    $settings = get_settings();
    $dur = (int)($settings['slot_duration'] ?? SLOT_DURATION);
    if ($dur < 15) $dur = 15;

    // Générer tous les créneaux
    $open  = strtotime($date . ' ' . $r['open_time']);
    $close = strtotime($date . ' ' . $r['close_time']);
    $minTs = time() + (int)($settings['min_hours'] ?? MIN_BOOKING_HOURS) * 3600;
    $slots = [];
    for ($ts = $open; $ts + $dur * 60 <= $close; $ts += $dur * 60) {
        if ($ts > $minTs) $slots[] = date('H:i', $ts);
    }

    // Enlever les créneaux déjà pris
    if ($slots) {
        $taken = db()->prepare("SELECT TIME_FORMAT(appt_time,'%H:%i') as t FROM appointments WHERE appt_date=? AND status != 'cancelled'");
        $taken->execute([$date]);
        $takenSlots = array_column($taken->fetchAll(), 't');
        $slots = array_values(array_diff($slots, $takenSlots));
    }

    return $slots;
}
