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
    echo 'I-Card not found.';
    exit;
}
$fullName = trim($app['first_name'].' '.$app['middle_name'].' '.$app['last_name']);
$code = event_application_code((int) $app['id']);
$passUrl = event_pass_url($token);
$qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&ecc=M&data=' . rawurlencode($passUrl);
$logo = panel_logo_src('');
$title = landing_page_title();
$classLabel = form_class_label((string) ($app['class'] ?? ''));
$photoSrc = icard_photo_src($app);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>I-Card · <?php echo htmlspecialchars($code); ?></title>
<style>
body{margin:0;font-family:system-ui,sans-serif;background:#eef3f8;padding:16px}
.card{max-width:380px;margin:0 auto;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 12px 32px rgba(13,59,140,.15)}
.head{background:linear-gradient(135deg,#0058F0,#0046C7);color:#fff;padding:16px;display:flex;gap:12px;align-items:center}
.head img{width:52px;height:52px;background:#fff;border-radius:10px;object-fit:contain}
.body{padding:16px;text-align:center}
.body h1{margin:0 0 4px;font-size:1.25rem}
.meta{color:#5c6b7a;font-size:14px;margin:4px 0}
.qr{margin:12px 0}
.btns{display:flex;gap:8px;justify-content:center;margin-top:12px}
.btn{background:#0058F0;color:#fff;text-decoration:none;padding:10px 14px;border-radius:10px;font-weight:700;border:0;cursor:pointer;font-family:inherit}
@media print{.btns{display:none}body{background:#fff;padding:0}.card{box-shadow:none;max-width:none}}
</style>
</head>
<body>
<div class="card">
  <div class="head">
    <?php if ($logo !== ''): ?>
    <img src="<?php echo htmlspecialchars($logo); ?>" alt="">
    <?php endif; ?>
    <div><small>DIGITAL I-CARD</small><div style="font-weight:800"><?php echo htmlspecialchars($title); ?></div></div>
  </div>
  <div class="body">
    <?php if ($photoSrc): ?>
      <img src="<?php echo htmlspecialchars($photoSrc); ?>" alt="" style="width:120px;height:140px;object-fit:cover;border-radius:12px;border:3px solid #0058F0;margin:4px 0 10px;">
    <?php endif; ?>
    <h1><?php echo htmlspecialchars($fullName); ?></h1>
    <div class="meta"><?php echo htmlspecialchars($code); ?> · <?php echo htmlspecialchars($classLabel); ?></div>
    <div class="meta"><?php echo htmlspecialchars((string) $app['school_name']); ?></div>
    <div class="qr"><img src="<?php echo htmlspecialchars($qrImg); ?>" width="200" height="200" alt="QR"></div>
    <div class="btns">
      <button class="btn" type="button" onclick="window.print()">Download / Print</button>
      <a class="btn" href="<?php echo htmlspecialchars($qrImg); ?>" target="_blank">Save QR</a>
    </div>
  </div>
</div>
</body>
</html>
