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
<link rel="stylesheet" href="assets/css/guest.css?v=1">
</head>
<body class="guest-account">
<?php
$guestKicker = '';
$guestAccent = $landing;
if (preg_match('/^SVSS\s+(.+)$/iu', $landing, $guestTitleParts)) {
    $guestKicker = 'SVSS';
    $guestAccent = $guestTitleParts[1];
}
$here = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
?>
<div class="guest-blob" aria-hidden="true"></div>
<header class="guest-top no-print">
  <div class="guest-top-in">
    <a class="guest-brand" href="index.php">
      <?php if ($logo): ?><span class="guest-crest"><img src="<?php echo htmlspecialchars($logo); ?>" alt=""></span><?php endif; ?>
      <span class="guest-word"><?php if ($guestKicker !== ''): ?><?php echo htmlspecialchars($guestKicker); ?> <?php endif; ?><span><?php echo htmlspecialchars($guestAccent); ?></span></span>
    </a>
    <nav class="guest-nav">
      <a class="<?php echo $here === 'my_registrations.php' ? 'on' : ''; ?>" href="my_registrations.php">My registrations</a>
      <a class="<?php echo $here === 'index.php' ? 'on' : ''; ?>" href="index.php">Registration form</a>
      <?php if (function_exists('help_whatsapp_url') && help_whatsapp_url() !== ''): ?>
      <a href="<?php echo htmlspecialchars(help_whatsapp_url()); ?>" target="_blank" rel="noopener">Help</a>
      <?php endif; ?>
      <a href="<?php echo ($user['type'] ?? '') === 'admin' ? 'adminpanel/admin_logout.php' : 'student_logout.php'; ?>">Logout</a>
    </nav>
  </div>
  <div class="guest-who"><?php echo htmlspecialchars($name); ?><?php echo $phone ? ' · +91 ' . htmlspecialchars($phone) : ''; ?></div>
</header>
<div class="wrap">
<?php
}

function account_layout_end(): void
{
    echo '</div></body></html>';
}
