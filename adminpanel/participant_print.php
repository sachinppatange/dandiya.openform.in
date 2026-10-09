<?php
session_start();
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../includes/participant_cards.php';
require_once __DIR__ . '/../includes/form_catalog.php';

if (empty($_SESSION['admin_auth_user'])) {
    header('Location: admin_login.php?next=participant_cards.php');
    exit;
}

$from = max(0, (int) ($_GET['from'] ?? 0));
$to = max(0, (int) ($_GET['to'] ?? 0));
$showName = (string) ($_GET['name'] ?? '1') !== '0';
$rows = participant_paid_rows($from, $to);
$title = participant_event_title();
$perSheet = participant_sheet_size();
$sheets = $rows === [] ? [] : array_chunk($rows, $perSheet);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Participant cards · <?php echo htmlspecialchars($title); ?></title>
<style>
  @page { size: 12in 18in; margin: 0; }
  * { box-sizing: border-box; }
  html, body { margin: 0; padding: 0; }
  body { background: #d9d9d9; font-family: Arial, Helvetica, sans-serif; color: #111; }
  .toolbar { position: sticky; top: 0; z-index: 2; background: #1c2430; color: #fff; padding: 12px 16px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
  .toolbar button, .toolbar a { background: #fff; color: #1c2430; border: 0; border-radius: 8px; padding: 8px 12px; font-weight: 800; text-decoration: none; cursor: pointer; font-family: inherit; }
  .toolbar span { font-size: 13px; opacity: .9; }
  .stage { padding: 18px 0 32px; }
  .sheet {
    width: 12in;
    height: 18in;
    margin: 0 auto 18px;
    background: #fff;
    display: grid;
    grid-template-columns: repeat(4, 3in);
    grid-template-rows: repeat(6, 3in);
    outline: 0.6pt dashed #b5b5b5;
    box-shadow: 0 8px 24px rgba(0,0,0,.18);
  }
  .pcard {
    width: 3in;
    height: 3in;
    padding: 0.14in 0.12in 0.1in;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    overflow: hidden;
    border-right: 0.6pt dashed #b5b5b5;
    border-bottom: 0.6pt dashed #b5b5b5;
  }
  .pcard-event { font-size: 11pt; font-weight: 800; letter-spacing: .04em; line-height: 1.15; max-width: 2.7in; }
  .pcard-no { font-size: 78pt; font-weight: 800; line-height: .86; letter-spacing: .02em; margin: 0.04in 0 0.02in; }
  .pcard-label { font-size: 9pt; font-weight: 800; letter-spacing: .14em; }
  .pcard-use { font-size: 8pt; font-weight: 700; letter-spacing: .04em; margin-top: 0.04in; }
  .pcard-name { font-size: 10pt; font-weight: 700; margin-top: 0.06in; max-width: 2.7in; line-height: 1.15; }
  @media print {
    body { background: #fff; }
    .toolbar { display: none !important; }
    .stage { padding: 0; }
    .sheet { margin: 0; box-shadow: none; page-break-after: always; }
    .sheet:last-child { page-break-after: auto; }
  }
</style>
</head>
<body>
<div class="toolbar">
  <button type="button" onclick="window.print()">Print 12 × 18</button>
  <a href="participant_cards.php">Back</a>
  <span><?php echo count($rows); ?> card<?php echo count($rows) === 1 ? '' : 's'; ?> · <?php echo count($sheets); ?> sheet<?php echo count($sheets) === 1 ? '' : 's'; ?> · 3 × 3 in · margins none · scale 100%</span>
</div>
<div class="stage">
<?php if ($sheets === []): ?>
  <p style="text-align:center;font-weight:700;">No paid registrations in this range.</p>
<?php endif; ?>
<?php foreach ($sheets as $sheet): ?>
  <section class="sheet">
    <?php foreach ($sheet as $app): ?>
    <article class="pcard">
      <div class="pcard-event"><?php echo htmlspecialchars($title); ?></div>
      <div class="pcard-no"><?php echo htmlspecialchars(participant_number_label($app['participant_no'] ?? 0)); ?></div>
      <div class="pcard-label">PARTICIPANT NUMBER</div>
      <div class="pcard-use">Judging &amp; Identification</div>
      <?php if ($showName): $name = participant_guest_name($app); ?>
      <div class="pcard-name"><?php echo htmlspecialchars($name); ?></div>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
    <?php for ($i = count($sheet); $i < $perSheet; $i++): ?>
    <article class="pcard"></article>
    <?php endfor; ?>
  </section>
<?php endforeach; ?>
</div>
</body>
</html>
