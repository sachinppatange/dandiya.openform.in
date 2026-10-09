<?php
session_start();
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../includes/sms_otp.php';
require_once __DIR__ . '/../includes/form_catalog.php';
require_once __DIR__ . '/../includes/coupons.php';
require_once __DIR__ . '/../includes/panel_layout.php';
ensure_form_catalog_schema();

if (empty($_SESSION['admin_auth_user'])) {
    header('Location: admin_login.php?next=coupons.php');
    exit;
}

$admin_phone = $_SESSION['admin_auth_user'];
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];
$msg_info = '';
$msg_error = '';
$editId = (int) ($_GET['edit'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($csrf, $_POST['csrf'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_toggle') {
        $result = save_app_settings(['coupons_on_form' => !empty($_POST['coupons_on_form']) ? '1' : '0'], $admin_phone);
        $msg_info = $result['ok'] ? 'Coupon box on the form is updated.' : '';
        $msg_error = $result['ok'] ? '' : ($result['error'] ?? 'Could not save.');
    }
    if ($action === 'save_coupon') {
        $id = (int) ($_POST['id'] ?? 0);
        $result = coupon_save($_POST, $id);
        if ($result['ok']) {
            $msg_info = $id ? 'Coupon updated.' : 'Coupon added.';
            $editId = 0;
        } else {
            $msg_error = $result['error'] ?? 'Could not save.';
            $editId = $id;
        }
    }
    if ($action === 'toggle_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = ($_POST['status'] ?? '') === 'active' ? 'inactive' : 'active';
        $msg_info = ($id && coupon_set_status($id, $status)) ? 'Coupon status updated.' : '';
        if ($msg_info === '') {
            $msg_error = 'Could not update status.';
        }
    }
    if ($action === 'delete_coupon') {
        $id = (int) ($_POST['id'] ?? 0);
        $msg_info = ($id && coupon_delete($id)) ? 'Coupon deleted.' : '';
        if ($msg_info === '') {
            $msg_error = 'Could not delete.';
        }
    }
}

$list = coupon_list();
$editing = null;
if ($editId > 0) {
    foreach ($list as $row) {
        if ((int) $row['id'] === $editId) {
            $editing = $row;
            break;
        }
    }
}

panel_start([
    'title' => 'Coupon codes',
    'role' => 'admin',
    'active' => 'coupons',
    'name' => get_admin_name(),
    'phone' => local_phone_display($admin_phone),
    'asset_prefix' => '../',
    'links' => admin_nav_links(),
]);
?>
<div class="hero">
  <div>
    <h2>Coupon codes</h2>
    <p>Each code sets the registration fee. After that, the usual gateway percentage is added.</p>
  </div>
  <div class="hero-pill"><?php echo count($list); ?> codes</div>
</div>

<div class="card">
  <div class="card-head"><h3>Show on registration form</h3></div>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
    <input type="hidden" name="action" value="save_toggle">
    <label style="display:flex;gap:8px;align-items:center;margin:0 0 12px;">
      <input type="checkbox" name="coupons_on_form" value="1" <?php echo coupons_on_form() ? 'checked' : ''; ?>>
      <span>On — coupon box above the fee</span>
    </label>
    <button class="btn" type="submit">Save</button>
  </form>
</div>

<div class="card">
  <div class="card-head"><h3><?php echo $editing ? 'Edit coupon' : 'Add coupon'; ?></h3></div>
  <?php if ($msg_info): ?><div class="msg info"><?php echo htmlspecialchars($msg_info); ?></div><?php endif; ?>
  <?php if ($msg_error): ?><div class="msg error"><?php echo htmlspecialchars($msg_error); ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
    <input type="hidden" name="action" value="save_coupon">
    <input type="hidden" name="id" value="<?php echo (int) ($editing['id'] ?? 0); ?>">
    <div class="row">
      <div class="field">
        <label for="code">Code</label>
        <input id="code" name="code" required minlength="3" maxlength="24" placeholder="e.g. SCHOOL50" value="<?php echo htmlspecialchars((string) ($editing['code'] ?? '')); ?>" style="text-transform:uppercase;">
      </div>
      <div class="field">
        <label for="fee_amount">Fee when used (₹)</label>
        <input id="fee_amount" name="fee_amount" type="number" min="1" max="99999" step="1" required value="<?php echo htmlspecialchars((string) (isset($editing['fee_amount']) ? (int) $editing['fee_amount'] : 100)); ?>">
      </div>
      <div class="field">
        <label for="max_uses">How many times it can be used</label>
        <input id="max_uses" name="max_uses" type="number" min="1" max="99999" required value="<?php echo htmlspecialchars((string) ($editing['max_uses'] ?? 50)); ?>">
      </div>
      <div class="field">
        <label for="note">Note (optional)</label>
        <input id="note" name="note" maxlength="120" placeholder="Batch / staff" value="<?php echo htmlspecialchars((string) ($editing['note'] ?? '')); ?>">
      </div>
      <button class="btn gold" type="submit"><?php echo $editing ? 'Update' : 'Add coupon'; ?></button>
      <?php if ($editing): ?>
        <a class="btn gray" href="coupons.php">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-head"><h3>All coupons</h3></div>
  <?php if (!$list): ?>
    <div class="empty"><b>No coupons yet</b>Add the first code above.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="plain">
        <thead><tr><th>Code</th><th>Fee</th><th>Used / limit</th><th>Note</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($list as $row): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars((string) $row['code']); ?></strong></td>
            <td>₹<?php echo number_format((float) $row['fee_amount'], 0); ?></td>
            <td><?php echo (int) $row['used_count']; ?> / <?php echo (int) $row['max_uses']; ?></td>
            <td><?php echo htmlspecialchars((string) ($row['note'] ?? '')); ?></td>
            <td><span class="badge <?php echo ($row['status'] ?? '') === 'active' ? 'on' : 'off'; ?>"><?php echo htmlspecialchars((string) $row['status']); ?></span></td>
            <td class="actions">
              <a class="btn sm gray" href="coupons.php?edit=<?php echo (int) $row['id']; ?>">Edit</a>
              <form method="post">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars((string) $row['status']); ?>">
                <button class="btn sm gray" type="submit"><?php echo ($row['status'] ?? '') === 'active' ? 'Stop' : 'Start'; ?></button>
              </form>
              <form method="post" onsubmit="return confirm('Delete this coupon?');">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="action" value="delete_coupon">
                <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                <button class="btn sm red" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php panel_end(); ?>
