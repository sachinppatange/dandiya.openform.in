<?php
session_start();
require_once __DIR__ . '/includes/app_settings.php';
require_once __DIR__ . '/includes/form_catalog.php';
require_once __DIR__ . '/includes/staff_repository.php';
require_once __DIR__ . '/includes/staff_auth.php';

$incoming = $_GET;
if (!landing_is_enabled()) {
    header('Location: login.php' . ($incoming ? ('?' . http_build_query($incoming)) : ''));
    exit;
}

capture_staff_referral();

$title = landing_page_title();
$subtitle = landing_page_subtitle();
$when = form_workshop_period_label();
$venue = form_event_venue_label();
$poster = landing_poster_src('');
$highlights = landing_lines('landing_highlights');
$showFee = landing_show_fees();
$fee = $showFee ? landing_entry_fee() : null;
$cta = trim((string) get_app_setting('landing_cta', 'Register for Dandiya Night'));
if ($cta === '') {
    $cta = 'Register for Dandiya Night';
}
$loggedIn = is_form_user_logged_in();
$loginQuery = $incoming;
$loginQuery['next'] = 'index.php';
$ctaHref = $loggedIn ? 'index.php' : ('login.php?' . http_build_query($loginQuery));
$wa = function_exists('help_whatsapp_url') ? help_whatsapp_url() : '';

$dateLines = landing_lines('landing_dates');
if ($dateLines === [] && $when !== '') {
    $dateLines = [$when];
}
$sections = [
    ['About the event', landing_lines('landing_about'), 'text'],
    ['When', $dateLines, 'list'],
    ['Where', array_values(array_filter(array_map('trim', preg_split("/\r\n|\n|\r/", $venue) ?: []))), 'list'],
    ['Who can register', landing_lines('landing_who'), 'list'],
    ['How to register', landing_lines('landing_how'), 'list'],
    ['What you fill in', landing_lines('landing_need'), 'list'],
    ['Help', landing_lines('landing_helpline'), 'list'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php echo htmlspecialchars($title); ?></title>
<link rel="stylesheet" href="assets/css/theme.css?v=2">
<style>
  * { box-sizing: border-box; }
  body { margin: 0; background: var(--bg); color: var(--text); font-family: system-ui, sans-serif; }
  .top { background: linear-gradient(135deg, var(--brand), var(--brand-deep)); color: #fff; padding: 28px 16px 22px; }
  .top-in, .wrap { max-width: 720px; margin: 0 auto; }
  .top h1 { margin: 0; font-size: 1.7rem; }
  .top p { margin: 8px 0 0; opacity: .92; }
  .wrap { padding: 16px 16px 40px; }
  .poster { display: block; width: 100%; border-radius: 16px; margin-top: -28px; background: #fff; box-shadow: var(--shadow); }
  .chips { display: flex; flex-wrap: wrap; gap: 8px; margin: 14px 0; }
  .chips span { background: #fff; border: 1px solid var(--line); border-radius: 999px; padding: 6px 10px; font-size: 13px; font-weight: 700; }
  .fee { background: #fff; border-radius: 16px; box-shadow: var(--shadow); padding: 16px; display: flex; justify-content: space-between; gap: 12px; align-items: center; margin: 14px 0; }
  .fee b { display: block; font-size: 1.6rem; color: var(--brand); }
  .fee small { color: var(--muted); font-weight: 600; }
  .card { background: #fff; border-radius: 16px; box-shadow: var(--shadow); padding: 16px; margin-top: 12px; }
  .card h2 { margin: 0 0 8px; font-size: 1.05rem; color: var(--brand); }
  .card p { margin: 0 0 8px; line-height: 1.5; }
  .card ul { margin: 0; padding-left: 18px; }
  .card li { margin: 4px 0; }
  .cta { display: block; text-align: center; background: var(--brand); color: #fff; text-decoration: none; font-weight: 800; border-radius: 14px; padding: 14px 16px; margin-top: 16px; }
  .cta.ghost { background: #fff; color: var(--brand); border: 1px solid var(--line); margin-top: 8px; }
</style>
</head>
<body>
<header class="top">
  <div class="top-in">
    <h1><?php echo htmlspecialchars($title); ?></h1>
    <?php if ($subtitle !== ''): ?><p><?php echo htmlspecialchars($subtitle); ?></p><?php endif; ?>
  </div>
</header>
<main class="wrap">
  <?php if ($poster !== ''): ?>
    <img class="poster" src="<?php echo htmlspecialchars($poster); ?>" alt="<?php echo htmlspecialchars($title); ?>">
  <?php endif; ?>
  <?php if ($highlights): ?>
  <div class="chips">
    <?php foreach ($highlights as $chip): ?><span><?php echo htmlspecialchars($chip); ?></span><?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php if ($fee): ?>
  <div class="fee">
    <div>
      <small>Entry fee<?php echo $fee['payer'] === 'user' && $fee['platform'] > 0 ? ' · includes payment charges' : ''; ?></small>
      <b><?php echo htmlspecialchars($fee['label']); ?></b>
    </div>
    <small>One guest</small>
  </div>
  <?php endif; ?>
  <a class="cta" href="<?php echo htmlspecialchars($ctaHref); ?>"><?php echo htmlspecialchars($loggedIn ? 'Continue registration' : $cta); ?></a>
  <?php foreach ($sections as [$heading, $lines, $kind]):
      $lines = array_values(array_filter(array_map(static fn($line) => trim((string) $line), $lines ?: [])));
      if ($lines === []) {
          continue;
      }
  ?>
  <section class="card">
    <h2><?php echo htmlspecialchars($heading); ?></h2>
    <?php if ($kind === 'text'): ?>
      <?php foreach ($lines as $line): ?><p><?php echo htmlspecialchars($line); ?></p><?php endforeach; ?>
    <?php else: ?>
      <ul>
        <?php foreach ($lines as $line): ?><li><?php echo htmlspecialchars($line); ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </section>
  <?php endforeach; ?>
  <?php if ($wa !== ''): ?>
    <a class="cta ghost" href="<?php echo htmlspecialchars($wa); ?>" target="_blank" rel="noopener">Help on WhatsApp</a>
  <?php endif; ?>
</main>
</body>
</html>
