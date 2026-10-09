<div class="card">
  <div class="card-head"><h3>Scan registration QR</h3></div>
  <p class="hint-block">Point the camera at the ticket QR, or type the pass number / mobile. The paid ticket opens below so you can mark entry and wristband.</p>
  <?php if ($scanMsg): ?><div class="msg info"><?php echo htmlspecialchars($scanMsg); ?></div><?php endif; ?>
  <?php if ($scanError): ?><div class="msg error"><?php echo htmlspecialchars($scanError); ?></div><?php endif; ?>
  <form method="get" class="filters" style="margin-bottom:14px;">
    <input type="search" name="q" value="<?php echo htmlspecialchars($scanQ); ?>" placeholder="Scan result, AGS00012, or mobile" autofocus>
    <button class="btn" type="submit">Show registration</button>
    <?php if ($scanApp): ?>
      <a class="btn gray" href="<?php echo htmlspecialchars($scanBackHref); ?>">Scan another</a>
    <?php endif; ?>
  </form>
  <?php if (!$scanApp): ?>
  <div id="reader" style="max-width:420px;margin:0 auto;"></div>
  <?php endif; ?>
</div>

<?php if ($scanApp): ?>
<div class="card scan-result">
  <div class="icard-banner">
    <div>
      <small>Scanned registration</small>
      <h2><?php echo htmlspecialchars($scanName); ?></h2>
      <p><?php echo htmlspecialchars($scanCode); ?>
        · <span class="badge <?php echo htmlspecialchars($scanStatus); ?>"><?php echo htmlspecialchars($scanStatus !== '' ? $scanStatus : 'unknown'); ?></span>
      </p>
    </div>
    <div class="scan-result-actions">
      <a class="btn sm gold" href="event_desk.php">Today’s counts</a>
      <?php if ($scanToken !== '' && $scanStatus === 'paid'): ?>
        <a class="btn sm" href="../payment_success.php?token=<?php echo urlencode($scanToken); ?>" target="_blank">Receipt</a>
        <a class="btn sm gray" href="../pass.php?t=<?php echo urlencode($scanToken); ?>" target="_blank">Digital I-Card</a>
      <?php endif; ?>
    </div>
  </div>
  <?php foreach ($scanProfile as $group => $rows): ?>
    <h4 class="scan-group"><?php echo htmlspecialchars($group); ?></h4>
    <dl class="scan-dl">
      <?php foreach ($rows as $label => $value): ?>
        <div>
          <dt><?php echo htmlspecialchars($label); ?></dt>
          <dd><?php echo htmlspecialchars((string) $value); ?></dd>
        </div>
      <?php endforeach; ?>
    </dl>
  <?php endforeach; ?>
</div>

<div class="card">
  <div class="card-head"><h3>Event stations</h3></div>
  <?php if ($scanStatus !== 'paid'): ?>
    <p class="hint-block">Stations can be marked after the registration is paid.</p>
  <?php endif; ?>
  <div class="stations-grid">
    <?php foreach ($scanStations as $key => $st):
        $done = isset($scanCheckins[$key]);
        $when = $done ? date('d M Y, h:i A', strtotime((string) $scanCheckins[$key]['checked_in_at'])) : '';
        $who = $done ? (string) ($scanCheckins[$key]['checked_in_by'] ?? '') : '';
    ?>
      <div class="st <?php echo $done ? 'done' : ''; ?>">
        <div>
          <b><?php echo htmlspecialchars($st['label']); ?></b>
          <small><?php echo $done ? htmlspecialchars('Done · ' . $when . ($who !== '' ? ' · ' . $who : '')) : htmlspecialchars($st['hint']); ?></small>
        </div>
        <span class="badge"><?php echo $done ? 'DONE' : 'PENDING'; ?></span>
        <?php if ($operator && $scanStatus === 'paid'): ?>
        <form method="post">
          <input type="hidden" name="q" value="<?php echo htmlspecialchars($scanToken !== '' ? $scanToken : (string) $scanApp['id']); ?>">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($scanToken); ?>">
          <input type="hidden" name="app_id" value="<?php echo (int) $scanApp['id']; ?>">
          <input type="hidden" name="station" value="<?php echo htmlspecialchars($key); ?>">
          <?php if ($done): ?>
            <input type="hidden" name="action" value="undo">
            <button class="btn sm gray" type="submit">Undo</button>
          <?php else: ?>
            <button class="btn sm" type="submit">Mark</button>
          <?php endif; ?>
        </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
