<?php
function account_layout_start(string $title, array $user): void
{
    $name = (string) ($user['name'] ?? 'Student');
    $phone = function_exists('local_10_digit') ? local_10_digit((string) ($user['phone'] ?? '')) : '';
    $landing = function_exists('landing_page_title') ? landing_page_title() : 'OpenForm';
    $logo = function_exists('panel_logo_src') ? panel_logo_src('') : '';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($title . ' · ' . $landing); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/theme.css?v=2">
<style>
:root{--ok:#0F9D58;--line:var(--line);--bg:var(--bg)}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);font-family:"Plus Jakarta Sans",system-ui,sans-serif;color:var(--text)}
.top{background:linear-gradient(135deg,var(--brand),var(--brand-deep));color:#fff;padding:12px 16px}
.top-in{max-width:640px;margin:0 auto;display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap}
.top a{color:#fff;text-decoration:none;font-weight:800;font-size:13px;background:rgba(255,255,255,.16);padding:8px 12px;border-radius:999px}
.wrap{max-width:640px;margin:0 auto;padding:16px 14px 88px}
.card{background:#fff;border-radius:16px;box-shadow:var(--shadow);padding:16px;margin-bottom:12px}
.card h2{margin:0 0 8px;font-size:1.15rem;color:var(--brand)}
.muted{color:var(--muted);font-size:13px;font-weight:600}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;background:var(--brand);color:#fff;text-decoration:none;border:0;border-radius:12px;padding:10px 14px;font-weight:800;font-family:inherit;cursor:pointer}
.btn.gray{background:#334155}
.btn.green{background:var(--ok)}
.btn.orange{background:var(--brand-mid)}
.btn.block{display:flex;width:100%;margin-top:8px}
.grid{display:grid;gap:8px}
.actions{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.reg{border:1px solid var(--line);border-radius:14px;padding:12px;margin-top:10px}
.reg b{display:block;font-size:15px}
.badge{display:inline-block;padding:3px 8px;border-radius:999px;font-size:11px;font-weight:800;text-transform:uppercase}
.badge.paid{background:#e8f6ee;color:var(--ok)}
.badge.pending{background:#fff4e5;color:#c56a00}
.badge.failed{background:#fdecea;color:#c62828}
.st{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:10px 0;border-bottom:1px solid var(--line);font-size:14px}
.st:last-child{border:0}
.help{background:#fff7ed;color:#9a3412;border-radius:10px;padding:10px 12px;font-size:13px;font-weight:600}
@media print{.top,.no-print{display:none!important}body{background:#fff}.card{box-shadow:none}}
</style>
</head>
<body>
<div class="top no-print">
  <div class="top-in">
    <div style="display:flex;gap:8px;align-items:center">
      <?php if ($logo): ?><img src="<?php echo htmlspecialchars($logo); ?>" alt="" style="height:36px;width:36px;background:#fff;border-radius:8px;object-fit:contain"><?php endif; ?>
      <div>
        <div style="font-weight:800;font-size:13px;"><?php echo htmlspecialchars($landing); ?></div>
        <div style="opacity:.85;font-size:12px;"><?php echo htmlspecialchars($name); ?><?php echo $phone ? ' · +91 '.$phone : ''; ?></div>
      </div>
    </div>
    <div>
      <a href="my_registrations.php">My registrations</a>
      <a href="index.php">Registration form</a>
      <a href="<?php echo ($user['type'] ?? '') === 'admin' ? 'adminpanel/admin_logout.php' : 'student_logout.php'; ?>">Logout</a>
    </div>
  </div>
</div>
<div class="wrap">
<?php
}

function account_layout_end(): void
{
    echo '</div>';
    if (function_exists('help_whatsapp_button')) {
        echo help_whatsapp_button(true);
    }
    echo '</body></html>';
}
