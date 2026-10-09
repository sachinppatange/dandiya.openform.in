<?php
if (!function_exists('event_lookup_application')) {
    require_once __DIR__ . '/event_stations.php';
}

$scanQ = trim((string) ($_GET['q'] ?? $_POST['q'] ?? ''));
$scanError = '';
$scanMsg = '';
$scanApp = null;
$operator = event_operator();
$scanBackHref = $scanBackHref ?? 'scan.php';

ensure_event_stations_schema();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $operator && isset($_POST['station'], $_POST['app_id'])) {
    $appId = (int) $_POST['app_id'];
    $station = (string) $_POST['station'];
    $mark = ($_POST['action'] ?? '') !== 'undo';
    $by = $operator['role'] . ':' . $operator['name'];
    if ($appId && event_toggle_checkin($appId, $station, $by, $mark)) {
        $tok = trim((string) ($_POST['token'] ?? ''));
        $redirQ = $tok !== '' ? $tok : (string) $appId;
        header('Location: ' . $scanBackHref . '?q=' . rawurlencode($redirQ) . '&ok=1');
        exit;
    }
    $scanError = 'Could not update that station.';
}

if ($scanQ !== '') {
    $scanApp = event_lookup_application($scanQ);
    if (!$scanApp) {
        $scanError = 'No registration matched that QR, Application ID, or mobile.';
    }
}

if (isset($_GET['ok']) && $scanApp) {
    $scanMsg = 'Station updated.';
}

$scanToken = (string) ($scanApp['receipt_token'] ?? '');
$scanCheckins = $scanApp ? event_checkins_for((int) $scanApp['id']) : [];
$scanStations = event_stations();
$scanProfile = $scanApp ? event_application_profile($scanApp) : [];
$scanName = $scanApp ? event_full_name($scanApp) : '';
$scanCode = $scanApp ? event_application_code((int) $scanApp['id']) : '';
$scanStatus = strtolower((string) ($scanApp['payment_status'] ?? ''));
