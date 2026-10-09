<?php
/**
 * Shared mobile + SMS OTP login screen.
 * Expected variables: $page_title, $page_heading, $page_sub, $phone_label,
 * $note_text, $csrf, $next, $msg_info, $msg_error, $otp_active, $ctx,
 * $mobile_prefill, $cooldownRemaining, $debug_block, $otp_length, $country_code
 */
$otp_length = $otp_length ?? (defined('OTP_LENGTH') ? (int) OTP_LENGTH : 4);
$country_code = $country_code ?? (defined('SMS_COUNTRY_CODE') ? SMS_COUNTRY_CODE : '91');
$debug_block = $debug_block ?? '';
$note_text = $note_text ?? '';
$phone_label = $phone_label ?? 'Mobile number*';
$extra_links = $extra_links ?? '';
$staff_ref = $staff_ref ?? ($_SESSION['staff_ref'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if (!empty($login_showcase)): ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <?php endif; ?>
    <link rel="stylesheet" href="<?php echo htmlspecialchars((strpos((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '/staff/') !== false || strpos((string) ($_SERVER['SCRIPT_NAME'] ?? ''), '/adminpanel/') !== false) ? '../assets/css/theme.css?v=2' : 'assets/css/theme.css?v=2'); ?>">
    <style>
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body { margin: 0; font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; }
        .wrap { min-height: 100dvh; display: grid; place-items: center; padding: 16px; background: radial-gradient(1200px 500px at 50% -10%, #cfe0ff 0%, var(--bg) 55%); }
        .card { width: 100%; max-width: 460px; background: var(--card); border-radius: 18px; box-shadow: var(--shadow); padding: 24px; }
        .brand { text-align:center; margin-bottom: 12px; }
        .brand img { height: 72px; width: 72px; object-fit: contain; background:#fff; border-radius:18px; padding:8px; box-shadow:0 8px 20px rgba(0,88,240,.12); }
        h1 { margin: 0 0 8px; font-size: 22px; text-align:center; color: var(--brand); }
        .sub { text-align:center; color:var(--muted); font-size:14px; margin-bottom:8px; }
        label { display:block; margin:14px 0 8px; font-weight:600; font-size:14px; }
        .input { width:100%; padding:14px; border:1px solid var(--line); border-radius:14px; font-size:16px; background:#fff; transition:border-color .15s, box-shadow .15s; }
        .input:focus { outline:none; border-color:var(--brand-mid); box-shadow:0 0 0 4px rgba(0,88,240,.15); }
        .phone-group { position:relative; }
        .phone-prefix { position:absolute; left:10px; top:50%; transform:translateY(-50%); background:var(--brand-soft); border:1px solid var(--line); color:var(--brand-dark); padding:8px 10px; border-radius:10px; font-weight:600; font-size:14px; user-select:none; }
        .phone-input { padding-left:88px; }
        .btn { display:block; width:100%; padding:14px 16px; border:0; border-radius:14px; background:var(--brand); color:#fff; font-size:16px; font-weight:600; cursor:pointer; box-shadow:0 8px 18px rgba(0,88,240,.28); }
        .btn.secondary { background:var(--brand-mid); }
        .btn[disabled] { opacity:.6; cursor:not-allowed; box-shadow:none; }
        .btn.loading { position:relative; color:transparent; }
        .btn.loading::after { content:""; position:absolute; inset:0; margin:auto; width:18px; height:18px; border:2px solid #fff; border-top-color:transparent; border-radius:50%; animation:spin .8s linear infinite; }
        @keyframes spin { to { transform:rotate(360deg); } }
        .top-gap { margin-top:14px; }
        .msg { margin:10px 0 0; padding:12px; border-radius:12px; font-size:14px; }
        .msg.info { background:var(--brand-soft); color:var(--brand-dark); }
        .msg.error { background:#fee2e2; color:#7f1d1d; }
        .note { font-size:12px; color:var(--muted); margin-top:12px; text-align:center; line-height:1.4; }
        .phone-display { display:flex; align-items:center; justify-content:space-between; background:#f8fafc; border:1px dashed var(--line); border-radius:12px; padding:10px 12px; margin-top:4px; }
        .assist { font-size:12px; color:var(--muted); margin-top:8px; text-align:center; line-height:1.5; }
        .link { background:none; border:0; color:var(--brand); font-weight:700; cursor:pointer; padding:0; font-size:12px; }
        .extra { margin-top:16px; text-align:center; font-size:14px; }
        .extra a { display:inline-block; margin:4px 4px 0; background:var(--brand); color:#fff; font-weight:700; text-decoration:none; padding:10px 14px; border-radius:12px; }
        .program { text-align:center; background:var(--brand-soft); border-radius:14px; padding:10px 12px; margin:0 0 12px; font-size:13px; line-height:1.45; color:var(--brand-dark); }
        .program a { color:var(--brand); font-weight:700; }
        .login-tabs { display:flex; gap:8px; margin:16px 0 4px; }
        .login-tabs a { flex:1; text-align:center; text-decoration:none; font-weight:700; font-size:13px; padding:10px 8px; border-radius:12px; background:#f1f5f9; color:#475569; }
        .login-tabs a.active { background:var(--brand); color:#fff; }
        .pw-wrap { position:relative; }
        .pw-wrap .input { padding-right:84px; }
        .pw-wrap .link { position:absolute; right:12px; top:50%; transform:translateY(-50%); font-size:13px; }
        .toast{position:fixed;left:50%;bottom:16px;transform:translateX(-50%);background:#0f172a;color:#fff;padding:10px 12px;border-radius:10px;opacity:0;transition:opacity .2s;z-index:9999;}
        .toast.show{opacity:1;}
        body.is-showcase { background:#fff; color:#172033; font-family:"Plus Jakarta Sans", system-ui, sans-serif; }
        body.is-showcase .help-link { position:absolute; top:22px; right:28px; z-index:3; color:#24356b; font-weight:700; text-decoration:none; font-size:15px; }
        body.is-showcase .stage { min-height:100dvh; display:grid; grid-template-columns:minmax(0,1.15fr) minmax(280px,.85fr); align-items:center; gap:32px; padding:72px 7vw 96px; position:relative; z-index:1; }
        body.is-showcase .hero-side { max-width:520px; }
        body.is-showcase .form-side { min-width:0; }
        body.is-showcase .crest { width:168px; height:168px; border-radius:50%; border:3px solid #c6a15b; background:#fff; display:grid; place-items:center; overflow:hidden; margin-bottom:22px; }
        body.is-showcase .crest img { width:78%; height:78%; object-fit:contain; }
        body.is-showcase .wordmark { margin:0; font-size:2.7rem; line-height:1.05; letter-spacing:-.03em; font-weight:800; color:#1d2a6b; }
        body.is-showcase .wordmark span { color:#c23b55; }
        body.is-showcase .goldbar { width:74px; height:4px; border-radius:99px; background:#d4a24a; margin:14px 0 0; }
        body.is-showcase .poster-link { display:block; margin:0; padding-top:16px; max-width:420px; }
        body.is-showcase .event-poster { display:block; width:100%; height:auto; max-height:min(40vh, 380px); object-fit:contain; border-radius:16px; background:#f8fafc; box-shadow:0 14px 36px rgba(15,23,42,.12); }
        body.is-showcase .facts { margin-top:18px; color:#3d4a60; font-size:.95rem; line-height:1.6; font-weight:600; }
        body.is-showcase .facts a { display:inline-block; margin-top:8px; color:#1d4ed8; font-weight:800; }
        body.is-showcase .wrap { min-height:0; display:block; padding:0; background:none; }
        body.is-showcase .card { width:100%; max-width:430px; margin-left:auto; border-radius:22px; padding:28px 26px 22px; border:1px solid #eef1f6; box-shadow:0 18px 50px rgba(15,23,42,.08); }
        body.is-showcase .card h1 { text-align:left; font-size:1.7rem; color:#1d3fbf; margin-bottom:6px; }
        body.is-showcase .sub { text-align:left; margin-bottom:4px; }
        body.is-showcase .brand { display:none; }
        body.is-showcase .program { display:none; }
        body.is-showcase .phone-prefix { background:#f3f5f8; color:#334155; border-color:#e6eaf0; }
        body.is-showcase .input { border-radius:12px; border-color:#e4e8ef; }
        body.is-showcase .btn { border-radius:999px; background:#6d78e6; box-shadow:none; font-weight:700; }
        body.is-showcase .btn.secondary { background:#5b66d6; }
        body.is-showcase .extra { margin-top:14px; }
        body.is-showcase .extra a { background:transparent; color:#5b6b86; padding:6px 0; box-shadow:none; font-size:13px; }
        body.is-showcase .blob { position:fixed; left:-6vw; bottom:-30vh; width:42vw; height:36vh; background:#1d4ed8; border-radius:50%; z-index:0; pointer-events:none; }
        @media (max-width:1024px) {
            body.is-showcase .stage { grid-template-columns:1fr 1fr; padding:64px 4vw 80px; gap:20px; }
            body.is-showcase .wordmark { font-size:2.1rem; }
            body.is-showcase .crest { width:132px; height:132px; }
        }
        @media (max-width:760px) {
            body.is-showcase .help-link { top:14px; right:16px; font-size:14px; }
            body.is-showcase .stage { grid-template-columns:1fr; padding:58px 16px 36px; }
            body.is-showcase .hero-side { max-width:none; text-align:center; }
            body.is-showcase .crest { margin:0 auto 16px; width:112px; height:112px; }
            body.is-showcase .goldbar { margin-left:auto; margin-right:auto; }
            body.is-showcase .poster-link, body.is-showcase .facts { margin-left:auto; margin-right:auto; }
            body.is-showcase .event-poster { max-height:168px; }
            body.is-showcase .wordmark { font-size:1.85rem; }
            body.is-showcase .card { margin:8px auto 0; }
            body.is-showcase .blob { width:78vw; height:22vh; left:-24vw; bottom:-12vh; }
        }
        @media (max-width:760px) and (max-height:740px) {
            body.is-showcase .stage { padding:46px 16px 20px; }
            body.is-showcase .crest { width:84px; height:84px; margin-bottom:10px; }
            body.is-showcase .wordmark { font-size:1.6rem; }
            body.is-showcase .goldbar { margin:8px auto 8px; }
            body.is-showcase .event-poster { max-height:100px; }
            body.is-showcase .facts { margin-top:8px; font-size:.86rem; line-height:1.4; }
            body.is-showcase .facts a { margin-top:4px; }
            body.is-showcase .card { padding:18px 16px 12px; }
        }
    </style>
</head>
<body<?php echo !empty($login_showcase) ? ' class="is-showcase"' : ''; ?>>
<?php if (!empty($login_showcase)):
    $helpUrl = function_exists('help_whatsapp_url') ? help_whatsapp_url() : '';
?>
<?php if ($helpUrl !== ''): ?><a class="help-link" href="<?php echo htmlspecialchars($helpUrl); ?>" target="_blank" rel="noopener">Help / Support</a><?php endif; ?>
<div class="stage">
    <section class="hero-side">
        <?php $brandLogo = function_exists('panel_logo_src') ? panel_logo_src() : ''; ?>
        <?php if ($brandLogo !== ''): ?><div class="crest"><img src="<?php echo htmlspecialchars($brandLogo); ?>" alt=""></div><?php endif; ?>
        <p class="wordmark"><?php if (!empty($showcase_kicker)): ?><?php echo htmlspecialchars($showcase_kicker); ?> <?php endif; ?><span><?php echo htmlspecialchars($showcase_accent ?? $page_title); ?></span></p>
        <div class="goldbar"></div>
        <?php
        $eventPoster = function_exists('landing_poster_src') ? landing_poster_src() : '';
        $posterHref = 'welcome.php' . ($staff_ref ? ('?ref=' . urlencode((string) $staff_ref)) : '');
        ?>
        <?php if ($eventPoster !== ''): ?>
        <a class="poster-link" href="<?php echo htmlspecialchars($posterHref); ?>" aria-label="<?php echo htmlspecialchars($page_title); ?> poster">
            <img class="event-poster" src="<?php echo htmlspecialchars($eventPoster); ?>" alt="<?php echo htmlspecialchars($page_title); ?>">
        </a>
        <?php endif; ?>
        <?php if (!empty($show_program_info) && !empty($program_facts)): ?>
        <div class="facts">
            <?php foreach ($program_facts as $fact): ?>
                <div><?php echo htmlspecialchars((string) $fact[0]); ?>: <?php echo htmlspecialchars((string) $fact[1]); ?></div>
            <?php endforeach; ?>
            <a href="welcome.php<?php echo $staff_ref ? ('?ref=' . urlencode((string) $staff_ref)) : ''; ?>">Event details</a>
        </div>
        <?php endif; ?>
    </section>
    <section class="form-side">
<?php endif; ?>
<div class="wrap">
    <div class="card" role="region" aria-label="<?php echo htmlspecialchars($page_heading); ?>">
        <?php
        $brandName = function_exists('panel_brand_name') ? panel_brand_name() : 'Registration';
        $brandLogo = function_exists('panel_logo_src') ? panel_logo_src() : '';
        $allow_password_login = !empty($allow_password_login);
        $login_method = $login_method ?? 'otp';
        if ($allow_password_login && !empty($otp_active)) {
            $login_method = 'otp';
        }
        if (!$allow_password_login) {
            $login_method = 'otp';
        }
        ?>
        <div class="brand">
            <?php if ($brandLogo !== ''): ?>
            <img src="<?php echo htmlspecialchars($brandLogo); ?>" alt="<?php echo htmlspecialchars($brandName); ?>">
            <?php endif; ?>
        </div>
        <h1><?php echo htmlspecialchars($page_heading); ?></h1>
        <div class="sub"><?php echo htmlspecialchars($page_sub); ?></div>
        <?php if (!empty($show_program_info)): ?>
            <div class="program">
                <?php foreach (($program_facts ?? []) as $fact): ?>
                    <div><b><?php echo htmlspecialchars((string) $fact[0]); ?>:</b> <?php echo htmlspecialchars((string) $fact[1]); ?></div>
                <?php endforeach; ?>
                <a href="welcome.php<?php echo $staff_ref ? ('?ref=' . urlencode((string) $staff_ref)) : ''; ?>">Event details</a>
            </div>
        <?php endif; ?>
        <?php if ($allow_password_login): ?>
        <div class="login-tabs">
            <a class="<?php echo $login_method === 'password' ? 'active' : ''; ?>" href="?mode=password&amp;next=<?php echo urlencode((string) $next); ?>">Mobile + password</a>
            <a class="<?php echo $login_method === 'otp' ? 'active' : ''; ?>" href="?mode=otp&amp;next=<?php echo urlencode((string) $next); ?>">SMS OTP</a>
        </div>
        <?php endif; ?>
        <div aria-live="polite" aria-atomic="true">
            <?php if (!empty($msg_info)): ?><div class="msg info"><?php echo htmlspecialchars($msg_info); ?></div><?php endif; ?>
            <?php if (!empty($msg_error)): ?><div class="msg error"><?php echo htmlspecialchars($msg_error); ?></div><?php endif; ?>
        </div>

        <?php if ($allow_password_login && $login_method === 'password'): ?>
            <form method="post" id="passwordLoginForm" autocomplete="on" novalidate class="top-gap">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="action" value="password_login">
                <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
                <label for="phone"><?php echo htmlspecialchars($phone_label); ?></label>
                <div class="phone-group">
                    <span class="phone-prefix">+<?php echo htmlspecialchars($country_code); ?></span>
                    <input id="phone" class="input phone-input" type="tel" name="mobile" maxlength="10" inputmode="numeric" pattern="\d{10}" placeholder="10-digit mobile number" value="<?php echo htmlspecialchars($mobile_prefill); ?>" required autofocus>
                </div>
                <label for="password">Password</label>
                <div class="pw-wrap">
                    <input id="password" class="input" type="password" name="password" autocomplete="current-password" placeholder="Enter password" required>
                    <button class="link" type="button" id="togglePw">Show</button>
                </div>
                <button class="btn top-gap" type="submit">Login</button>
                <?php if ($note_text): ?><div class="note"><?php echo htmlspecialchars(strip_tags($note_text)); ?></div><?php endif; ?>
            </form>
        <?php elseif (!$otp_active): ?>
            <form method="post" id="phoneForm" autocomplete="off" novalidate class="top-gap">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                <input type="hidden" name="action" value="send_otp">
                <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
                <?php if ($staff_ref): ?><input type="hidden" name="ref" value="<?php echo htmlspecialchars($staff_ref); ?>"><?php endif; ?>
                <label for="phone"><?php echo htmlspecialchars($phone_label); ?></label>
                <div class="phone-group">
                    <span class="phone-prefix">+<?php echo htmlspecialchars($country_code); ?></span>
                    <input id="phone" class="input phone-input" type="tel" name="mobile" maxlength="10" inputmode="numeric" pattern="\d{10}" placeholder="10-digit mobile number" value="<?php echo htmlspecialchars($mobile_prefill); ?>" required autofocus>
                </div>
                <button class="btn top-gap" id="sendOtpBtn" type="submit" disabled><?php echo htmlspecialchars($send_otp_label ?? 'Send SMS OTP'); ?></button>
                <?php if ($note_text): ?><div class="note"><?php echo htmlspecialchars(strip_tags($note_text)); ?></div><?php endif; ?>
            </form>
        <?php else: ?>
            <div class="top-gap">
                <label><?php echo htmlspecialchars($phone_label); ?></label>
                <div class="phone-display">
                    <small>+<?php echo htmlspecialchars($country_code . ' ' . local_10_digit($ctx['mobile_e164'] ?? '')); ?></small>
                    <form method="post" style="margin:0;">
                        <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                        <input type="hidden" name="action" value="change_phone">
                        <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
                        <?php if ($staff_ref): ?><input type="hidden" name="ref" value="<?php echo htmlspecialchars($staff_ref); ?>"><?php endif; ?>
                        <button class="link" type="submit">Change</button>
                    </form>
                </div>
                <form method="post" id="otpLoginForm" autocomplete="one-time-code" novalidate class="top-gap">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
                    <?php if ($staff_ref): ?><input type="hidden" name="ref" value="<?php echo htmlspecialchars($staff_ref); ?>"><?php endif; ?>
                    <label for="otp">Enter SMS OTP</label>
                    <input id="otp" name="otp" class="input" inputmode="numeric" pattern="\d{<?php echo (int) $otp_length; ?>}" maxlength="<?php echo (int) $otp_length; ?>" autocomplete="one-time-code" placeholder="<?php echo $otp_length; ?>-digit OTP" required>
                    <div class="assist">
                        OTP SMS sent to your mobile.<br>
                        Didn't receive it?
                        <button type="button" id="resendBtn" class="link" <?php echo $cooldownRemaining > 0 ? 'disabled aria-disabled="true"' : ''; ?> aria-live="polite">
                            <?php echo $cooldownRemaining > 0 ? 'Resend in ' . sprintf('00:%02d', $cooldownRemaining) : 'Resend OTP'; ?>
                        </button>
                    </div>
                    <button class="btn secondary top-gap" type="submit" id="loginBtn">Login</button>
                </form>
                <form method="post" id="resendFormHidden" style="display:none;">
                    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="action" value="resend_otp">
                    <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
                    <?php if ($staff_ref): ?><input type="hidden" name="ref" value="<?php echo htmlspecialchars($staff_ref); ?>"><?php endif; ?>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($debug_block && defined('APP_DEBUG') && APP_DEBUG): ?>
            <div style="margin-top:12px;"><?php echo $debug_block; ?></div>
        <?php endif; ?>
        <?php if ($extra_links): ?>
            <div class="extra"><?php echo $extra_links; ?></div>
        <?php endif; ?>
    </div>
</div>
<?php if (!empty($login_showcase)): ?>
    </section>
</div>
<div class="blob" aria-hidden="true"></div>
<?php endif; ?>
<div id="toast" class="toast" role="status" aria-live="polite"></div>
<script>
    const toastEl = document.getElementById('toast');
    function toast(msg){ if(!toastEl) return; toastEl.textContent=msg; toastEl.classList.add('show'); setTimeout(()=>toastEl.classList.remove('show'), 1800); }
    <?php if (!empty($msg_info)): ?>toast(<?php echo json_encode($msg_info); ?>);<?php endif; ?>

    const phoneInput = document.getElementById('phone');
    const sendBtn = document.getElementById('sendOtpBtn');
    if (phoneInput) {
        const toggle = () => { if (sendBtn) sendBtn.disabled = phoneInput.value.replace(/\D+/g,'').length !== 10; };
        phoneInput.addEventListener('input', (e) => { e.target.value = e.target.value.replace(/\D+/g,'').slice(0,10); toggle(); });
        toggle();
        if (sendBtn) phoneInput.form?.addEventListener('submit', () => sendBtn.classList.add('loading'));
    }
    document.getElementById('togglePw')?.addEventListener('click', (e) => {
        const input = document.getElementById('password');
        if (!input) return;
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        e.currentTarget.textContent = show ? 'Hide' : 'Show';
    });

    const otp = document.getElementById('otp');
    const verifyBtn = document.getElementById('loginBtn');
    if (otp && verifyBtn) {
        otp.addEventListener('input', () => {
            const len = <?php echo (int) $otp_length; ?>;
            otp.value = otp.value.replace(/\D/g,'').slice(0,len);
            if (otp.value.length === len) {
                verifyBtn.disabled = true;
                otp.form.submit();
            }
        });
        setTimeout(()=>{ try{ otp.focus(); }catch(_){} }, 100);
    }

    const resendBtn = document.getElementById('resendBtn');
    if (resendBtn) {
        let remaining = <?php echo (int) $cooldownRemaining; ?>;
        const tick = () => {
            if (remaining > 0) {
                remaining--;
                resendBtn.textContent = 'Resend in ' + ('00:' + String(remaining).padStart(2,'0'));
                resendBtn.setAttribute('disabled','true');
                resendBtn.setAttribute('aria-disabled','true');
            } else {
                resendBtn.textContent = 'Resend OTP';
                resendBtn.removeAttribute('disabled');
                resendBtn.removeAttribute('aria-disabled');
                clearInterval(timer);
            }
        };
        if (remaining > 0) { var timer = setInterval(tick, 1000); }
        resendBtn.addEventListener('click', () => {
            if (resendBtn.hasAttribute('disabled')) return;
            document.getElementById('resendFormHidden').submit();
        });
    }
</script>
<?php if (empty($login_showcase) && function_exists('help_whatsapp_button')) { echo help_whatsapp_button(true); } ?>
</body>
</html>
