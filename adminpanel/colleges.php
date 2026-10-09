<?php
session_start();
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../includes/sms_otp.php';
require_once __DIR__ . '/../includes/colleges.php';
require_once __DIR__ . '/../includes/panel_layout.php';
ensure_colleges_schema();

if (empty($_SESSION['admin_auth_user'])) {
    header('Location: admin_login.php?next=colleges.php');
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($csrf, (string) ($_POST['csrf'] ?? ''))) {
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);
    if ($action === 'save_college') {
        $result = college_save($_POST, $id);
        if ($result['ok']) {
            $msg_info = $id ? 'College updated.' : 'College added.';
            $editId = 0;
        } else {
            $msg_error = $result['error'] ?? 'Could not save.';
            $editId = $id;
        }
    }
    if ($action === 'toggle_status') {
        $status = (($_POST['status'] ?? '') === 'active') ? 'inactive' : 'active';
        $result = college_set_status($id, $status);
        $msg_info = $result['ok'] ? 'College updated.' : '';
        $msg_error = $result['ok'] ? '' : ($result['error'] ?? 'Could not update.');
    }
    if ($action === 'delete_college') {
        $result = college_delete($id);
        $msg_info = $result['ok'] ? 'College deleted.' : '';
        $msg_error = $result['ok'] ? '' : ($result['error'] ?? 'Could not delete.');
    }
}

$list = college_list(false);
$editing = null;
if ($editId > 0) {
    $editing = college_by_id($editId);
}
$nextSort = 10;
foreach ($list as $row) {
    if ((int) ($row['is_other'] ?? 0) === 1) {
        continue;
    }
    $nextSort = max($nextSort, (int) $row['sort_order'] + 10);
}

panel_start([
    'title' => 'Colleges',
    'role' => 'admin',
    'active' => 'colleges',
    'name' => get_admin_name(),
    'phone' => local_phone_display($admin_phone),
    'asset_prefix' => '../',
    'links' => admin_nav_links(),
]);
?>
<div class="hero">
  <div>
    <h2>Colleges</h2>
    <p>These names appear in the registration dropdown. Hidden colleges stay on old tickets but are not offered to new guests. Other lets a guest type a name that is not in this list.</p>
  </div>
  <div class="hero-pill"><?php echo count($list); ?> colleges</div>
</div>

<div class="card">
  <div class="card-head"><h3><?php echo $editing ? 'Edit college' : 'Add college'; ?></h3></div>
  <?php if ($msg_info): ?><div class="msg info"><?php echo htmlspecialchars($msg_info); ?></div><?php endif; ?>
  <?php if ($msg_error): ?><div class="msg error"><?php echo htmlspecialchars($msg_error); ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
    <input type="hidden" name="action" value="save_college">
    <input type="hidden" name="id" value="<?php echo (int) ($editing['id'] ?? 0); ?>">
    <div class="row">
      <div class="field">
        <label for="name">College name</label>
        <input id="name" name="name" required minlength="2" maxlength="200" value="<?php echo htmlspecialchars((string) ($editing['name'] ?? '')); ?>">
      </div>
      <div class="field">
        <label for="sort_order">Order</label>
        <input id="sort_order" name="sort_order" type="number" value="<?php echo (int) ($editing['sort_order'] ?? $nextSort); ?>">
      </div>
      <button class="btn gold" type="submit"><?php echo $editing ? 'Update' : 'Add college'; ?></button>
      <?php if ($editing): ?>
        <a class="btn gray" href="colleges.php">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-head"><h3>All colleges</h3></div>
  <?php if (!$list): ?>
    <div class="empty"><b>No colleges yet</b>Add the first college above.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="plain">
        <thead><tr><th>Order</th><th>Name</th><th>On form</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($list as $row):
            $isOther = (int) ($row['is_other'] ?? 0) === 1;
            $active = ($row['status'] ?? '') === 'active';
        ?>
          <tr>
            <td><?php echo (int) $row['sort_order']; ?></td>
            <td><strong><?php echo htmlspecialchars((string) $row['name']); ?></strong></td>
            <td><span class="badge <?php echo $active ? 'on' : 'off'; ?>"><?php echo $active ? 'Shown' : 'Hidden'; ?></span></td>
            <td class="actions">
              <a class="btn sm gray" href="colleges.php?edit=<?php echo (int) $row['id']; ?>">Edit</a>
              <?php if (!$isOther): ?>
              <form method="post">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                <input type="hidden" name="status" value="<?php echo htmlspecialchars((string) $row['status']); ?>">
                <button class="btn sm gray" type="submit"><?php echo $active ? 'Hide' : 'Show'; ?></button>
              </form>
              <form method="post" onsubmit="return confirm('Delete this college?');">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="action" value="delete_college">
                <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                <button class="btn sm red" type="submit">Delete</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php panel_end(); ?>
