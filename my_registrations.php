<?php
session_start();
require_once __DIR__ . '/includes/sms_otp.php';
require_once __DIR__ . '/includes/staff_auth.php';
require_once __DIR__ . '/includes/student_repository.php';
require_once __DIR__ . '/includes/form_catalog.php';
require_once __DIR__ . '/includes/app_settings.php';
require_once __DIR__ . '/includes/account_layout.php';
if (!function_exists('event_application_code')) {
    require_once __DIR__ . '/includes/event_stations.php';
}

require_form_login();
$user = get_form_user();
$rows = applications_for_account();
$landing = landing_page_title();

if (!$rows) {
    header('Location: index.php');
    exit;
}

account_layout_start('My registrations', $user);
?>
<div class="card">
  <h2>My registrations</h2>
  <p class="muted">All registrations on this mobile number. You can submit another form for a colleague.</p>
  <a class="btn block" href="index.php">Submit another form</a>
</div>
<?php foreach ($rows as $app):
    $name = trim($app['first_name'].' '.$app['middle_name'].' '.$app['last_name']);
    $st = (string) $app['payment_status'];
    $code = event_application_code((int) $app['id']);
    $class = form_class_label((string) ($app['class'] ?? ''));
    $paid = $st === 'paid' && !empty($app['receipt_token']);
?>
  <div class="reg">
    <span class="badge <?php echo htmlspecialchars($st); ?>"><?php echo htmlspecialchars($st); ?></span>
    <b><?php echo htmlspecialchars($name); ?></b>
    <div class="muted"><?php echo htmlspecialchars($code); ?> · <?php echo htmlspecialchars($class); ?> · <?php echo htmlspecialchars((string) $app['school_name']); ?></div>
    <div class="muted">Fee ₹<?php echo number_format((float) $app['exam_fee'], 2); ?><?php echo !empty($app['coupon_code']) ? ' · Coupon ' . htmlspecialchars((string) $app['coupon_code']) : ''; ?> · <?php echo htmlspecialchars(date('d M Y', strtotime((string) $app['created_at']))); ?></div>
    <div class="actions" style="margin-top:10px;">
      <?php if ($paid): ?>
        <a class="btn" href="icard.php?token=<?php echo urlencode((string) $app['receipt_token']); ?>">Ticket <?php echo htmlspecialchars($code); ?></a>
        <a class="btn gray" href="payment_success.php?token=<?php echo urlencode((string) $app['receipt_token']); ?>">Receipt</a>
      <?php else: ?>
        <a class="btn orange" href="index.php">Pay / continue</a>
        <span class="muted" style="align-self:center;">Complete payment to get your pass number and ticket</span>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
<?php
account_layout_end();
