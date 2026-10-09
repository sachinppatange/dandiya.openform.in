<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/sms_otp.php';
require_once __DIR__ . '/includes/staff_auth.php';
require_once __DIR__ . '/includes/student_repository.php';
require_once __DIR__ . '/includes/form_catalog.php';
require_once __DIR__ . '/includes/app_settings.php';
require_once __DIR__ . '/includes/event_stations.php';
require_once __DIR__ . '/includes/account_layout.php';

require_form_login();
ensure_form_catalog_schema();
$user = get_form_user();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM scholarship_applications WHERE id = ?');
$stmt->execute([$id]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$app || !account_owns_application($app)) {
    http_response_code(404);
    echo '<p style="font-family:sans-serif;padding:24px;text-align:center;">This registration was not found on your login.</p>';
    exit;
}

$photoMsg = '';
$photoErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo']) && !empty($_FILES['photo']['tmp_name'])) {
    $up = save_icard_photo($_FILES['photo'], (int) $app['id']);
    if ($up['ok'] && empty($up['skipped'])) {
        $stmt->execute([$id]);
        $app = $stmt->fetch(PDO::FETCH_ASSOC) ?: $app;
        $photoMsg = 'Photo saved. Open the I-Card to see it.';
    } else {
        $photoErr = $up['error'] ?? 'Could not save photo.';
    }
}
$fullName = trim($app['first_name'].' '.$app['middle_name'].' '.$app['last_name']);
$code = event_application_code((int) $app['id']);
$token = (string) ($app['receipt_token'] ?? '');
$paid = ($app['payment_status'] ?? '') === 'paid' && $token !== '';
$checkins = event_checkins_for((int) $app['id']);
$stations = event_stations();
$attended = isset($checkins['entry']);
$s = get_app_settings();
$group = trim((string) ($s['event_group_url'] ?? ''));
$photos = trim((string) ($s['event_photos_url'] ?? ''));
$feedback = trim((string) ($s['event_feedback_url'] ?? ''));
$wa = preg_replace('/\D+/', '', (string) ($s['landing_whatsapp'] ?? ''));
$wa = strlen($wa) >= 10 ? substr($wa, -10) : '';
$venue = trim((string) ($s['landing_venue'] ?? ''));
$classLabel = form_class_label((string) ($app['class'] ?? ''));
$passUrl = $paid ? event_pass_url($token) : '';
$qrImg = $passUrl !== '' ? ('https://api.qrserver.com/v1/create-qr-code/?size=280x280&ecc=M&data=' . rawurlencode($passUrl)) : '';
$photoSrc = icard_photo_src($app);

account_layout_start($fullName, $user);
?>
<div class="card">
  <span class="badge <?php echo htmlspecialchars((string) $app['payment_status']); ?>"><?php echo htmlspecialchars((string) $app['payment_status']); ?></span>
  <h2><?php echo htmlspecialchars($fullName); ?></h2>
  <?php if ($photoSrc): ?>
    <img src="<?php echo htmlspecialchars($photoSrc); ?>" alt="" style="width:96px;height:96px;object-fit:cover;border-radius:12px;margin:8px 0;border:1px solid #dce5ef;">
  <?php endif; ?>
  <p class="muted"><?php echo htmlspecialchars($code); ?> · <?php echo htmlspecialchars($classLabel); ?> · <?php echo htmlspecialchars((string) $app['school_name']); ?></p>
  <?php if ($venue !== ''): ?><p class="muted"><?php echo nl2br(htmlspecialchars($venue)); ?></p><?php endif; ?>
  <p class="muted">+91 <?php echo htmlspecialchars((string) $app['mobile']); ?></p>
  <?php if (!empty($app['coupon_code'])): ?>
  <p class="muted">Coupon: <?php echo htmlspecialchars((string) $app['coupon_code']); ?> · Fee ₹<?php echo number_format((float) $app['exam_fee'], 2); ?></p>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Profile photo</h2>
  <?php if ($photoMsg): ?><p class="muted" style="color:#1a7f4c;"><?php echo htmlspecialchars($photoMsg); ?></p><?php endif; ?>
  <?php if ($photoErr): ?><p class="help"><?php echo htmlspecialchars($photoErr); ?></p><?php endif; ?>
  <p class="muted">Optional face photo. JPG or PNG, max 3 MB.</p>
  <form method="post" enctype="multipart/form-data">
    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required style="width:100%;margin:8px 0;">
    <button class="btn block" type="submit" name="upload_photo" value="1"><?php echo $photoSrc ? 'Change photo' : 'Upload photo'; ?></button>
  </form>
</div>

<?php if ($paid): ?>
<div class="card">
  <h2>Downloads</h2>
  <div class="actions">
    <a class="btn" href="icard.php?token=<?php echo urlencode($token); ?>">Entry ticket <?php echo htmlspecialchars($code); ?></a>
    <a class="btn gray" href="<?php echo htmlspecialchars($qrImg); ?>" download="qr-<?php echo htmlspecialchars($code); ?>.png" target="_blank">QR Code</a>
  </div>
  <a class="btn block gray" href="payment_success.php?token=<?php echo urlencode($token); ?>">Payment receipt</a>
  <?php if ($attended): ?>
    <a class="btn block green" href="certificate.php?token=<?php echo urlencode($token); ?>">E-Certificate</a>
  <?php else: ?>
    <p class="help" style="margin-top:10px;">This opens after entry is marked at the gate.</p>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Event day</h2>
  <?php foreach (event_stations() as $key => $stMeta):
      $lab = (string) ($stMeta['label'] ?? $key);
      $done = isset($checkins[$key]);
      $when = $done ? date('d M, h:i A', strtotime((string) $checkins[$key]['checked_in_at'])) : '';
  ?>
    <div class="st">
      <div>
        <b><?php echo htmlspecialchars($lab); ?></b>
        <div class="muted"><?php echo $done ? htmlspecialchars('Done · ' . $when) : 'Not yet'; ?></div>
      </div>
      <span class="badge <?php echo $done ? 'paid' : 'pending'; ?>"><?php echo $done ? 'Done' : 'Pending'; ?></span>
    </div>
  <?php endforeach; ?>
</div>
<?php else: ?>
<div class="card">
  <p class="help">This form is not paid yet. Your pass number and ticket open after successful payment.</p>
  <a class="btn block orange" href="index.php">Go to form / payment</a>
</div>
<?php endif; ?>

<div class="card">
  <h2>Helpful links</h2>
  <div class="grid">
    <?php if ($feedback !== ''): ?>
      <a class="btn block" href="<?php echo htmlspecialchars($feedback); ?>" target="_blank" rel="noopener">Feedback form</a>
    <?php else: ?>
      <p class="muted">Feedback link will appear here when admin adds it in Settings.</p>
    <?php endif; ?>
    <?php if ($group !== ''): ?>
      <a class="btn block green" href="<?php echo htmlspecialchars($group); ?>" target="_blank" rel="noopener">WhatsApp group</a>
    <?php endif; ?>
    <?php if ($photos !== ''): ?>
      <a class="btn block gray" href="<?php echo htmlspecialchars($photos); ?>" target="_blank" rel="noopener">Event photographs</a>
    <?php endif; ?>
    <?php if ($wa !== ''): ?>
      <a class="btn block orange" href="<?php echo htmlspecialchars(help_whatsapp_url()); ?>" target="_blank" rel="noopener">Help / Support</a>
    <?php endif; ?>
    <a class="btn block gray" href="my_registrations.php">All my forms</a>
    <a class="btn block" href="index.php">Register another student</a>
  </div>
</div>
<?php
account_layout_end();
