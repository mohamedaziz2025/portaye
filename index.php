<?php
require_once __DIR__ . '/config.php';

$settings   = jdb_read(JSON_SETTINGS);
$notInstalled = empty($settings)
    || str_contains($settings['admin_password'] ?? '', 'PLACEHOLDER')
    || !file_exists(JSON_APPOINTMENTS)
    || !file_exists(JSON_AVAILABILITY)
    || !file_exists(JSON_BLOCKED);

if ($notInstalled) {
    header('Location: /install.php');
    exit;
}

readfile(__DIR__ . '/main.html');
