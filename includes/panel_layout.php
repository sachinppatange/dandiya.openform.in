<?php
if (!function_exists('get_app_settings')) {
    require_once __DIR__ . '/app_settings.php';
}

function panel_start(array $opts): void {
    $title = $opts['title'] ?? 'Dashboard';
    $role = $opts['role'] ?? 'admin';
    $active = $opts['active'] ?? '';
    $name = $opts['name'] ?? '';
    $phone = $opts['phone'] ?? '';
    $prefix = $opts['asset_prefix'] ?? '../';
    $css = $prefix . 'assets/css/panel.css';
    $cssFile = __DIR__ . '/../assets/css/panel.css';
    if (is_file($cssFile)) $css .= '?v=' . filemtime($cssFile);
    $brandName = panel_brand_name();
    $roleLabel = panel_tagline($role);
    $logo = panel_logo_src($prefix);
    $photo = (string) ($opts['photo'] ?? '');
    if ($photo === '' && $role === 'admin') {
        $photo = (string) ($_SESSION['admin_auth_photo'] ?? '');
    }
    $photoSrc = '';
    if ($photo !== '') {
        $abs = dirname(__DIR__) . '/' . ltrim($photo, '/');
        if (is_file($abs)) {
            $photoSrc = $prefix . ltrim($photo, '/') . '?v=' . filemtime($abs);
        }
    }
    $links = $opts['links'] ?? [];
    $initials = panel_initials($name);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?php echo htmlspecialchars($title); ?> · <?php echo htmlspecialchars($brandName); ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
<?php echo $opts['head'] ?? ''; ?>
</head>
<body class="panel role-<?php echo htmlspecialchars($role); ?>">
<aside class="sidebar">
  <div class="brand">
    <img src="<?php echo htmlspecialchars($logo); ?>" alt="<?php echo htmlspecialchars($brandName); ?>">
    <div>
      <b><?php echo htmlspecialchars($brandName); ?></b>
      <small><?php echo htmlspecialchars($roleLabel); ?></small>
    </div>
  </div>
  <nav class="side-nav">
  <?php foreach ($links as $link): ?>
    <?php if (!empty($link['logout'])) continue; ?>
    <a class="side-link <?php echo (($link['key'] ?? '') === $active) ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($link['href']); ?>">
      <span class="side-ico" aria-hidden="true"><?php echo $link['icon'] ?? panel_icon($link['key'] ?? ''); ?></span>
      <span><?php echo htmlspecialchars($link['label']); ?></span>
    </a>
  <?php endforeach; ?>
  </nav>
  <div class="side-foot">
    <div class="side-user">
      <span class="avatar"><?php echo $photoSrc ? '<img src="'.htmlspecialchars($photoSrc).'" alt="">' : htmlspecialchars($initials); ?></span>
      <div>
        <b><?php echo htmlspecialchars($name ?: $roleLabel); ?></b>
        <small><?php echo $phone ? '+91 ' . htmlspecialchars($phone) : ''; ?></small>
      </div>
    </div>
    <?php foreach ($links as $link): ?>
      <?php if (empty($link['logout'])) continue; ?>
      <a class="side-link logout" href="<?php echo htmlspecialchars($link['href']); ?>">
        <span class="side-ico" aria-hidden="true"><?php echo panel_icon('logout'); ?></span>
        <span><?php echo htmlspecialchars($link['label']); ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</aside>
<div class="backdrop" id="backdrop"></div>
<header class="topbar">
  <button class="menu-btn" id="menuBtn" type="button" aria-label="Menu">☰</button>
  <div class="top-copy">
    <div class="crumb"><?php echo htmlspecialchars($roleLabel); ?></div>
    <h1><?php echo htmlspecialchars($title); ?></h1>
  </div>
  <div class="userchip">
    <span class="avatar sm"><?php echo $photoSrc ? '<img src="'.htmlspecialchars($photoSrc).'" alt="">' : htmlspecialchars($initials); ?></span>
    <div>
      <b><?php echo htmlspecialchars($name); ?></b>
      <small><?php echo $phone ? '+91 ' . htmlspecialchars($phone) : ''; ?></small>
    </div>
  </div>
</header>
<main class="content">
<?php
}

function panel_end(?string $scripts = null): void {
    ?>
</main>
<script>
document.getElementById('menuBtn')?.addEventListener('click', () => document.body.classList.toggle('nav-open'));
document.getElementById('backdrop')?.addEventListener('click', () => document.body.classList.remove('nav-open'));
</script>
<?php echo $scripts ?? ''; ?>
</body>
</html>
<?php
}

function admin_nav_links(): array {
    return [
        ['key' => 'dashboard', 'href' => 'admin_dashboard.php', 'label' => 'Dashboard'],
        ['key' => 'registration', 'href' => 'admin_dashboard.php?tab=list', 'label' => 'Registration'],
        ['key' => 'scan', 'href' => 'scan.php', 'label' => 'QR scan'],
        ['key' => 'event', 'href' => 'event_desk.php', 'label' => "Today's counts"],
        ['key' => 'search', 'href' => 'search_applications.php', 'label' => 'Search forms'],
        ['key' => 'staff', 'href' => 'staff_manage.php', 'label' => 'Staff logins'],
        ['key' => 'coupons', 'href' => 'coupons.php', 'label' => 'Coupon codes'],
        ['key' => 'landing', 'href' => 'settings.php?tab=landing', 'label' => 'Landing page'],
        ['key' => 'settings', 'href' => 'settings.php', 'label' => 'Settings'],
        ['key' => 'logout', 'href' => 'admin_logout.php', 'label' => 'Logout', 'logout' => true],
    ];
}

function staff_nav_links(): array {
    return [
        ['key' => 'forms', 'href' => 'dashboard.php', 'label' => 'My forms'],
        ['key' => 'scan', 'href' => 'scan.php', 'label' => 'QR scan'],
        ['key' => 'event', 'href' => 'event_desk.php', 'label' => "Today's counts"],
        ['key' => 'logout', 'href' => 'logout.php', 'label' => 'Logout', 'logout' => true],
    ];
}

function local_phone_display(?string $e164): string {
    $digits = preg_replace('/\D+/', '', (string) $e164);
    return strlen($digits) >= 10 ? substr($digits, -10) : $digits;
}

function panel_initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = strtoupper(substr($parts[0] ?? 'A', 0, 1));
    $last = strtoupper(substr($parts[1] ?? ($parts[0] ?? 'P'), 0, 1));
    return $first . $last;
}

function panel_icon(string $key): string {
    $icons = [
        'dashboard' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>',
        'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3-3"/></svg>',
        'staff' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'coupons' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v6"/><path d="M4 12a2 2 0 0 1 2 2 2 2 0 0 0 2 2h8a2 2 0 0 0 2-2 2 2 0 0 1 2-2"/><path d="M9 9h.01M15 9h.01"/></svg>',
        'settings' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
        'forms' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h8M8 10h8M8 14h5"/><rect x="4" y="3" width="16" height="18" rx="2"/></svg>',
        'registration' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h8M8 10h8M8 14h5"/><rect x="4" y="3" width="16" height="18" rx="2"/></svg>',
        'landing' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/><path d="M8 4v5"/></svg>',
        'scan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3h-3zM20 14v7M14 20h7"/></svg>',
        'event' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg>',
        'logout' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
    ];
    return $icons[$key] ?? $icons['dashboard'];
}
