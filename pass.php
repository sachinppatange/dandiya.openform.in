<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/event_stations.php';
if (!function_exists('form_class_label')) {
    require_once __DIR__ . '/includes/form_catalog.php';
}
if (!function_exists('landing_page_title')) {
    require_once __DIR__ . '/includes/app_settings.php';
}

ensure_event_stations_schema();

$token = trim((string) ($_GET['t'] ?? $_POST['t'] ?? ''));
$operator = event_operator();
$msg = '';
$err = '';

if (!$token) {
    http_response_code(400);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pass</title></head><body style="font-family:sans-serif;padding:32px;text-align:center;"><h2>Invalid QR</h2><p>This pass link is missing a token.</p></body></html>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $operator) {
    $station = (string) ($_POST['station'] ?? '');
    $mark = ($_POST['action'] ?? '') !== 'undo';
    $by = $operator['role'] . ':' . $operator['name'];
    $stmt = $pdo->prepare("SELECT id FROM scholarship_applications WHERE receipt_token = ? AND payment_status = 'paid'");
    $stmt->execute([$token]);
    $appId = (int) ($stmt->fetchColumn() ?: 0);
    if ($appId && event_toggle_checkin($appId, $station, $by, $mark)) {
        $msg = $mark ? 'Marked.' : 'Cleared.';
        header('Location: pass.php?t=' . rawurlencode($token) . '&ok=1');
        exit;
    }
    $err = 'Could not update that station.';
}

$stmt = $pdo->prepare("SELECT * FROM scholarship_applications WHERE receipt_token = ? AND payment_status = 'paid'");
$stmt->execute([$token]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$app) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pass</title></head><body style="font-family:sans-serif;padding:32px;text-align:center;"><h2>Pass not found</h2><p>Only a paid registration can be opened from this QR.</p></body></html>';
    exit;
}

if (isset($_GET['ok'])) {
    $msg = 'Updated.';
}

$fullName = trim($app['first_name'] . ' ' . $app['middle_name'] . ' ' . $app['last_name']);
$code = event_application_code((int) $app['id']);
$instLabel = function_exists('form_institution_label') ? form_institution_label((string) ($app['institution_type'] ?? 'school')) : 'School';
$classLabel = function_exists('form_class_label') ? form_class_label((string) ($app['class'] ?? '')) : (string) $app['class'];
$landingTitle = landing_page_title();
$passUrl = event_pass_url($token);
$checkins = event_checkins_for((int) $app['id']);
$stations = event_stations();
$profile = event_application_profile($app);
$logoSrc = function_exists('panel_logo_src') ? panel_logo_src('') : '';
$photoSrc = function_exists('icard_photo_src') ? icard_photo_src($app) : '';
$loginHint = $operator ? '' : 'Staff / admin login is required to mark kit, breakfast, attendance and other stations.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?php echo htmlspecialchars($code . ' · ' . $fullName); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
<style>
:root { --blue:#0058F0; --orange:#3D7FFF; --ok:#0F9D58; --line:#D7E3F7; }
*{box-sizing:border-box}
body{margin:0;background:#eef3f8;font-family:"Plus Jakarta Sans",system-ui,sans-serif;color:#122}
.wrap{max-width:520px;margin:0 auto;padding:16px 14px 40px}
.card{background:#fff;border-radius:18px;box-shadow:0 10px 30px rgba(13,59,140,.12);overflow:hidden;margin-bottom:14px}
.icard-top{background:linear-gradient(135deg,#0058F0,#0039A6);color:#fff;padding:16px 18px;display:flex;gap:12px;align-items:center}
.icard-top img{width:48px;height:48px;border-radius:10px;background:#fff;object-fit:contain}
.icard-top img.face{width:64px;height:64px;object-fit:cover;margin-left:auto;border:2px solid #fff}
.icard-top small{opacity:.85;font-weight:700;letter-spacing:.08em;text-transform:uppercase;font-size:10px}
.icard-top b{display:block;font-size:1.15rem;margin-top:2px}
.body{padding:16px 18px}
.meta{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:12px 0}
.meta div{background:#f4f7fb;border-radius:10px;padding:10px}
.meta span{display:block;color:#5c6b7a;font-size:11px;font-weight:700;text-transform:uppercase}
.meta b{font-size:14px}
.row{display:flex;justify-content:space-between;gap:8px;padding:8px 0;border-bottom:1px solid var(--line);font-size:14px}
.row:last-child{border:0}
.group-title{margin:16px 0 6px;font-size:12px;letter-spacing:.06em;text-transform:uppercase;color:#0058F0}
.dl{margin:0}
.dl div{display:flex;justify-content:space-between;gap:10px;padding:8px 0;border-bottom:1px solid var(--line);font-size:14px}
.dl dt{color:#5c6b7a;font-weight:700;min-width:130px}
.dl dd{margin:0;font-weight:700;text-align:right;word-break:break-word}
.qrbox{display:flex;flex-direction:column;align-items:center;padding:8px 0 4px}
.qrbox canvas,.qrbox img{border-radius:8px}
.hint{color:#5c6b7a;font-size:12px;text-align:center;margin:8px 0 0}
.stations{display:grid;gap:8px}
.st{display:flex;align-items:center;justify-content:space-between;gap:10px;border:1px solid var(--line);border-radius:12px;padding:10px 12px}
.st.done{border-color:#9fd4b3;background:#f1faf5}
.st b{display:block;font-size:14px}
.st small{color:#5c6b7a}
.badge{font-size:11px;font-weight:800;padding:4px 8px;border-radius:999px;background:#eee}
.st.done .badge{background:#1a7f4c;color:#fff}
form.inline{margin:0}
button,a.btn{border:0;border-radius:9px;padding:8px 12px;font-weight:800;font-family:inherit;cursor:pointer;text-decoration:none;display:inline-block}
.mark{background:var(--blue);color:#fff}
.undo{background:#fff;color:#c62828;border:1px solid #f3c4c4}
.flash{background:#e8f6ee;color:#1a7f4c;padding:10px 12px;border-radius:10px;font-weight:700;margin-bottom:10px}
.err{background:#fdecea;color:#c62828;padding:10px 12px;border-radius:10px;font-weight:700;margin-bottom:10px}
.login{font-size:13px;background:#fff7ed;color:#9a3412;padding:10px 12px;border-radius:10px}
@media print {
  body{background:#fff}
  .no-print{display:none !important}
  .card{box-shadow:none;border:1px solid #ccc}
}
</style>
</head>
<body>
<div class="wrap">
  <?php if ($msg): ?><div class="flash"><?php echo htmlspecialchars($msg); ?></div><?php endif; ?>
  <?php if ($err): ?><div class="err"><?php echo htmlspecialchars($err); ?></div><?php endif; ?>

  <div class="card">
    <div class="icard-top">
      <?php if ($logoSrc !== ''): ?>
      <img src="<?php echo htmlspecialchars($logoSrc); ?>" alt="">
      <?php endif; ?>
      <div>
        <small>Digital I-Card</small>
        <b><?php echo htmlspecialchars($fullName); ?></b>
        <span><?php echo htmlspecialchars($code); ?> · Paid</span>
      </div>
      <?php if ($photoSrc): ?>
        <img class="face" src="<?php echo htmlspecialchars($photoSrc); ?>" alt="">
      <?php endif; ?>
    </div>
    <div class="body">
      <div class="qrbox">
        <div id="passQr"></div>
        <p class="hint">Scan for Digital I-Card, kit, breakfast, attendance, feedback and e-certificate</p>
      </div>
      <div class="meta">
        <div><span>Role</span><b><?php echo htmlspecialchars($classLabel); ?></b></div>
        <div><span><?php echo htmlspecialchars($instLabel); ?></span><b><?php echo htmlspecialchars((string) $app['school_name']); ?></b></div>
        <div><span>Mobile</span><b><?php echo htmlspecialchars((string) ($app['mobile'] ?: '—')); ?></b></div>
      </div>
      <?php foreach ($profile as $group => $rows): ?>
        <div class="group-title"><?php echo htmlspecialchars($group); ?></div>
        <dl class="dl">
          <?php foreach ($rows as $label => $value): ?>
          <div>
            <dt><?php echo htmlspecialchars($label); ?></dt>
            <dd><?php echo htmlspecialchars((string) $value); ?></dd>
          </div>
          <?php endforeach; ?>
        </dl>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <div class="body">
      <b style="display:block;margin-bottom:10px;">Event stations</b>
      <?php if ($loginHint): ?>
        <p class="login no-print"><?php echo htmlspecialchars($loginHint); ?></p>
      <?php endif; ?>
      <div class="stations">
        <?php foreach ($stations as $key => $st):
            $done = isset($checkins[$key]);
            $when = $done ? date('d M, h:i A', strtotime((string) $checkins[$key]['checked_in_at'])) : '';
        ?>
          <div class="st <?php echo $done ? 'done' : ''; ?>">
            <div>
              <b><?php echo htmlspecialchars($st['label']); ?></b>
              <small><?php echo $done ? htmlspecialchars('Done · ' . $when) : htmlspecialchars($st['hint']); ?></small>
            </div>
            <span class="badge"><?php echo $done ? 'DONE' : 'PENDING'; ?></span>
            <?php if ($operator): ?>
            <form class="inline no-print" method="post">
              <input type="hidden" name="t" value="<?php echo htmlspecialchars($token); ?>">
              <input type="hidden" name="station" value="<?php echo htmlspecialchars($key); ?>">
              <?php if ($done): ?>
                <input type="hidden" name="action" value="undo">
                <button class="undo" type="submit">Undo</button>
              <?php else: ?>
                <button class="mark" type="submit">Mark</button>
              <?php endif; ?>
            </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
      <p class="hint no-print" style="margin-top:14px;">
        <a class="btn mark" href="payment_success.php?token=<?php echo urlencode($token); ?>">Open receipt</a>
        <?php if ($operator && $operator['role'] === 'admin'): ?>
          <a class="btn mark" href="adminpanel/scan.php" style="background:#334155;margin-left:6px;">Scanner</a>
          <a class="btn mark" href="adminpanel/event_desk.php" style="background:#0e8f7a;margin-left:6px;">Today’s counts</a>
        <?php elseif ($operator): ?>
          <a class="btn mark" href="staff/scan.php" style="background:#334155;margin-left:6px;">Scanner</a>
          <a class="btn mark" href="staff/event_desk.php" style="background:#0e8f7a;margin-left:6px;">Today’s counts</a>
        <?php endif; ?>
      </p>
    </div>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById('passQr'), {
  text: <?php echo json_encode($passUrl); ?>,
  width: 168,
  height: 168,
  correctLevel: QRCode.CorrectLevel.M
});
</script>
</body>
</html>
