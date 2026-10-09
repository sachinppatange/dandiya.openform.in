<?php
session_start();
require_once __DIR__ . '/../includes/staff_auth.php';
require_once __DIR__ . '/../includes/staff_repository.php';
require_once __DIR__ . '/../includes/panel_layout.php';
require_once __DIR__ . '/../includes/form_catalog.php';
require_once __DIR__ . '/../includes/event_stations.php';

require_staff_login();
$scanHref = 'scan.php';
if (($_GET['export'] ?? '') === 'csv') {
    require __DIR__ . '/../includes/event_desk_view.php';
    exit;
}

panel_start([
    'title' => "Today's counts",
    'role' => 'staff',
    'active' => 'event',
    'name' => (string) ($_SESSION['staff_auth_name'] ?? 'Staff'),
    'phone' => local_phone_display($_SESSION['staff_auth_user'] ?? ''),
    'asset_prefix' => '../',
    'links' => staff_nav_links(),
]);
require __DIR__ . '/../includes/event_desk_view.php';
panel_end();
