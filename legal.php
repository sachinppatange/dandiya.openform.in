<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/app_settings.php';

$title = landing_page_title();
$h = static function (string $t): string {
    return htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Declaration &amp; terms · <?php echo $h($title); ?></title>
<style>
body{margin:0;font-family:system-ui,sans-serif;background:#f4f6f9;color:#122;line-height:1.55}
.wrap{max-width:760px;margin:0 auto;padding:20px 16px 48px}
.card{background:#fff;border-radius:14px;padding:20px;box-shadow:0 8px 24px rgba(15,23,42,.06)}
.legal-tiny h4{margin:18px 0 10px;color:#0058F0;font-size:1.05rem}
.legal-tiny p,.legal-tiny li{font-size:15px;color:#334}
.legal-tiny ol{padding-left:20px}
a{color:#0058F0}
.back{display:inline-block;margin-bottom:14px;font-weight:700;text-decoration:none}
</style>
</head>
<body>
<div class="wrap">
  <a class="back" href="login.php">← Home</a>
  <div class="card">
    <h1 style="margin-top:0;font-size:1.3rem;"><?php echo $h($title); ?></h1>
    <?php echo legal_documents_html(); ?>
  </div>
</div>
<?php if (function_exists('help_whatsapp_button')) { echo help_whatsapp_button(true); } ?>
</body>
</html>
