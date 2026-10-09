<?php
/**
 * Paid-registration entry ticket for SVSS Dandiya Night.
 */

function event_ticket_assets_once(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    echo <<<'CSS'
<style>
.dn-ticket{--maroon:#7a1230;--gold:#e8b923;--ink:#2a1020;max-width:720px;margin:0 auto 16px;background:#fffaf3;border-radius:18px;overflow:hidden;box-shadow:0 14px 36px rgba(122,18,48,.18);display:grid;grid-template-columns:176px 1fr;color:var(--ink);font-family:"Segoe UI",system-ui,sans-serif}
.dn-stub{background:linear-gradient(180deg,#7a1230,#4d0b1e);color:#fff8e8;padding:18px 12px;text-align:center;display:flex;flex-direction:column;justify-content:space-between;gap:12px;position:relative}
.dn-stub:after{content:"";position:absolute;top:0;right:-8px;bottom:0;width:16px;background:radial-gradient(circle at 0 12px,transparent 8px,#fffaf3 9px) 0 0/16px 24px repeat-y}
.dn-kicker{letter-spacing:.16em;font-size:10px;font-weight:800;opacity:.85}
.dn-pass{font-size:.98rem;font-weight:800;line-height:1.25;white-space:nowrap}
.dn-pass small{display:block;font-size:10px;letter-spacing:.12em;font-weight:700;opacity:.8;margin-bottom:4px}
.dn-main{padding:16px 16px 14px 22px}
.dn-top{display:flex;justify-content:space-between;gap:10px;align-items:flex-start}
.dn-top h2{margin:0;font-size:1.25rem;color:#7a1230;letter-spacing:.02em}
.dn-top p{margin:4px 0 0;font-size:12px;color:#7a4a58}
.dn-paid{background:#e8b923;color:#4d0b1e;font-size:11px;font-weight:800;letter-spacing:.08em;border-radius:999px;padding:4px 8px}
.dn-name{margin:12px 0 2px;font-size:1.35rem;font-weight:800}
.dn-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:10px}
.dn-grid div{background:#fff;border:1px solid #f0d7c4;border-radius:10px;padding:8px 10px}
.dn-grid span{display:block;font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:#8a6070;font-weight:700}
.dn-grid b{font-size:13px}
.dn-foot{display:flex;gap:12px;align-items:center;margin-top:12px}
.dn-foot img{width:108px;height:108px;border-radius:8px;background:#fff;border:1px solid #f0d7c4}
.dn-foot p{margin:0;font-size:12px;color:#6b4450;line-height:1.45}
.dn-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
.dn-actions a,.dn-actions button{background:#7a1230;color:#fff;border:0;border-radius:10px;padding:9px 12px;font-weight:800;text-decoration:none;cursor:pointer;font-family:inherit}
.dn-actions .ghost{background:#fff;color:#7a1230;border:1px solid #e4b7c4}
@media(max-width:640px){
  .dn-ticket{grid-template-columns:1fr}
  .dn-stub:after{display:none}
  .dn-stub{border-bottom:2px dashed rgba(255,248,232,.45)}
}
@media print{
  .dn-actions{display:none!important}
  .dn-ticket{box-shadow:none;border:1px solid #7a1230}
}
</style>
CSS;
}

function event_ticket_render(array $app, bool $withActions = true): void
{
    if (!function_exists('event_application_code')) {
        require_once __DIR__ . '/event_stations.php';
    }
    if (!function_exists('form_class_label')) {
        require_once __DIR__ . '/form_catalog.php';
    }
    if (!function_exists('landing_page_title')) {
        require_once __DIR__ . '/app_settings.php';
    }
    $token = (string) ($app['receipt_token'] ?? '');
    $code = event_application_code((int) ($app['id'] ?? 0));
    $name = trim(($app['first_name'] ?? '') . ' ' . ($app['middle_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
    $type = form_class_label((string) ($app['class'] ?? ''));
    $college = trim((string) ($app['school_name'] ?? ''));
    $when = function_exists('form_workshop_period_label') ? form_workshop_period_label() : '';
    $venue = function_exists('form_event_venue_label') ? form_event_venue_label() : '';
    $title = landing_page_title();
    $passUrl = $token !== '' ? event_pass_url($token) : '';
    $qr = $passUrl !== ''
        ? 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&ecc=M&margin=8&data=' . rawurlencode($passUrl)
        : '';
    event_ticket_assets_once();
    ?>
    <article class="dn-ticket">
      <div class="dn-stub">
        <div class="dn-kicker">ENTRY PASS</div>
        <div class="dn-pass"><small>PASS NO.</small><?php echo htmlspecialchars($code); ?></div>
        <div class="dn-kicker">SHOW AT GATE</div>
      </div>
      <div class="dn-main">
        <div class="dn-top">
          <div>
            <h2><?php echo htmlspecialchars($title); ?></h2>
            <p><?php echo htmlspecialchars(landing_page_subtitle()); ?></p>
          </div>
          <span class="dn-paid">PAID</span>
        </div>
        <div class="dn-name"><?php echo htmlspecialchars($name); ?></div>
        <div class="dn-grid">
          <div><span>Ticket type</span><b><?php echo htmlspecialchars($type); ?></b></div>
          <div><span>Mobile</span><b><?php echo htmlspecialchars((string) ($app['mobile'] ?? '')); ?></b></div>
          <div><span>College</span><b><?php echo htmlspecialchars($college !== '' ? $college : '—'); ?></b></div>
          <div><span>When</span><b><?php echo htmlspecialchars($when); ?></b></div>
        </div>
        <div class="dn-foot">
          <?php if ($qr !== ''): ?>
          <img src="<?php echo htmlspecialchars($qr); ?>" alt="Ticket QR">
          <?php endif; ?>
          <p>
            <strong><?php echo nl2br(htmlspecialchars($venue)); ?></strong><br>
            This ticket is valid for one entry. Staff will scan the QR at the gate.
          </p>
        </div>
        <?php if ($withActions && $token !== ''): ?>
        <div class="dn-actions">
          <button type="button" onclick="window.print()">Print ticket</button>
          <a class="ghost" href="icard.php?token=<?php echo urlencode($token); ?>">Open ticket</a>
          <a class="ghost" href="payment_success.php?token=<?php echo urlencode($token); ?>">Receipt</a>
        </div>
        <?php endif; ?>
      </div>
    </article>
    <?php
}
