<?php
session_start();
require_once __DIR__ . '/../includes/staff_auth.php';
require_once __DIR__ . '/../includes/sms_otp.php';
require_once __DIR__ . '/../includes/staff_repository.php';
require_once __DIR__ . '/../includes/panel_layout.php';
if (!function_exists('form_all_class_labels')) {
    require_once __DIR__ . '/../includes/form_catalog.php';
}

require_staff_login();
$pdo = db();

$staffId = (int) ($_SESSION['staff_auth_id'] ?? 0);
$staffName = (string) ($_SESSION['staff_auth_name'] ?? 'Staff');
$staffPhone = (string) ($_SESSION['staff_auth_user'] ?? '');
$class_labels = form_all_class_labels();
$q = trim($_GET['q'] ?? '');
$filter = $_GET['filter'] ?? 'all';
if (!in_array($filter, ['all','paid','pending','failed'], true)) $filter = 'all';
$classFilter = trim($_GET['class'] ?? '');
if ($classFilter !== '' && !isset($class_labels[$classFilter])) $classFilter = '';

$apps = [];
$counts = ['total' => 0, 'paid' => 0, 'pending' => 0, 'failed' => 0];
try {
    $sql = "SELECT id, first_name, middle_name, last_name, class, mobile, district, city,
                   school_name, payment_status, exam_fee, created_at
            FROM scholarship_applications
            WHERE submitted_by_staff_id = ?";
    $params = [$staffId];
    if ($filter !== 'all') {
        $sql .= " AND payment_status = ?";
        $params[] = $filter;
    }
    if ($classFilter !== '') {
        $sql .= " AND class = ?";
        $params[] = $classFilter;
    }
    if ($q !== '') {
        $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR mobile LIKE ? OR city LIKE ? OR school_name LIKE ?)";
        $like = '%' . $q . '%';
        array_push($params, $like, $like, $like, $like, $like);
    }
    $sql .= " ORDER BY created_at DESC LIMIT 2000";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $apps = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $sum = $pdo->prepare("
        SELECT COUNT(*) total,
               SUM(payment_status='paid') paid,
               SUM(payment_status='pending') pending,
               SUM(payment_status='failed') failed
        FROM scholarship_applications
        WHERE submitted_by_staff_id = ?
    ");
    $sum->execute([$staffId]);
    $row = $sum->fetch(PDO::FETCH_ASSOC) ?: [];
    $counts = [
        'total' => (int) ($row['total'] ?? 0),
        'paid' => (int) ($row['paid'] ?? 0),
        'pending' => (int) ($row['pending'] ?? 0),
        'failed' => (int) ($row['failed'] ?? 0),
    ];
} catch (Throwable $e) {
    $db_error = $e->getMessage();
}

$staffRow = get_staff_by_id($staffId);
$referralCode = $staffRow ? ensure_staff_referral_code($staffId) : '';
$studentFormLink = staff_student_form_url($staffRow ?: ['id' => $staffId, 'referral_code' => $referralCode]);
$qs = static function (array $extra) use ($q, $filter, $classFilter): string {
    $base = ['q' => $q, 'filter' => $filter, 'class' => $classFilter];
    return '?' . http_build_query(array_merge($base, $extra));
};

panel_start([
    'title' => 'My forms',
    'role' => 'staff',
    'active' => 'forms',
    'name' => $staffName,
    'phone' => local_10_digit($staffPhone),
    'asset_prefix' => '../',
    'links' => staff_nav_links(),
    'head' => '<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">',
]);
?>
<div class="hero">
  <div>
    <h2>Namaste, <?php echo htmlspecialchars($staffName); ?></h2>
    <p>Share this link with participants. Your name stays locked on the form.</p>
  </div>
  <div class="hero-pill"><?php echo $counts['total']; ?> total forms</div>
</div>
<div class="stats">
  <a class="stat <?php echo $filter==='all'?'active':''; ?>" href="<?php echo htmlspecialchars($qs(['filter'=>'all'])); ?>">
    <span>Total</span><b><?php echo $counts['total']; ?></b>
  </a>
  <a class="stat ok <?php echo $filter==='paid'?'active':''; ?>" href="<?php echo htmlspecialchars($qs(['filter'=>'paid'])); ?>">
    <span>Paid</span><b><?php echo $counts['paid']; ?></b>
  </a>
  <a class="stat warn <?php echo $filter==='pending'?'active':''; ?>" href="<?php echo htmlspecialchars($qs(['filter'=>'pending'])); ?>">
    <span>Pending</span><b><?php echo $counts['pending']; ?></b>
  </a>
  <a class="stat bad <?php echo $filter==='failed'?'active':''; ?>" href="<?php echo htmlspecialchars($qs(['filter'=>'failed'])); ?>">
    <span>Failed</span><b><?php echo $counts['failed']; ?></b>
  </a>
</div>
<div class="card">
  <div class="card-head"><h3>Your registration link</h3></div>
  <div class="row">
    <div class="field" style="flex:2;">
      <input id="staffFormLink" type="text" readonly value="<?php echo htmlspecialchars($studentFormLink); ?>">
    </div>
    <button class="btn gold" type="button" id="copyStaffLink">Copy link</button>
  </div>
  <p class="page-sub" style="margin:10px 0 0;">Participants log in on this link and complete the form.</p>
</div>
<div class="card">
  <div class="card-head">
    <h3>Application list (<?php echo count($apps); ?>)</h3>
    <div id="exportBtns"></div>
  </div>
  <form class="filters" method="get">
    <input type="search" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search name / mobile / organisation">
    <select name="filter" onchange="this.form.submit()">
      <option value="all" <?php echo $filter==='all'?'selected':''; ?>>All status</option>
      <option value="paid" <?php echo $filter==='paid'?'selected':''; ?>>Paid</option>
      <option value="pending" <?php echo $filter==='pending'?'selected':''; ?>>Pending</option>
      <option value="failed" <?php echo $filter==='failed'?'selected':''; ?>>Failed</option>
    </select>
    <select name="class" onchange="this.form.submit()">
      <option value="">All roles</option>
      <?php foreach ($class_labels as $num => $label): ?>
        <option value="<?php echo $num; ?>" <?php echo $classFilter===$num?'selected':''; ?>><?php echo $label; ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Search</button>
    <a class="btn gray" href="dashboard.php">Reset</a>
  </form>
  <?php if (empty($apps)): ?>
    <div class="empty">
      <b>No forms yet</b>
      Send the link above, or change the filters.
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table id="staffApps" class="display" style="width:100%">
        <thead>
          <tr>
            <th>ID</th><th>Participant</th><th>Role</th><th>Mobile</th>
            <th>Organisation Name</th><th>Status</th><th>Fee</th><th>Date</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($apps as $app): ?>
          <tr>
            <td><?php echo (int) $app['id']; ?></td>
            <td><?php echo htmlspecialchars(trim($app['first_name'].' '.$app['middle_name'].' '.$app['last_name'])); ?></td>
            <td><?php echo htmlspecialchars(form_class_label((string) ($app['class'] ?? ''))); ?></td>
            <td><?php echo htmlspecialchars($app['mobile']); ?></td>
            <td><?php echo htmlspecialchars((string) $app['school_name']); ?></td>
            <td><span class="badge <?php echo htmlspecialchars($app['payment_status']); ?>"><?php echo htmlspecialchars($app['payment_status']); ?></span></td>
            <td>₹<?php echo number_format((float)$app['exam_fee'], 0); ?></td>
            <td><?php echo htmlspecialchars(date('d M Y', strtotime($app['created_at']))); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="app-cards">
      <?php foreach ($apps as $app): ?>
        <div class="app-card">
          <b><?php echo htmlspecialchars(trim($app['first_name'].' '.$app['last_name'])); ?></b>
          <div class="app-meta">
            #<?php echo (int)$app['id']; ?> · <?php echo htmlspecialchars(form_class_label((string) ($app['class'] ?? ''))); ?> · <?php echo htmlspecialchars($app['mobile']); ?><br>
            <?php echo htmlspecialchars((string) $app['school_name']); ?> · ₹<?php echo number_format((float)$app['exam_fee'], 0); ?>
            · <span class="badge <?php echo htmlspecialchars($app['payment_status']); ?>"><?php echo htmlspecialchars($app['payment_status']); ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<?php
$safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', $staffName) ?: 'staff';
$scripts = '<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script>
document.getElementById("copyStaffLink")?.addEventListener("click", async () => {
  const input = document.getElementById("staffFormLink");
  if (!input) return;
  try {
    await navigator.clipboard.writeText(input.value);
    document.getElementById("copyStaffLink").textContent = "Copied";
    setTimeout(() => { document.getElementById("copyStaffLink").textContent = "Copy link"; }, 1500);
  } catch (e) {
    input.select();
    document.execCommand("copy");
  }
});
$(function(){
  if (!$("#staffApps").length) return;
  var table = $("#staffApps").DataTable({
    pageLength: 25,
    order: [[0, "desc"]],
    dom: "frtip",
    buttons: [
      { extend: "excelHtml5", title: "AGNIPANKH_'.addslashes($safeName).'_forms", className: "dt-btn" },
      { extend: "pdfHtml5", title: "AGNIPANKH Staff Forms", orientation: "landscape", pageSize: "A4", className: "dt-btn" },
      { extend: "csvHtml5", title: "AGNIPANKH_'.addslashes($safeName).'_forms", className: "dt-btn" },
      { extend: "print", title: "AGNIPANKH Staff Forms", className: "dt-btn" }
    ]
  });
  table.buttons().container().appendTo("#exportBtns");
});
</script>';
panel_end($scripts);
