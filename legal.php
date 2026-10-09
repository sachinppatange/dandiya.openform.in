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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
<style>
body{margin:0;font-family:"Plus Jakarta Sans",system-ui,sans-serif;background:#fff;color:#172033;line-height:1.55}
.wrap{max-width:760px;margin:0 auto;padding:20px 16px 48px}
.card{background:#fff;border:1px solid #eef1f6;border-radius:22px;padding:20px;box-shadow:0 18px 50px rgba(15,23,42,.08)}
.legal-tiny h4{margin:18px 0 10px;color:#1d3fbf;font-size:1.05rem}
.legal-tiny p,.legal-tiny li{font-size:15px;color:#334}
.legal-tiny ol{padding-left:20px}
a{color:#1d3fbf}
.back{display:inline-block;margin-bottom:14px;font-weight:800;text-decoration:none}
h1{color:#1d2a6b}
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
