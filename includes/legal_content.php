<?php
/**
 * Participant declaration + terms: stored in app_settings, shown on form, review, receipt, welcome.
 */

function legal_default_checkbox_text(): string
{
    return 'I have read the workshop details, declaration and rules. I confirm that the information is true, and I voluntarily accept these terms.';
}

function legal_default_declaration_html(): string
{
    return <<<'HTML'
<h4 id="declaration">Participant Declaration</h4>
<p>I register as a participant in the Academia &amp; Industry Workshop on Advanced HPLC Method Development &amp; Validation, organised by Latur College of Pharmacy, Hasegaon, through this official website.</p>
<p>I understand that the programme includes theory and practical laboratory sessions from 28 September to 3 October 2026, and that a seat is confirmed only after successful payment of the published fee.</p>
<p>I will follow laboratory safety instructions and the organiser’s rules. I confirm that the information in this form is true. I accept that the organiser may change schedule or venue when reasonably necessary and will communicate through this website, registered mobile, or email.</p>
<p>By selecting the acceptance checkbox and submitting this form, I give my informed consent and treat this electronic acceptance as my formal declaration.</p>
HTML;
}

function legal_default_terms_html(): string
{
    return <<<'HTML'
<h4 id="terms">Rules — Terms and Conditions</h4>
<p>These terms apply to participants in the HPLC workshop organised by Latur College of Pharmacy, Hasegaon (“the Organiser”).</p>
<ol>
    <li><b>Voluntary participation.</b> Registration is voluntary. You confirm that you are authorised to submit this application.</li>
    <li><b>Accuracy.</b> Provide complete and correct information. The Organiser may cancel registration or a certificate if details are false or misleading.</li>
    <li><b>Fees.</b> The published registration fee and any gateway fee shown on this website apply at the time of payment. A seat is confirmed only after successful payment.</li>
    <li><b>Payment.</b> Pay only through the official Razorpay facility on this website. Keep your receipt. The Organiser is not responsible for payments to unofficial accounts or links.</li>
    <li><b>Fee inclusions.</b> The fee includes instruction, course materials, certificate, case studies, and lunch as published on this website. Travel and stay are the participant’s responsibility unless stated otherwise.</li>
    <li><b>Venue and schedule.</b> Sessions run at Latur College of Pharmacy, Hasegaon, from 28 September to 3 October 2026, unless officially changed. The Organiser may change venue or timings when reasonably necessary.</li>
    <li><b>Laboratory conduct.</b> Follow trainer and staff instructions, safety rules, and dress requirements for practical sessions. Misconduct may lead to removal without refund.</li>
    <li><b>Communication.</b> Keep your mobile and email correct and check official messages. Missing a message is not ordinarily a reason for a special session or refund.</li>
    <li><b>Postponement.</b> The Organiser may postpone, relocate, or cancel due to circumstances beyond reasonable control. Any change will be communicated through this website, SMS, WhatsApp, or email.</li>
    <li><b>Refunds.</b> Fees are handled according to the Organiser’s published refund policy. No refund is ordinarily payable for absence, false information, or rule violation.</li>
    <li><b>Certificates.</b> Certificates are issued for eligible participants who complete the workshop as announced by the Organiser.</li>
    <li><b>Governing law.</b> These terms are governed by the laws of India. Disputes are subject to courts of competent jurisdiction in Maharashtra.</li>
    <li><b>Electronic acceptance.</b> Selecting the checkbox and submitting the form is your valid consent and declaration.</li>
</ol>
HTML;
}

function legal_sanitize_html(string $html): string
{
    $html = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $html) ?? $html;
    $html = preg_replace('#<style\b[^>]*>.*?</style>#is', '', $html) ?? $html;
    $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? $html;
    $html = strip_tags($html, '<p><br><h3><h4><h5><ol><ul><li><b><strong><em><i><a><div><span>');
    $html = preg_replace_callback('/href\s*=\s*([\'"])(.*?)\1/i', static function (array $m): string {
        $href = trim($m[2]);
        if ($href === '' || preg_match('#^\s*javascript:#i', $href)) {
            return 'href="#"';
        }
        if (!preg_match('#^(https?:)?//|^/|^#|^mailto:#i', $href)) {
            return 'href="#"';
        }
        return $m[0];
    }, $html) ?? $html;
    return substr($html, 0, 80000);
}

function legal_checkbox_text(): string
{
    $t = trim((string) get_app_setting('legal_checkbox_text', ''));
    return $t !== '' ? $t : legal_default_checkbox_text();
}

function legal_declaration_html(): string
{
    $html = trim((string) get_app_setting('legal_declaration_html', ''));
    if ($html === '') {
        $html = legal_default_declaration_html();
    }
    return legal_sanitize_html($html);
}

function legal_terms_html(): string
{
    $html = trim((string) get_app_setting('legal_terms_html', ''));
    if ($html === '') {
        $html = legal_default_terms_html();
    }
    return legal_sanitize_html($html);
}

function legal_declaration_url(): string
{
    $url = trim((string) get_app_setting('legal_declaration_url', ''));
    return $url !== '' ? $url : 'legal.php#declaration';
}

function legal_rules_url(): string
{
    $url = trim((string) get_app_setting('legal_rules_url', ''));
    return $url !== '' ? $url : 'legal.php#terms';
}

function legal_documents_html(): string
{
    return '<div class="legal-tiny">' . legal_declaration_html() . legal_terms_html() . '</div>';
}

function public_whatsapp_10(): string
{
    $d = preg_replace('/\D+/', '', (string) get_app_setting('landing_whatsapp', ''));
    return strlen($d) >= 10 ? substr($d, -10) : '';
}

function public_contact_lines(): array
{
    $lines = function_exists('landing_lines') ? landing_lines('landing_helpline') : [];
    $out = [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '' || public_text_has_legacy_agnipankh($line)) {
            continue;
        }
        $out[] = $line;
    }
    $wa = public_whatsapp_10();
    if ($wa !== '' && $out === []) {
        $out[] = 'WhatsApp: +91 ' . $wa;
    }
    return $out;
}

function public_contact_oneline(): string
{
    return implode(' | ', public_contact_lines());
}

function public_text_has_legacy_agnipankh(string $text): bool
{
    return (bool) preg_match('/agnipankh|9112-9113-88|91129\s*11388|9112911388|admin@agnipankh/i', $text);
}

function public_receipt_prefix(): string
{
    $brand = function_exists('panel_brand_name') ? panel_brand_name() : 'REG';
    $slug = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $brand));
    if ($slug === '' || strcasecmp($slug, 'AGNIPANKH') === 0) {
        $slug = 'REG';
    }
    return substr($slug, 0, 12);
}

function scrub_legacy_agnipankh_settings(PDO $pdo): void
{
    $stmt = $pdo->query('SELECT setting_key, setting_value FROM app_settings');
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];
    if (!is_array($rows)) {
        return;
    }
    $up = $pdo->prepare('UPDATE app_settings SET setting_value = ? WHERE setting_key = ?');
    $legalKeys = ['legal_checkbox_text', 'legal_declaration_html', 'legal_terms_html'];
    $defaults = [
        'legal_checkbox_text' => legal_default_checkbox_text(),
        'legal_declaration_html' => legal_default_declaration_html(),
        'legal_terms_html' => legal_default_terms_html(),
    ];
    foreach ($legalKeys as $key) {
        $cur = (string) ($rows[$key] ?? '');
        if ($cur !== '' && public_text_has_legacy_agnipankh($cur)) {
            $up->execute([$defaults[$key], $key]);
        }
    }
    foreach (['legal_declaration_url', 'legal_rules_url', 'brand_logo_url'] as $key) {
        $cur = (string) ($rows[$key] ?? '');
        if ($cur !== '' && stripos($cur, 'agnipankh') !== false) {
            $up->execute(['', $key]);
        }
    }
    $wa = preg_replace('/\D+/', '', (string) ($rows['landing_whatsapp'] ?? ''));
    if ($wa === '9112911388') {
        $up->execute(['', 'landing_whatsapp']);
    }
    $brand = trim((string) ($rows['brand_name'] ?? ''));
    if (strcasecmp($brand, 'AGNIPANKH') === 0) {
        $up->execute(['Registration', 'brand_name']);
    }
    foreach (['landing_helpline', 'landing_dates', 'landing_about', 'landing_subtitle', 'landing_need', 'landing_how', 'landing_who', 'landing_title'] as $key) {
        $cur = (string) ($rows[$key] ?? '');
        if ($cur === '' || !public_text_has_legacy_agnipankh($cur)) {
            continue;
        }
        $clean = preg_replace('/^.*(?:agnipankh|9112-9113-88|91129\s*11388|9112911388|admin@agnipankh).*$/im', '', $cur) ?? $cur;
        $clean = trim(preg_replace("/\n{3,}/", "\n\n", $clean) ?? $clean);
        $up->execute([$clean, $key]);
    }
    $reply = strtolower(trim((string) ($rows['zepto_reply_to_email'] ?? '')));
    if ($reply === 'admin@agnipankh.in') {
        $up->execute(['', 'zepto_reply_to_email']);
    }
}
