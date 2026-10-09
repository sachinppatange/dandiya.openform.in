<?php
session_start();
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../includes/sms_otp.php';
require_once __DIR__ . '/../includes/participant_cards.php';
require_once __DIR__ . '/../includes/form_catalog.php';
require_once __DIR__ . '/../includes/panel_layout.php';

if (empty($_SESSION['admin_auth_user'])) {
    header('Location: admin_login.php?next=participant_cards.php');
    exit;
}

$admin_phone = $_SESSION['admin_auth_user'];
$rows = participant_paid_rows();
$from = $rows ? (int) $rows[0]['participant_no'] : 1;
$to = $rows ? (int) $rows[count($rows) - 1]['participant_no'] : 1;

panel_start([
    'title' => 'Participant cards',
    'role' => 'admin',
    'active' => 'cards',
    'name' => get_admin_name(),
    'phone' => local_phone_display($admin_phone),
    'asset_prefix' => '../',
    'links' => admin_nav_links(),
]);
?>
<div class="hero">
  <div>
    <h2>Participant number cards</h2>
    <p>Each paid guest keeps one number. Cards are 3 × 3 inches. One 12 × 18 inch sheet prints 24 cards, 4 across and 6 down.</p>
  </div>
</div>

<div class="card">
  <form method="get" action="participant_print.php" target="_blank" class="row" style="display:flex;flex-wrap:wrap;gap:12px;align-items:end;">
    <label>From number<br>
      <input type="number" name="from" min="1" value="<?php echo $from; ?>" style="width:120px;padding:8px;border-radius:8px;border:1px solid #d7dbe3;">
    </label>
    <label>To number<br>
      <input type="number" name="to" min="1" value="<?php echo $to; ?>" style="width:120px;padding:8px;border-radius:8px;border:1px solid #d7dbe3;">
    </label>
    <label style="display:flex;gap:8px;align-items:center;padding-bottom:8px;">
      <input type="hidden" name="name" value="0">
      <input type="checkbox" name="name" value="1" checked> Show guest name
    </label>
    <button class="btn" type="submit">Open print sheet</button>
  </form>
  <p class="muted" style="margin-top:12px;">In the print dialog choose paper <strong>12 × 18 in</strong>, margins <strong>None</strong>, and scale <strong>100%</strong>. Dashed lines are the cut guides. The guest name is only for pinning the right card; the number is what judges read.</p>
</div>

<div class="card">
  <h3><?php echo count($rows); ?> paid guest<?php echo count($rows) === 1 ? '' : 's'; ?></h3>
  <?php if ($rows === []): ?>
  <p>Numbers appear here after a registration is paid.</p>
  <?php else: ?>
  <div style="overflow:auto;">
  <table class="plain">
    <thead>
      <tr><th>No.</th><th>Name</th><th>College</th><th>Ticket</th><th>Mobile</th></tr>
    </thead>
    <tbody>
    <?php foreach ($rows as $app): ?>
      <tr>
        <td><strong><?php echo htmlspecialchars(participant_number_label($app['participant_no'] ?? 0)); ?></strong></td>
        <td><?php echo htmlspecialchars(participant_guest_name($app)); ?></td>
        <td><?php echo htmlspecialchars((string) ($app['school_name'] ?? '')); ?></td>
        <td><?php echo htmlspecialchars(form_class_label((string) ($app['class'] ?? ''))); ?></td>
        <td><?php echo htmlspecialchars((string) ($app['mobile'] ?? '')); ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>
<?php panel_end(); ?>
