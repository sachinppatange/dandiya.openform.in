<?php
session_start();
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/wa_config.php';
if (empty($_SESSION['admin_auth_user'])) {
    http_response_code(401);
    echo json_encode(["error"=>"Unauthorized"]);
    exit;
}

// -- COLUMNS mapping --
$columns = [
    'id', 'first_name', 'class', 'mobile', 'school_name', 'city', 'exam_fee', 'payment_status', 'created_at'
];

// Paging, ordering, search
$start      = intval($_GET['start'] ?? 0);
$length     = intval($_GET['length'] ?? 25);
$draw       = intval($_GET['draw'] ?? 1);

$order_col  = intval($_GET['order'][0]['column'] ?? 0);
$order_dir  = ($_GET['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$order_by   = $columns[$order_col] ?? 'id';

$search     = trim($_GET['search']['value'] ?? '');
$where = '';
$params = [];

if ($search !== '') {
    $where = "WHERE
        id LIKE :search
        OR CONCAT(first_name,' ',middle_name,' ',last_name) LIKE :search
        OR mobile LIKE :search
        OR school_name LIKE :search
        OR city LIKE :search
        OR payment_status LIKE :search
    ";
    $params[':search'] = '%' . $search . '%';
}

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
    DB_USER, DB_PASSWORD,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$totalQuery = $pdo->query("SELECT COUNT(*) FROM scholarship_applications");
$recordsTotal = $totalQuery->fetchColumn();

if ($where) {
    $sql = "SELECT COUNT(*) FROM scholarship_applications $where";
    $countStmt = $pdo->prepare($sql);
    $countStmt->execute($params);
    $recordsFiltered = $countStmt->fetchColumn();
} else {
    $recordsFiltered = $recordsTotal;
}

$sql = "SELECT * FROM scholarship_applications $where ORDER BY $order_by $order_dir LIMIT :start, :length";
$stmt = $pdo->prepare($sql);
if ($where) foreach($params as $k=>$v) $stmt->bindValue($k, $v);
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':length', $length, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = [];
$sr = $start + 1;
foreach($rows as $row){
    $student_name = htmlspecialchars(trim(implode(' ', array_filter([$row['first_name'], $row['middle_name'], $row['last_name']]))));
    $actions = '<a href="view_application.php?id='.$row['id'].'" class="action-btn btn-view" style="background:#0ea5e9;color:#fff;padding:4px 10px;border-radius:6px;margin-right:3px;">👁️ View</a>';
    $actions .= '<a href="edit_application.php?id='.$row['id'].'" class="action-btn btn-edit" style="background:#f59e0b;color:#fff;padding:4px 10px;border-radius:6px;margin-right:3px;">✏️ Edit</a>';
    if($row['payment_status']==='paid' && !empty($row['receipt_token'])){
        $actions .= '<a href="../payment_success.php?token='.urlencode($row['receipt_token']).'" class="action-btn btn-receipt" style="background:#10b981;color:#fff;padding:4px 10px;border-radius:6px;" target="_blank">📄 Receipt</a>';
    }
    $data[] = [
        "sr_no"=>$sr++,
        "id"=>htmlspecialchars($row["id"]),
        "student_name"=>$student_name,
        "class"=>htmlspecialchars($row["class"])."th",
        "mobile"=>htmlspecialchars($row["mobile"]),
        "school_name"=>htmlspecialchars($row["school_name"]),
        "city"=>htmlspecialchars($row["city"]),
        "exam_fee"=>'₹'.number_format($row["exam_fee"],2),
        "payment_status"=>strtoupper(htmlspecialchars($row["payment_status"])),
        "created_at"=>date('d-m-Y', strtotime($row["created_at"])),
        "actions"=>$actions
    ];
}
echo json_encode([
    "draw"=>$draw,
    "recordsTotal"=>intval($recordsTotal),
    "recordsFiltered"=>intval($recordsFiltered),
    "data"=>$data
]);