<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$action = $_GET['a'] ?? $_POST['a'] ?? (json_decode(file_get_contents('php://input'), true)['a'] ?? '');

$json = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST)) {
    $raw = file_get_contents('php://input');
    if ($raw) $json = json_decode($raw, true) ?? [];
}

function p(string $key, $default = ''): string {
    global $json;
    $v = $_POST[$key] ?? $json[$key] ?? $default;
    if (is_array($v)) return (string)$default;
    return trim((string)$v);
}

// ─── ROUTES PUBLIQUES ────────────────────────────────────────────────────────

switch ($action) {

case 'calendar':
    $month = preg_replace('/[^0-9-]/', '', $_GET['month'] ?? date('Y-m'));
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) json_response(['error' => 'Format de mois invalide'], 400);

    [$year, $mon] = explode('-', $month);
    $daysInMonth = (int)date('t', mktime(0, 0, 0, $mon, 1, $year));
    $today   = date('Y-m-d');
    $maxDate = date('Y-m-d', strtotime('+' . ADVANCE_DAYS_MAX . ' days'));
    $available = [];

    for ($d = 1; $d <= $daysInMonth; $d++) {
        $date = "$year-" . str_pad($mon, 2, '0', STR_PAD_LEFT) . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
        if ($date < $today || $date > $maxDate) continue;
        if (count(get_available_slots($date)) > 0) $available[] = $date;
    }
    json_response(['available' => $available]);
    break;

case 'slots':
    $date = preg_replace('/[^0-9-]/', '', $_GET['date'] ?? '');
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) json_response(['error' => 'Date invalide'], 400);
    $slots = get_available_slots($date);
    json_response(['slots' => $slots, 'date' => $date]);
    break;

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
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))    json_response(['error' => 'Email invalide'], 400);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))  json_response(['error' => 'Date invalide'], 400);
    if (!preg_match('/^\d{2}:\d{2}$/', $time))         json_response(['error' => 'Heure invalide'], 400);

    $slots = get_available_slots($date);
    if (!in_array($time, $slots)) json_response(['error' => 'Ce créneau n\'est plus disponible'], 409);

    $appointments = jdb_read(JSON_APPOINTMENTS);
    foreach ($appointments as $a) {
        if ($a['email'] === $email && $a['appt_date'] === $date && $a['status'] !== 'cancelled') {
            json_response(['error' => 'Vous avez déjà un rendez-vous ce jour'], 409);
        }
    }

    $token = bin2hex(random_bytes(24));
    $id    = jdb_next_id($appointments);
    $newAppt = [
        'id'           => $id,
        'name'         => $name,
        'email'        => $email,
        'phone'        => $phone,
        'city'         => $city,
        'door_type'    => $door_type,
        'appt_date'    => $date,
        'appt_time'    => $time . ':00',
        'status'       => 'pending',
        'cancel_token' => $token,
        'admin_note'   => '',
        'created_at'   => date('Y-m-d H:i:s'),
    ];
    $appointments[] = $newAppt;
    jdb_write(JSON_APPOINTMENTS, $appointments);

    mail_client_pending($newAppt);
    mail_admin_new_booking($newAppt);

    json_response(['success' => true, 'id' => $id, 'token' => $token]);
    break;

case 'cancel_client':
    $token = preg_replace('/[^a-f0-9]/', '', $_GET['token'] ?? '');
    if (!$token) { http_response_code(400); die('Token invalide'); }

    $appointments = jdb_read(JSON_APPOINTMENTS);
    $appt = null;
    foreach ($appointments as $a) {
        if ($a['cancel_token'] === $token) { $appt = $a; break; }
    }
    if (!$appt) { http_response_code(404); die('Réservation introuvable'); }
    if ($appt['status'] === 'cancelled') { die('Ce rendez-vous est déjà annulé.'); }

    foreach ($appointments as &$a) {
        if ($a['cancel_token'] === $token) { $a['status'] = 'cancelled'; break; }
    }
    unset($a);
    jdb_write(JSON_APPOINTMENTS, $appointments);
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

    $all = jdb_read(JSON_APPOINTMENTS);

    $filtered = array_filter($all, function ($a) use ($status, $date_from, $date_to) {
        if ($status && in_array($status, ['pending', 'confirmed', 'cancelled']) && $a['status'] !== $status) return false;
        if ($date_from && $a['appt_date'] < $date_from) return false;
        if ($date_to   && $a['appt_date'] > $date_to)   return false;
        return true;
    });

    usort($filtered, function ($a, $b) {
        $cmp = strcmp($b['appt_date'], $a['appt_date']);
        return $cmp !== 0 ? $cmp : strcmp($b['appt_time'], $a['appt_time']);
    });

    $total = count($filtered);
    $rows  = array_slice(array_values($filtered), ($page - 1) * $per_page, $per_page);

    $today = date('Y-m-d');
    $stats = [
        'total'     => count($all),
        'pending'   => count(array_filter($all, function($a) { return $a['status'] === 'pending'; })),
        'confirmed' => count(array_filter($all, function($a) { return $a['status'] === 'confirmed'; })),
        'cancelled' => count(array_filter($all, function($a) { return $a['status'] === 'cancelled'; })),
        'today'     => count(array_filter($all, function($a) use ($today) { return $a['appt_date'] === $today && $a['status'] !== 'cancelled'; })),
    ];

    json_response(['appointments' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $per_page, 'stats' => $stats]);
    break;

case 'appointment_update':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'POST requis'], 405);
    $id     = (int)p('id');
    $status = p('status');
    $note   = p('note');

    if (!$id || !in_array($status, ['pending', 'confirmed', 'cancelled'])) json_response(['error' => 'Paramètres invalides'], 400);

    $appointments = jdb_read(JSON_APPOINTMENTS);
    $found = false;
    $appt  = null;
    $prevStatus = '';
    foreach ($appointments as &$a) {
        if ((int)$a['id'] === $id) {
            $appt       = $a;
            $prevStatus = $a['status'];
            $a['status']     = $status;
            $a['admin_note'] = $note;
            $found = true;
            break;
        }
    }
    unset($a);

    if (!$found) json_response(['error' => 'Rendez-vous introuvable'], 404);
    jdb_write(JSON_APPOINTMENTS, $appointments);

    if ($status === 'confirmed' && $prevStatus !== 'confirmed') mail_client_confirmed($appt);
    if ($status === 'cancelled' && $prevStatus !== 'cancelled') mail_client_cancelled($appt);

    json_response(['success' => true]);
    break;

case 'appointment_delete':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'POST requis'], 405);
    $id = (int)p('id');
    if (!$id) json_response(['error' => 'ID invalide'], 400);

    $appointments = jdb_read(JSON_APPOINTMENTS);
    $appointments = array_values(array_filter($appointments, function($a) use ($id) { return (int)$a['id'] !== $id; }));
    jdb_write(JSON_APPOINTMENTS, $appointments);
    json_response(['success' => true]);
    break;

case 'availability':
    require_admin();
    $rules = jdb_read(JSON_AVAILABILITY);
    usort($rules, function($a, $b) { return $a['day_of_week'] <=> $b['day_of_week']; });
    json_response(['rules' => $rules]);
    break;

case 'availability_save':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'POST requis'], 405);
    $rules = $json['rules'] ?? $_POST['rules'] ?? [];
    if (!is_array($rules)) json_response(['error' => 'Format invalide'], 400);

    $newRules = [];
    $rid = 1;
    foreach ($rules as $r) {
        $day    = (int)($r['day_of_week'] ?? 0);
        $open   = preg_replace('/[^0-9:]/', '', $r['open_time'] ?? '09:00');
        $close  = preg_replace('/[^0-9:]/', '', $r['close_time'] ?? '18:00');
        $active = isset($r['is_active']) ? (int)(bool)$r['is_active'] : 1;
        if ($day >= 1 && $day <= 7) {
            $newRules[] = [
                'id'          => $rid++,
                'day_of_week' => $day,
                'open_time'   => strlen($open) === 5 ? $open . ':00' : $open,
                'close_time'  => strlen($close) === 5 ? $close . ':00' : $close,
                'is_active'   => $active,
            ];
        }
    }
    jdb_write(JSON_AVAILABILITY, $newRules);

    if (isset($json['slot_duration'])) {
        $settings = get_settings();
        $settings['slot_duration'] = (string)max(15, min(240, (int)$json['slot_duration']));
        jdb_write(JSON_SETTINGS, $settings);
    }

    json_response(['success' => true]);
    break;

case 'blocked':
    require_admin();
    $blocked = jdb_read(JSON_BLOCKED);
    usort($blocked, function($a, $b) { return strcmp($a['start_date'], $b['start_date']); });
    json_response(['blocked' => $blocked]);
    break;

case 'blocked_add':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'POST requis'], 405);
    $start  = preg_replace('/[^0-9-]/', '', p('start_date'));
    $end    = preg_replace('/[^0-9-]/', '', p('end_date'));
    $reason = p('reason');
    if (!$start || !$end || $end < $start) json_response(['error' => 'Dates invalides'], 400);

    $blocked = jdb_read(JSON_BLOCKED);
    $newId   = jdb_next_id($blocked);
    $blocked[] = [
        'id'         => $newId,
        'start_date' => $start,
        'end_date'   => $end,
        'reason'     => $reason,
        'created_at' => date('Y-m-d H:i:s'),
    ];
    jdb_write(JSON_BLOCKED, $blocked);
    json_response(['success' => true, 'id' => $newId]);
    break;

case 'blocked_remove':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'POST requis'], 405);
    $id = (int)p('id');
    $blocked = jdb_read(JSON_BLOCKED);
    $blocked = array_values(array_filter($blocked, function($b) use ($id) { return (int)$b['id'] !== $id; }));
    jdb_write(JSON_BLOCKED, $blocked);
    json_response(['success' => true]);
    break;

case 'settings_save':
    require_admin();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error' => 'POST requis'], 405);
    $settings = get_settings();
    foreach (['admin_email', 'advance_days', 'min_hours'] as $k) {
        if (isset($json[$k])) $settings[$k] = $json[$k];
    }
    if (!empty($json['new_password']) && strlen($json['new_password']) >= 8) {
        $settings['admin_password'] = password_hash($json['new_password'], PASSWORD_DEFAULT);
    }
    jdb_write(JSON_SETTINGS, $settings);
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
    $cache = jdb_read(JSON_SETTINGS);
    return $cache;
}

function get_available_slots(string $date): array {
    $today = date('Y-m-d');
    if ($date < $today) return [];
    if ($date > date('Y-m-d', strtotime('+' . ADVANCE_DAYS_MAX . ' days'))) return [];

    $blocked = jdb_read(JSON_BLOCKED);
    foreach ($blocked as $b) {
        if ($b['start_date'] <= $date && $b['end_date'] >= $date) return [];
    }

    $dow   = (int)date('N', strtotime($date));
    $rules = jdb_read(JSON_AVAILABILITY);
    $r     = null;
    foreach ($rules as $rule) {
        if ((int)$rule['day_of_week'] === $dow && $rule['is_active']) { $r = $rule; break; }
    }
    if (!$r) return [];

    $settings = get_settings();
    $dur      = max(15, (int)($settings['slot_duration'] ?? SLOT_DURATION));
    $minHours = (int)($settings['min_hours'] ?? MIN_BOOKING_HOURS);

    $open  = strtotime($date . ' ' . $r['open_time']);
    $close = strtotime($date . ' ' . $r['close_time']);
    $minTs = time() + $minHours * 3600;

    $slots = [];
    for ($ts = $open; $ts + $dur * 60 <= $close; $ts += $dur * 60) {
        if ($ts > $minTs) $slots[] = date('H:i', $ts);
    }

    if ($slots) {
        $appointments = jdb_read(JSON_APPOINTMENTS);
        $taken = [];
        foreach ($appointments as $a) {
            if ($a['appt_date'] === $date && $a['status'] !== 'cancelled') {
                $taken[] = substr($a['appt_time'], 0, 5);
            }
        }
        $slots = array_values(array_diff($slots, $taken));
    }

    return $slots;
}
