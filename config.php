<?php
// ─── SMTP ─────────────────────────────────────────────────────────────────────
define('SMTP_HOST',      'smtp.gmail.com');
define('SMTP_PORT',       587);
define('SMTP_SECURE',    'tls');
define('SMTP_USER',      'sirh.contact2023@gmail.com');
define('SMTP_PASS',      'gozqplkchdhiylck');
define('SMTP_FROM',      'sirh.contact2023@gmail.com');
define('SMTP_FROM_NAME', 'Portaye');

// ─── ADMIN PAR DÉFAUT (écrasé par data/settings.json après install) ───────────
define('ADMIN_USERNAME',      'admin');
define('ADMIN_PASSWORD_HASH', '$2y$10$PLACEHOLDER_RUN_INSTALL_TO_SET');
define('ADMIN_EMAIL',         'sirh.contact2023@gmail.com');

// ─── SITE ─────────────────────────────────────────────────────────────────────
define('SITE_NAME', 'Portaye');
define('SITE_URL',  'https://portaye.fr');
define('TIMEZONE',  'Europe/Paris');

// ─── PARAMÈTRES DE RÉSERVATION (valeurs par défaut) ──────────────────────────
define('SLOT_DURATION',     60);
define('ADVANCE_DAYS_MAX',  60);
define('MIN_BOOKING_HOURS',  4);
define('PRICE',            179);

// ─── CHEMINS DES FICHIERS JSON ────────────────────────────────────────────────
define('DATA_DIR',          __DIR__ . '/data');
define('JSON_APPOINTMENTS', DATA_DIR . '/appointments.json');
define('JSON_AVAILABILITY', DATA_DIR . '/availability_rules.json');
define('JSON_BLOCKED',      DATA_DIR . '/blocked_dates.json');
define('JSON_SETTINGS',     DATA_DIR . '/settings.json');

date_default_timezone_set(TIMEZONE);
session_name('portaye_admin');

// ─── JSON "BASE DE DONNÉES" ───────────────────────────────────────────────────

function jdb_read(string $file): array {
    if (!file_exists($file)) return [];
    $fp = fopen($file, 'r');
    if (!$fp) return [];
    flock($fp, LOCK_SH);
    $content = stream_get_contents($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
    return $content ? (json_decode($content, true) ?? []) : [];
}

function jdb_write(string $file, array $data): void {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0750, true);
    $fp = fopen($file, 'c');
    flock($fp, LOCK_EX);
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

function jdb_next_id(array $records): int {
    if (empty($records)) return 1;
    return max(array_column($records, 'id')) + 1;
}

// ─── UTILITAIRES HTTP ─────────────────────────────────────────────────────────

function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function is_admin(): bool {
    return !empty($_SESSION['admin_logged_in']);
}

function require_admin(): void {
    if (!is_admin()) {
        json_response(['error' => 'Non autorisé'], 401);
    }
}
