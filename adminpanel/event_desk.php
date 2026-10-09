<?php
session_start();
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../includes/panel_layout.php';
require_once __DIR__ . '/../includes/form_catalog.php';
require_once __DIR__ . '/../includes/event_stations.php';

if (empty($_SESSION['admin_auth_user'])) {
    header('Location: admin_login.php?next=event_desk.php');
    exit;
}

$scanHref = 'scan.php';
if (($_GET['export'] ?? '') === 'csv') {
    require __DIR__ . '/../includes/event_desk_view.php';
    exit;
}

panel_start([
    'title' => "Today's counts",
    'role' => 'admin',
    'active' => 'event',
    'name' => get_admin_name(),
    'phone' => local_phone_display($_SESSION['admin_auth_user'] ?? ''),
    'asset_prefix' => '../',
    'links' => admin_nav_links(),
]);
require __DIR__ . '/../includes/event_desk_view.php';
panel_end();
