<?php
session_start();
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/wa_config.php';
if (!function_exists('form_class_label')) {
    require_once __DIR__ . '/../includes/form_catalog.php';
}

if (empty($_SESSION['admin_auth_user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Setup
$pdo = new PDO(
    'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
    DB_USER, DB_PASSWORD, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
);

// Parameters
$draw = intval($_POST['draw']);
$start = intval($_POST['start']);
$length = intval($_POST['length']);
$searchValue = $_POST['search']['value'] ?? '';
$where = '';
$params = [];
if($searchValue){
    $where = "WHERE (id LIKE :q OR first_name LIKE :q OR last_name LIKE :q OR mobile LIKE :q OR school_name LIKE :q)";
    $params[':q'] = "%$searchValue%";
}

// Total count
$recordsTotal = $pdo->query("SELECT COUNT(*) FROM scholarship_applications")->fetchColumn();
if($where){
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM scholarship_applications $where");
    $stmt->execute($params);
    $recordsFiltered = $stmt->fetchColumn();
}else{
    $recordsFiltered = $recordsTotal;
}

// Main data query
$sql = "SELECT * FROM scholarship_applications $where ORDER BY id DESC LIMIT :start, :len";
$stmt = $pdo->prepare($sql);
foreach($params as $k=>$v) $stmt->bindValue($k,$v);
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':len', $length, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = [];
foreach($rows as $row){
    $actions  = '<a class="btn sm" href="view_application.php?id='.$row['id'].'">View</a> ';
    $actions .= '<a class="btn sm gold" href="edit_application.php?id='.$row['id'].'">Edit</a>';
    $data[]=[
        $row['id'],
        trim($row['first_name'].' '.$row['middle_name'].' '.$row['last_name']),
        form_class_label((string) ($row['class'] ?? '')),
        $row['mobile'],
        $row['school_name'],
        strtoupper($row['payment_status']),
        date('d-m-Y',strtotime($row['created_at'])),
        $actions
    ];
}
echo json_encode([
    "draw"=>$draw,
    "recordsTotal"=>$recordsTotal,
    "recordsFiltered"=>$recordsFiltered,
    "data"=>$data
]);