<?php
session_start();
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/wa_config.php';
if (!function_exists('form_all_class_labels')) {
    require_once __DIR__ . '/../includes/form_catalog.php';
}
require_once __DIR__ . '/registration_query.php';

header('Content-Type: application/json');

if (empty($_SESSION['admin_auth_user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$src = array_merge($_GET, $_POST);
$f = registration_read_filters($src);
if (isset($src['status']) && ($src['filter'] ?? '') === '') {
    $st = (string) $src['status'];
    if (in_array($st, ['all', 'paid', 'pending', 'failed'], true)) {
        $f['status'] = $st;
    }
}

$start = (int) ($_POST['start'] ?? $_GET['start'] ?? 0);
$length = (int) ($_POST['length'] ?? $_GET['length'] ?? 25);
$draw = (int) ($_POST['draw'] ?? $_GET['draw'] ?? 1);
if ($length < 1 || $length > 200) {
    $length = 25;
}

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
    DB_USER, DB_PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$class_labels = registration_class_labels();
[$WHERE, $params] = registration_where($f);

$recordsTotal = (int) $pdo->query("SELECT COUNT(*) FROM scholarship_applications")->fetchColumn();
if ($WHERE) {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM scholarship_applications $WHERE");
    $countStmt->execute($params);
    $recordsFiltered = (int) $countStmt->fetchColumn();
} else {
    $recordsFiltered = $recordsTotal;
}

$sql = "SELECT * FROM scholarship_applications $WHERE ORDER BY id DESC LIMIT ?, ?";
$stmt = $pdo->prepare($sql);
$i = 1;
foreach ($params as $v) {
    $stmt->bindValue($i++, $v);
}
$stmt->bindValue($i++, $start, PDO::PARAM_INT);
$stmt->bindValue($i++, $length, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$data = [];
$sr = $start + 1;
foreach ($rows as $app) {
    $full_name = trim($app['first_name'] . ' ' . $app['middle_name'] . ' ' . $app['last_name']);
    $classLab = form_class_label((string) ($app['class'] ?? ''));
    $status = (string) $app['payment_status'];
    $actions = '<a href="view_application.php?id=' . (int) $app['id'] . '" class="btn sm">View</a> ';
    $actions .= '<a href="edit_application.php?id=' . (int) $app['id'] . '" class="btn sm gold">Edit</a>';
    if ($status === 'paid' && !empty($app['receipt_token'])) {
        $actions .= ' <a href="../payment_success.php?token=' . urlencode((string) $app['receipt_token']) . '" class="btn sm green" target="_blank">Receipt</a>';
    }
    $data[] = [
        $sr++,
        (int) $app['id'],
        htmlspecialchars($full_name),
        htmlspecialchars((string) $app['school_name']),
        htmlspecialchars((string) $classLab),
        htmlspecialchars((string) $app['mobile']),
        '₹' . number_format((float) $app['exam_fee'], 2) . (!empty($app['coupon_code']) ? ' · ' . htmlspecialchars((string) $app['coupon_code']) : ''),
        '<span class="badge ' . htmlspecialchars($status) . '">' . htmlspecialchars($status) . '</span>',
        date('d-m-Y', strtotime((string) $app['created_at'])),
        $actions,
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $data,
]);
exit;
