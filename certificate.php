<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/event_stations.php';
require_once __DIR__ . '/includes/form_catalog.php';
require_once __DIR__ . '/includes/app_settings.php';

$token = trim((string) ($_GET['token'] ?? ''));
$stmt = $pdo->prepare("SELECT * FROM scholarship_applications WHERE receipt_token = ? AND payment_status = 'paid'");
$stmt->execute([$token]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$app) {
    http_response_code(404);
    echo 'Certificate not found.';
    exit;
}
ensure_event_stations_schema();
$checkins = event_checkins_for((int) $app['id']);
if (empty($checkins['entry'])) {
    echo '<p style="font-family:sans-serif;padding:32px;text-align:center;">This opens after entry is marked at the gate.</p>';
    exit;
}
$fullName = trim($app['first_name'].' '.$app['middle_name'].' '.$app['last_name']);
$code = event_application_code((int) $app['id']);
$title = landing_page_title();
$logo = panel_logo_src('');
$classLabel = form_class_label((string) ($app['class'] ?? ''));
$when = date('d F Y', strtotime((string) $checkins['entry']['checked_in_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>E-Certificate · <?php echo htmlspecialchars($fullName); ?></title>
<style>
body{margin:0;background:#eef3f8;font-family:Georgia,serif;padding:16px}
.sheet{max-width:720px;margin:0 auto;background:#fff;border:12px solid #0058F0;padding:28px 24px;text-align:center}
.sheet img{height:64px}
h1{color:#0058F0;font-size:1.6rem;margin:12px 0 4px}
.name{font-size:1.8rem;margin:16px 0;color:#0058F0}
.btn{display:inline-block;margin-top:16px;background:#0058F0;color:#fff;text-decoration:none;padding:10px 16px;border-radius:8px;font-family:system-ui,sans-serif;font-weight:700;border:0;cursor:pointer}
@media print{.btn{display:none}body{background:#fff;padding:0}}
</style>
</head>
<body>
<div class="sheet">
  <img src="<?php echo htmlspecialchars($logo); ?>" alt="">
  <h1>Certificate of Participation</h1>
  <p><?php echo htmlspecialchars($title); ?></p>
  <p>This is to certify that</p>
  <div class="name"><?php echo htmlspecialchars($fullName); ?></div>
  <p><?php echo htmlspecialchars($code); ?> · <?php echo htmlspecialchars($classLabel); ?><br>
  <?php echo htmlspecialchars((string) $app['school_name']); ?></p>
  <p>was present on <?php echo htmlspecialchars($when); ?>.</p>
  <button class="btn" type="button" onclick="window.print()">Download / Print</button>
</div>
</body>
</html>
