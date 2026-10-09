<?php
session_start();
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../includes/sms_otp.php';
require_once __DIR__ . '/../includes/staff_repository.php';
require_once __DIR__ . '/../includes/panel_layout.php';

if (empty($_SESSION['admin_auth_user'])) {
    header('Location: admin_login.php?next=staff_manage.php');
    exit;
}

$admin_phone = $_SESSION['admin_auth_user'];
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];
$msg_info = '';
$msg_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($csrf, $_POST['csrf'] ?? '')) {
    $action = $_POST['action'] ?? '';
    if ($action === 'save_staff_ask') {
        $result = save_app_settings(['staff_ask_on_form' => !empty($_POST['staff_ask_on_form']) ? '1' : '0'], $admin_phone);
        $msg_info = $result['ok'] ? 'Form option saved. It applies on the public registration form.' : '';
        $msg_error = $result['ok'] ? '' : ($result['error'] ?? 'Could not save.');
    }
    if ($action === 'add_staff') {
        $result = add_staff($_POST['name'] ?? '', $_POST['mobile'] ?? '', $admin_phone);
        if ($result['ok']) {
            $created = get_staff_by_id((int) $result['id']);
            $formLink = staff_student_form_url($created);
            $msg_info = 'Staff created. Participant form link: ' . $formLink;
        } else {
            $msg_error = $result['error'];
        }
    }
    if ($action === 'toggle_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = ($_POST['status'] ?? '') === 'active' ? 'inactive' : 'active';
        if ($id && set_staff_status($id, $status)) {
            $msg_info = $status === 'active' ? 'Staff activated.' : 'Staff deactivated.';
        } else {
            $msg_error = 'Could not update status.';
        }
    }
    if ($action === 'delete_staff') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id && delete_staff($id)) {
            $msg_info = 'Staff deleted.';
        } else {
            $msg_error = 'Could not delete staff.';
        }
    }
}

ensure_all_staff_referral_codes();
$staffList = get_all_staff();
$formLoginUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/staffagnipankh/adminpanel')), '/')
    . '/staff/login.php';

panel_start([
    'title' => 'Staff logins',
    'role' => 'admin',
    'active' => 'staff',
    'name' => get_admin_name(),
    'phone' => local_phone_display($admin_phone),
    'asset_prefix' => '../',
    'links' => admin_nav_links(),
]);
?>
<div class="hero">
  <div>
    <h2>Staff logins</h2>
    <p>Create a mobile + OTP login for each staff member.</p>
  </div>
  <div class="hero-pill"><?php echo count($staffList); ?> staff</div>
</div>
<p class="page-sub">Staff login link: <strong><?php echo htmlspecialchars($formLoginUrl); ?></strong></p>
<div class="card">
  <div class="card-head"><h3>Show staff question on the form</h3></div>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
    <input type="hidden" name="action" value="save_staff_ask">
    <p class="page-sub" style="margin-top:0;">When on, the registration form asks: “Did someone give you this form?” Yes shows the staff names listed here.</p>
    <label style="display:flex;gap:8px;align-items:center;margin:0 0 12px;">
      <input type="checkbox" name="staff_ask_on_form" value="1" <?php echo staff_ask_on_form() ? 'checked' : ''; ?>>
      <span>On — ask Yes / No and staff name on the form</span>
    </label>
    <button class="btn" type="submit">Save</button>
  </form>
</div>
<div class="card">
  <div class="card-head"><h3>Add new staff</h3></div>
  <?php if ($msg_info): ?><div class="msg info"><?php echo htmlspecialchars($msg_info); ?></div><?php endif; ?>
  <?php if ($msg_error): ?><div class="msg error"><?php echo htmlspecialchars($msg_error); ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
    <input type="hidden" name="action" value="add_staff">
    <div class="row">
      <div class="field">
        <label for="name">Staff name</label>
        <input id="name" name="name" required minlength="2" placeholder="Full name">
      </div>
      <div class="field">
        <label for="mobile">Mobile (username)</label>
        <input id="mobile" name="mobile" required maxlength="10" inputmode="numeric" placeholder="10-digit mobile">
      </div>
      <button class="btn gold" type="submit">Add staff</button>
    </div>
  </form>
</div>
<div class="card">
  <div class="card-head"><h3>All staff</h3></div>
  <?php if (!$staffList): ?>
    <div class="empty"><b>No staff yet</b>Add the first staff above.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="plain">
        <thead><tr><th>Name</th><th>Mobile</th><th>Participant form link</th><th>Status</th><th>Last login</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($staffList as $row): ?>
          <?php $rowLink = staff_student_form_url($row); ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
            <td>+91 <?php echo htmlspecialchars(local_10_digit($row['phone'])); ?></td>
            <td style="max-width:280px;word-break:break-all;font-size:12px;"><?php echo htmlspecialchars($rowLink); ?></td>
            <td><span class="badge <?php echo $row['status']==='active'?'on':'off'; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
            <td><?php echo !empty($row['last_login']) ? htmlspecialchars(date('d M Y, h:i A', strtotime($row['last_login']))) : 'Never'; ?></td>
            <td class="actions">
              <form method="post">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars($row['status']); ?>">
                <button class="btn sm gray" type="submit"><?php echo $row['status']==='active'?'Stop':'Start'; ?></button>
              </form>
              <form method="post" onsubmit="return confirm('Delete this staff?');">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="action" value="delete_staff">
                <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                <button class="btn sm red" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="app-cards">
      <?php foreach ($staffList as $row): ?>
        <div class="app-card">
          <b><?php echo htmlspecialchars($row['name']); ?></b>
          <div class="app-meta">+91 <?php echo htmlspecialchars(local_10_digit($row['phone'])); ?> · <?php echo htmlspecialchars($row['status']); ?><br><?php echo htmlspecialchars(staff_student_form_url($row)); ?></div>
          <div class="actions" style="margin-top:8px;">
            <form method="post">
              <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
              <input type="hidden" name="action" value="toggle_status">
              <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
              <input type="hidden" name="status" value="<?php echo htmlspecialchars($row['status']); ?>">
              <button class="btn sm gray" type="submit"><?php echo $row['status']==='active'?'Stop':'Start'; ?></button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
<script>
document.getElementById('mobile')?.addEventListener('input', e => e.target.value = e.target.value.replace(/\D+/g,'').slice(0,10));
</script>
<?php panel_end();