<?php
session_start();
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/wa_config.php';
if (!function_exists('form_all_class_labels')) {
    require_once __DIR__ . '/../includes/form_catalog.php';
}
require_once __DIR__ . '/registration_query.php';

if (empty($_SESSION['admin_auth_user'])) {
    header('Location: admin_login.php?next=admin_dashboard.php');
    exit;
}

$f = registration_read_filters($_GET);
$class_labels = registration_class_labels();
$filter = $f['status'];
$class = $f['class'];
if ($class !== '' && !isset($class_labels[$class])) {
    $class = '';
    $f['class'] = '';
}
$format = $_GET['format'] ?? 'csv';
if (!in_array($format, ['csv', 'excel', 'pdf'], true)) {
    $format = 'csv';
}

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
    DB_USER, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

[$WHERE, $params] = registration_where($f);
$stmt = $pdo->prepare("SELECT * FROM scholarship_applications $WHERE ORDER BY id DESC LIMIT 20000");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$stamp = date('Y-m-d');
$filename = 'registrations_' . $stamp;
$headers = registration_export_headers();
$title = function_exists('landing_page_title') ? landing_page_title() : 'Registrations';

if ($format === 'pdf') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . htmlspecialchars($filename) . '</title>
    <style>
      @page { size: A4 landscape; margin: 10mm; }
      body{font-family:Arial,sans-serif;font-size:10px;color:#111;padding:12px}
      h1{font-size:16px;margin:0 0 6px}
      p{color:#555;margin:0 0 10px}
      table{width:100%;border-collapse:collapse}
      th,td{border:1px solid #ddd;padding:4px 5px;text-align:left;vertical-align:top}
      th{background:#0058F0;color:#fff;font-size:9px}
      td{font-size:9px}
      @media print { .no-print{display:none} }
    </style></head><body>
    <div class="no-print" style="margin-bottom:12px">
      <button onclick="window.print()">Print / Save as PDF</button>
    </div>
    <h1>' . htmlspecialchars($title) . ' · Registration</h1>
    <p>' . count($rows) . ' rows · ' . htmlspecialchars($stamp) . '</p>
    <table><thead><tr>';
    foreach ($headers as $h) {
        echo '<th>' . htmlspecialchars($h) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $app) {
        echo '<tr>';
        foreach (registration_export_row($app, $class_labels) as $cell) {
            echo '<td>' . htmlspecialchars((string) $cell) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table><script>window.addEventListener("load",function(){setTimeout(function(){window.print();},400);});</script></body></html>';
    exit;
}

$excel = ($format === 'excel');
header('Content-Type: ' . ($excel ? 'application/vnd.ms-excel' : 'text/csv') . '; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '.' . ($excel ? 'xls' : 'csv') . '"');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, $headers);
foreach ($rows as $app) {
    fputcsv($out, registration_export_row($app, $class_labels));
}
fclose($out);
exit;
