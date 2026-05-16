<?php
// FICHIER DE DIAGNOSTIC TEMPORAIRE — SUPPRIMER APRÈS UTILISATION
header('Content-Type: text/plain; charset=utf-8');

echo "=== PHP ===\n";
echo "Version : " . PHP_VERSION . "\n";
echo "SAPI    : " . php_sapi_name() . "\n\n";

echo "=== CONFIG.PHP ===\n";
try {
    require_once __DIR__ . '/config.php';
    echo "OK — chargé\n";
    echo "DATA_DIR           : " . DATA_DIR . "\n";
    echo "DATA_DIR existe    : " . (is_dir(DATA_DIR) ? 'OUI' : 'NON') . "\n";
    echo "DATA_DIR lisible   : " . (is_readable(DATA_DIR) ? 'OUI' : 'NON') . "\n";
    echo "DATA_DIR accessible en écriture : " . (is_writable(DATA_DIR) ? 'OUI' : 'NON') . "\n";
    echo "appointments.json  : " . (file_exists(JSON_APPOINTMENTS) ? 'OUI' : 'NON') . "\n";
    echo "availability.json  : " . (file_exists(JSON_AVAILABILITY) ? 'OUI' : 'NON') . "\n";
    echo "blocked.json       : " . (file_exists(JSON_BLOCKED) ? 'OUI' : 'NON') . "\n";
    echo "settings.json      : " . (file_exists(JSON_SETTINGS) ? 'OUI' : 'NON') . "\n";
} catch (Throwable $e) {
    echo "ERREUR : " . $e->getMessage() . " (" . $e->getFile() . ":" . $e->getLine() . ")\n";
}

echo "\n=== MAILER.PHP ===\n";
try {
    require_once __DIR__ . '/mailer.php';
    echo "OK — chargé\n";
} catch (Throwable $e) {
    echo "ERREUR : " . $e->getMessage() . " (" . $e->getFile() . ":" . $e->getLine() . ")\n";
}

echo "\n=== SESSION ===\n";
try {
    session_start();
    echo "OK — session démarrée\n";
} catch (Throwable $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
}

echo "\n=== FONCTIONS REQUISES ===\n";
$fns = ['json_encode','json_decode','flock','stream_get_contents','quoted_printable_encode','password_verify','random_bytes'];
foreach ($fns as $fn) {
    echo "$fn : " . (function_exists($fn) ? 'OK' : 'MANQUANTE') . "\n";
}

echo "\n=== EXTENSIONS ===\n";
echo "openssl  : " . (extension_loaded('openssl') ? 'OK' : 'MANQUANTE') . "\n";
echo "json     : " . (extension_loaded('json') ? 'OK' : 'MANQUANTE') . "\n";
echo "session  : " . (extension_loaded('session') ? 'OK' : 'MANQUANTE') . "\n";

echo "\n=== DERNIERES ERREURS PHP ===\n";
$last = error_get_last();
echo $last ? print_r($last, true) : "Aucune\n";
