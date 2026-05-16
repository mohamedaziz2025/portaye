<?php
// ─── BASE DE DONNÉES ──────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'portaye_db');
define('DB_USER', 'portaye_user');
define('DB_PASS', 'CHANGE_ME_DB_PASSWORD');

// ─── SMTP ─────────────────────────────────────────────────────────────────────
define('SMTP_HOST',      'smtp.gmail.com');
define('SMTP_PORT',       587);           // 587 = STARTTLS
define('SMTP_SECURE',    'tls');
define('SMTP_USER',      'sirh.contact2023@gmail.com');
define('SMTP_PASS',      'gozqplkchdhiylck');
define('SMTP_FROM',      'sirh.contact2023@gmail.com');
define('SMTP_FROM_NAME', 'Portaye');

// ─── ADMIN ────────────────────────────────────────────────────────────────────
define('ADMIN_USERNAME', 'admin');
// Générer le hash : php -r "echo password_hash('VotreMotDePasse', PASSWORD_DEFAULT);"
define('ADMIN_PASSWORD_HASH', '$2y$10$PLACEHOLDER_RUN_INSTALL_TO_SET');
define('ADMIN_EMAIL', 'sirh.contact2023@gmail.com');

// ─── SITE ─────────────────────────────────────────────────────────────────────
define('SITE_NAME', 'Portaye');
define('SITE_URL',  'https://portaye.fr');
define('TIMEZONE',  'Europe/Paris');

// ─── PARAMÈTRES DE RÉSERVATION ────────────────────────────────────────────────
define('SLOT_DURATION',    60);  // durée d'un créneau en minutes
define('ADVANCE_DAYS_MAX', 60);  // réservation max X jours à l'avance
define('MIN_BOOKING_HOURS', 4);  // délai min avant rendez-vous (heures)
define('PRICE',           179);  // prix affiché dans les emails

date_default_timezone_set(TIMEZONE);
session_name('portaye_admin');

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES   => false]
        );
    }
    return $pdo;
}

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
