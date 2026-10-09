<?php
/**
 * Simple event-day counts for admin / staff.
 */
if (!function_exists('form_class_label')) {
    require_once __DIR__ . '/form_catalog.php';
}

$f = event_desk_filters($_GET);
$stats = event_desk_stats($f);
$rows = event_desk_rows($f);
$stations = event_stations();
$stationLabel = $stations[$f['station']]['label'] ?? $f['station'];
$listTitle = [
    'remaining' => 'Still remaining',
    'today' => 'Done today',
    'done' => 'Done so far',
][$f['view']];

if (($_GET['export'] ?? '') === 'csv') {
    $filename = 'event_' . $f['station'] . '_' . $f['view'] . '_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Code', 'Name', 'Role', 'Organisation Name', 'Mobile', 'Status', 'Checked at', 'Checked by']);
    foreach ($rows as $app) {
        fputcsv($out, [
            $app['id'],
            event_application_code((int) $app['id']),
            event_full_name($app),
            form_class_label((string) ($app['class'] ?? '')),
            $app['school_name'] ?? '',
            $app['mobile'] ?? '',
            $app['payment_status'] ?? '',
            $app['checked_in_at'] ?? '',
            $app['checked_in_by'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

$eventOptions = [];

$qs = static function (array $extra = []) use ($f): string {
    return '?' . http_build_query(array_merge($f, $extra));
};
$paid = (int) $stats['paid'];
?>
<div class="dash-page">
  <div class="card">
    <div class="card-head">
      <h3>Today’s counts</h3>
      <a class="btn sm" href="<?php echo htmlspecialchars($scanHref); ?>">Scan QR</a>
    </div>
    <p class="hint-block">Tap a number to see the names. Remaining = paid participants not yet marked at that counter.</p>
    <form class="filters" method="get">
      <input type="hidden" name="station" value="<?php echo htmlspecialchars($f['station']); ?>">
      <input type="hidden" name="view" value="<?php echo htmlspecialchars($f['view']); ?>">
    </form>
    <p class="hint-block" style="margin:0;"><?php echo number_format($paid); ?> paid registrations in this filter.</p>
  </div>

  <div class="count-grid">
    <?php foreach ($stats['stations'] as $key => $st): ?>
      <div class="count-card <?php echo $f['station'] === $key ? 'active' : ''; ?>">
        <h4><?php echo htmlspecialchars($st['label']); ?></h4>
        <div class="count-nums">
          <a href="<?php echo htmlspecialchars($qs(['station' => $key, 'view' => 'today'])); ?>#names">
            <b><?php echo number_format((int) $st['today']); ?></b>
            <span>Today</span>
          </a>
          <a href="<?php echo htmlspecialchars($qs(['station' => $key, 'view' => 'remaining'])); ?>#names">
            <b><?php echo number_format((int) $st['remaining']); ?></b>
            <span>Remaining</span>
          </a>
          <a href="<?php echo htmlspecialchars($qs(['station' => $key, 'view' => 'done'])); ?>#names">
            <b><?php echo number_format((int) $st['done']); ?></b>
            <span>Total done</span>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card" id="names">
    <div class="card-head">
      <h3><?php echo htmlspecialchars($stationLabel); ?> — <?php echo htmlspecialchars($listTitle); ?></h3>
      <a class="btn sm" href="<?php echo htmlspecialchars($qs(['export' => 'csv'])); ?>">Download CSV</a>
    </div>
    <div class="settings-tabs" style="margin:0 0 12px;">
      <a class="<?php echo $f['view'] === 'remaining' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($qs(['view' => 'remaining'])); ?>#names">Remaining</a>
      <a class="<?php echo $f['view'] === 'today' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($qs(['view' => 'today'])); ?>#names">Today</a>
      <a class="<?php echo $f['view'] === 'done' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($qs(['view' => 'done'])); ?>#names">Total done</a>
    </div>
    <form class="filters" method="get">
      <input type="hidden" name="event" value="<?php echo htmlspecialchars($f['event']); ?>">
      <input type="hidden" name="inst" value="<?php echo htmlspecialchars($f['inst']); ?>">
      <input type="hidden" name="station" value="<?php echo htmlspecialchars($f['station']); ?>">
      <input type="hidden" name="view" value="<?php echo htmlspecialchars($f['view']); ?>">
      <input type="search" name="q" value="<?php echo htmlspecialchars($f['q']); ?>" placeholder="Search name or mobile">
      <button class="btn" type="submit">Search</button>
    </form>
    <p class="hint-block"><?php echo number_format(count($rows)); ?> names</p>
    <?php if (!$rows): ?>
      <div class="empty"><b>No names</b>Nothing in this list yet.</div>
    <?php else: ?>
    <div class="table-wrap">
      <table class="plain">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Role</th>
            <th>Organisation Name</th>
            <th>Mobile</th>
            <?php if ($f['view'] !== 'remaining'): ?><th>Time</th><?php endif; ?>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $app):
            $token = (string) ($app['receipt_token'] ?? '');
        ?>
          <tr>
            <td><?php echo htmlspecialchars(event_application_code((int) $app['id'])); ?></td>
            <td><?php echo htmlspecialchars(event_full_name($app)); ?></td>
            <td><?php echo htmlspecialchars(form_class_label((string) ($app['class'] ?? ''))); ?></td>
            <td><?php echo htmlspecialchars((string) ($app['school_name'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string) ($app['mobile'] ?? '')); ?></td>
            <?php if ($f['view'] !== 'remaining'): ?>
              <td><?php echo !empty($app['checked_in_at']) ? htmlspecialchars(date('d M, h:i A', strtotime((string) $app['checked_in_at']))) : '—'; ?></td>
            <?php endif; ?>
            <td>
              <?php if ($token !== ''): ?>
                <a class="btn sm" href="<?php echo htmlspecialchars($scanHref . '?q=' . rawurlencode($token)); ?>">Open</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="app-cards">
      <?php foreach ($rows as $app):
          $token = (string) ($app['receipt_token'] ?? '');
      ?>
        <div class="app-card">
          <b><?php echo htmlspecialchars(event_full_name($app)); ?></b>
          <div class="app-meta">
            <?php echo htmlspecialchars(event_application_code((int) $app['id'])); ?>
            · <?php echo htmlspecialchars(form_class_label((string) ($app['class'] ?? ''))); ?>
            · <?php echo htmlspecialchars((string) ($app['mobile'] ?? '')); ?><br>
            <?php echo htmlspecialchars((string) ($app['school_name'] ?? '')); ?>
            <?php if (!empty($app['checked_in_at'])): ?>
              <br><?php echo htmlspecialchars(date('d M, h:i A', strtotime((string) $app['checked_in_at']))); ?>
            <?php endif; ?>
          </div>
          <?php if ($token !== ''): ?>
            <div class="actions" style="margin-top:8px;"><a class="btn sm" href="<?php echo htmlspecialchars($scanHref . '?q=' . rawurlencode($token)); ?>">Open</a></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
