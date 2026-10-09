<?php
session_start();
require_once 'db.php';
if (!function_exists('form_class_label')) {
    require_once __DIR__ . '/includes/form_catalog.php';
}
if (!function_exists('landing_page_title')) {
    require_once __DIR__ . '/includes/app_settings.php';
}
require_once __DIR__ . '/includes/event_stations.php';
require_once __DIR__ . '/includes/ticket_card.php';

// ✅ Check if PDO connection exists
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("
    <div style='text-align:center;padding:50px;font-family:Arial;'>
        <h2 style='color:#dc3545;'>❌ Database Connection Error</h2>
        <p>Unable to connect to database. Please contact administrator.</p>
        <a href='index.php' style='color:#0d6efd;text-decoration:none;padding:10px 20px;background:#0d6efd;color:white;border-radius:5px;display:inline-block;margin-top:15px;'>Go to Home</a>
    </div>
    ");
}

// ✅ Token-based access (Secure)
$token = $_GET['token'] ?? null;

if (!$token) {
    die("
    <div style='text-align:center;padding:50px;font-family:Arial;'>
        <h2 style='color:#dc3545;'>❌ Invalid Access</h2>
        <p>Receipt token is missing or invalid.</p>
        <a href='index.php' style='color:#0d6efd;text-decoration:none;padding:10px 20px;background:#0d6efd;color:white;border-radius:5px;display:inline-block;margin-top:15px;'>Go to Home</a>
    </div>
    ");
}

try {
    // Fetch application using TOKEN (not ID)
    $stmt = $pdo->prepare("
        SELECT * FROM scholarship_applications 
        WHERE receipt_token = ? 
        AND payment_status = 'paid'
    ");
    $stmt->execute([$token]);
    $application = $stmt->fetch();

    if (!$application) {
        throw new Exception("Receipt not found or payment not completed.");
    }

} catch (Exception $e) {
    die("
    <div style='text-align:center;padding:50px;font-family:Arial;'>
        <h2 style='color:#dc3545;'>❌ Receipt Not Found</h2>
        <p>Invalid or expired receipt token.</p>
        <p>Only paid applications can view receipts.</p>
        <a href='index.php' style='color:#0d6efd;text-decoration:none;padding:10px 20px;background:#0d6efd;color:white;border-radius:5px;display:inline-block;margin-top:15px;'>Go to Home</a>
    </div>
    ");
}

// Application found - show receipt
$application_id = $application['id'];

// Generate Receipt Number
$receipt_no = public_receipt_prefix() . '/2026/' . str_pad($application_id, 5, '0', STR_PAD_LEFT);
$application_code = event_application_code((int) $application_id);

// Format date
$created_date = new DateTime($application['created_at']);
$formatted_date = $created_date->format('d / m / Y');
$full_date_time = $created_date->format('d M Y, h:i A');

// Payment Status
$payment_status_label = [
    'paid' => '✅ SUCCESSFUL',
    'pending' => '⏳ PENDING',
    'failed' => '❌ FAILED'
];
$status_class = [
    'paid' => 'success',
    'pending' => 'warning',
    'failed' => 'danger'
];

$payment_status = $payment_status_label[$application['payment_status']] ?? '⏳ PENDING';
$status_badge_class = $status_class[$application['payment_status']] ?? 'warning';

// Payment Mode Detection
$payment_mode = 'Online Payment';
if (!empty($application['razorpay_payment_id'])) {
    $payment_mode = 'UPI / Card / Netbanking';
}
$fullName = trim($application['first_name'] . ' ' . $application['middle_name'] . ' ' . $application['last_name']);
$instType = (string) ($application['institution_type'] ?? 'school');
$instLabel = function_exists('form_institution_label') ? form_institution_label($instType) : ($instType === 'college' ? 'College' : 'School');
$classLabel = function_exists('form_class_label') ? form_class_label($application['class'] ?? '') : (string) ($application['class'] ?? '');
$feeBase = $application['fee_base'] ?? null;
$feePlat = $application['fee_platform'] ?? null;
$landingTitle = function_exists('landing_page_title') ? landing_page_title() : 'Registration';
$landingSubtitle = function_exists('landing_page_subtitle') ? landing_page_subtitle() : '';
$receiptLogo = function_exists('panel_logo_src') ? panel_logo_src('') : '';
$receiptContact = function_exists('public_contact_oneline') ? public_contact_oneline() : '';
$passUrl = event_pass_url((string) $token);
$qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&ecc=M&margin=8&data=' . rawurlencode($passUrl);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Receipt - <?php echo htmlspecialchars($landingTitle); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Security Meta Tags -->
<meta name="robots" content="noindex, nofollow">
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

<style>
/* Screen View CSS */
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding: 20px;
}
.receipt-container {
    max-width: 800px;
    margin: 0 auto;
    background: #ffffff;
    border-radius: 15px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    overflow: hidden;
}
.receipt-header {
    background: linear-gradient(135deg, #0058F0 0%, #0046C7 100%);
    color: #ffffff;
    padding: 20px;
    text-align: center;
    position: relative;
}
.receipt-header::after {
    content: '';
    position: absolute;
    bottom: -15px;
    left: 0;
    right: 0;
    height: 15px;
    background: #ffffff;
    clip-path: polygon(0 0, 5% 100%, 10% 0, 15% 100%, 20% 0, 25% 100%, 30% 0, 35% 100%, 40% 0, 45% 100%, 50% 0, 55% 100%, 60% 0, 65% 100%, 70% 0, 75% 100%, 80% 0, 85% 100%, 90% 0, 95% 100%, 100% 0);
}
.receipt-logo {
    width: 60px;
    height: 60px;
    margin-bottom: 10px;
    background: #ffffff;
    border-radius: 50%;
    padding: 8px;
}
.receipt-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    letter-spacing: 0.3px;
    line-height: 1.35;
    text-transform: none;
}
.receipt-subtitle {
    font-size: 0.95rem;
    margin-top: 5px;
    opacity: 0.9;
}
.receipt-body {
    padding: 20px;
    position: relative;
}
.status-banner {
    background: #d4edda;
    border: 2px solid #28a745;
    padding: 10px;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 15px;
}
.status-icon {
    font-size: 2rem;
    margin-bottom: 5px;
}
.status-text {
    font-size: 1.2rem;
    font-weight: 700;
    margin: 0;
}
.receipt-info {
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 15px;
}
.info-row {
    display: flex;
    justify-content: space-between;
    padding: 6px 0;
    border-bottom: 1px solid #e9ecef;
}
.info-row:last-child {
    border-bottom: none;
}
.info-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.85rem;
}
.info-value {
    font-weight: 700;
    color: #212529;
    font-size: 0.85rem;
    text-align: right;
}
.section-title {
    font-size: 1rem;
    font-weight: 700;
    color: #0058F0;
    margin: 15px 0 10px 0;
    padding-bottom: 6px;
    border-bottom: 2px solid #0058F0;
}
.detail-box {
    background: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 10px;
}
.detail-item {
    display: flex;
    padding: 5px 0;
    border-bottom: 1px dotted #dee2e6;
    font-size: 0.85rem;
}
.detail-item:last-child {
    border-bottom: none;
}
.detail-label {
    min-width: 160px;
    font-weight: 600;
    color: #495057;
}
.detail-value {
    flex: 1;
    color: #212529;
}
.fee-highlight {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: #ffffff;
    padding: 12px;
    border-radius: 8px;
    text-align: center;
    margin: 15px 0;
}
.fee-amount {
    font-size: 1.8rem;
    font-weight: 700;
}
.transaction-box {
    background: #e7f3ff;
    border: 2px solid #0d6efd;
    border-radius: 6px;
    padding: 10px;
    margin: 10px 0;
}
.transaction-id {
    font-family: 'Courier New', monospace;
    font-size: 0.75rem;
    background: #ffffff;
    padding: 6px 10px;
    border-radius: 4px;
    margin-top: 5px;
    word-break: break-all;
}
.note-box {
    background: #fff3cd;
    border: 2px solid #ffc107;
    border-radius: 6px;
    padding: 10px;
    margin-top: 15px;
}
.note-box ul {
    margin: 8px 0 0 0;
    padding-left: 18px;
    font-size: 0.8rem;
}
.note-box li {
    margin-bottom: 3px;
}
.legal-tiny {
    margin-top: 12px;
    padding-top: 10px;
    border-top: 1px dashed #cfcfcf;
    font-size: 7.5px;
    line-height: 1.35;
    color: #555;
    text-align: justify;
}
.legal-tiny h4 {
    font-size: 8.5px;
    font-weight: 800;
    color: #0058F0;
    margin: 8px 0 4px;
    text-transform: uppercase;
    letter-spacing: .02em;
}
.legal-tiny p { margin: 0 0 4px; }
.legal-tiny ol { margin: 0 0 6px; padding-left: 14px; }
.legal-tiny li { margin-bottom: 3px; }
.legal-tiny a { color: #0058F0; }
.action-buttons {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}
.btn-print, .btn-download, .btn-home {
    flex: 1;
    padding: 10px;
    font-weight: 600;
    border-radius: 6px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-size: 0.85rem;
}
.receipt-footer {
    background: #f8f9fa;
    padding: 15px;
    text-align: center;
    border-top: 2px solid #dee2e6;
}
.footer-text {
    color: #6c757d;
    font-size: 0.8rem;
    margin: 3px 0;
}
.watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-45deg);
    font-size: 4rem;
    font-weight: 900;
    color: rgba(40, 167, 69, 0.03);
    z-index: 0;
    pointer-events: none;
}
.security-notice {
    background: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
    padding: 8px;
    border-radius: 5px;
    margin-bottom: 15px;
    font-size: 0.8rem;
}
.pass-qr-block {
    display: flex;
    gap: 16px;
    align-items: center;
    border: 2px dashed #0058F0;
    border-radius: 12px;
    padding: 14px;
    margin: 12px 0 18px;
    background: #f4f8ff;
    page-break-inside: avoid;
}
.pass-qr-block img, .pass-qr-block canvas {
    width: 150px;
    height: 150px;
    background: #fff;
    border-radius: 8px;
}
.pass-qr-block h3 {
    font-size: 1rem;
    margin: 0 0 6px;
    color: #0058F0;
}
.pass-qr-block ul {
    margin: 0;
    padding-left: 18px;
    font-size: 0.82rem;
    color: #334155;
    columns: 2;
    column-gap: 16px;
}
.pass-qr-block a { font-weight: 700; font-size: 0.85rem; }

/* ==================== A4 PRINT CSS ==================== */
@media print {
    @page {
        size: A4 portrait;
        margin: 10mm;
    }
    
    body {
        background: #ffffff;
        padding: 0;
        margin: 0;
        font-size: 10pt;
    }
    
    .receipt-container {
        max-width: 100%;
        box-shadow: none;
        border-radius: 0;
        page-break-inside: avoid;
    }
    
    .receipt-header {
        padding: 12mm 10mm;
        page-break-inside: avoid;
    }
    
    .receipt-header::after {
        display: none;
    }
    
    .receipt-logo {
        width: 50px;
        height: 50px;
        margin-bottom: 8px;
    }
    
    .receipt-title {
        font-size: 14pt;
    }
    
    .receipt-subtitle {
        font-size: 10pt;
    }
    
    .receipt-body {
        padding: 8mm 10mm;
    }
    
    .status-banner {
        padding: 6mm;
        margin-bottom: 4mm;
        page-break-inside: avoid;
    }
    
    .status-icon {
        font-size: 18pt;
        margin-bottom: 2mm;
    }
    
    .status-text {
        font-size: 12pt;
    }
    
    .receipt-info {
        padding: 3mm;
        margin-bottom: 4mm;
        page-break-inside: avoid;
    }
    
    .info-row {
        padding: 1.5mm 0;
    }
    
    .info-label, .info-value {
        font-size: 9pt;
    }
    
    .section-title {
        font-size: 11pt;
        margin: 4mm 0 3mm 0;
        padding-bottom: 2mm;
        page-break-after: avoid;
    }
    
    .detail-box {
        padding: 3mm;
        margin-bottom: 3mm;
        page-break-inside: avoid;
    }
    
    .detail-item {
        padding: 1.5mm 0;
        font-size: 9pt;
    }
    
    .detail-label {
        min-width: 140px;
    }
    
    .pass-qr-block {
        padding: 3mm;
        margin: 3mm 0;
        page-break-inside: avoid;
    }
    .pass-qr-block img, .pass-qr-block canvas {
        width: 32mm;
        height: 32mm;
    }
    .pass-qr-block ul { columns: 2; font-size: 8pt; }
    
    .fee-amount {
        font-size: 20pt;
    }
    
    .transaction-box {
        padding: 3mm;
        margin: 3mm 0;
        page-break-inside: avoid;
    }
    
    .transaction-id {
        font-size: 8pt;
        padding: 2mm;
    }
    
    .note-box {
        padding: 3mm;
        margin-top: 4mm;
        page-break-inside: avoid;
    }
    
    .note-box ul {
        margin: 2mm 0 0 0;
        padding-left: 5mm;
        font-size: 8.5pt;
    }
    
    .note-box li {
        margin-bottom: 1mm;
    }

    .legal-tiny {
        font-size: 6.5pt;
        line-height: 1.3;
        margin-top: 3mm;
        page-break-inside: auto;
    }
    .legal-tiny h4 { font-size: 7pt; margin: 2mm 0 1mm; }
    
    .receipt-footer {
        padding: 3mm 10mm;
        page-break-inside: avoid;
    }
    
    .footer-text {
        font-size: 8.5pt;
        margin: 1mm 0;
    }
    
    .watermark {
        font-size: 60pt;
        opacity: 0.5;
    }
    
    /* Hide elements not needed in print */
    .action-buttons,
    .btn-print,
    .btn-download,
    .btn-home,
    .security-notice {
        display: none !important;
    }
    
    /* Ensure no page breaks inside important sections */
    .detail-group,
    .transaction-box,
    .note-box {
        page-break-inside: avoid;
    }
    
    /* Compact spacing for A4 */
    h1, h2, h3, h4, h5, h6 {
        margin-top: 2mm;
        margin-bottom: 2mm;
    }
    
    p {
        margin-top: 1mm;
        margin-bottom: 1mm;
    }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .receipt-body {
        padding: 15px;
    }
    .info-row {
        flex-direction: column;
        gap: 3px;
    }
    .info-value {
        text-align: left;
    }
    .action-buttons {
        flex-direction: column;
    }
    .detail-label {
        min-width: 120px;
    }
}
</style>
</head>
<body>

<div class="receipt-container">
    <!-- Header -->
    <div class="receipt-header">
        <?php if ($receiptLogo !== ''): ?>
        <img src="<?= htmlspecialchars($receiptLogo) ?>" class="receipt-logo" alt="">
        <?php endif; ?>
        <h1 class="receipt-title"><?= htmlspecialchars($landingTitle) ?></h1>
        <?php if ($landingSubtitle !== ''): ?>
        <p class="receipt-subtitle"><?= htmlspecialchars($landingSubtitle) ?></p>
        <?php else: ?>
        <p class="receipt-subtitle">APPLICATION RECEIPT</p>
        <?php endif; ?>
    </div>

    <!-- Body -->
    <div class="receipt-body">
        <!-- Security Notice (Hidden in print) -->
        <div class="security-notice">
            <i class="fas fa-lock"></i> <strong>Secure Receipt:</strong> 
            This receipt is accessible only through a unique secure link.
        </div>

        <div class="watermark">PAID</div>
        
        <!-- Status Banner -->
        <div class="status-banner">
            <div class="status-icon">
                <i class="fas fa-check-circle" style="color:#28a745;"></i>
            </div>
            <p class="status-text"><?= $payment_status ?></p>
        </div>

        <?php event_ticket_render($application, true); ?>

        <!-- Receipt Information -->
        <div class="receipt-info">
            <div class="info-row">
                <span class="info-label">Pass number:</span>
                <span class="info-value"><?= htmlspecialchars($application_code) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Receipt No.:</span>
                <span class="info-value"><?= htmlspecialchars($receipt_no) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Date:</span>
                <span class="info-value"><?= htmlspecialchars($formatted_date) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Time:</span>
                <span class="info-value"><?= htmlspecialchars($full_date_time) ?></span>
            </div>
        </div>

        <!-- Student Details -->
        <div class="section-title"><i class="fas fa-user"></i> Guest details</div>
        <div class="detail-box">
            <div class="detail-item">
                <span class="detail-label">Name:</span>
                <span class="detail-value"><strong><?= htmlspecialchars($fullName) ?></strong></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Ticket type:</span>
                <span class="detail-value"><?= htmlspecialchars($classLabel) ?></span>
            </div>
            <div class="detail-item">
                <span class="detail-label">College:</span>
                <span class="detail-value"><?= htmlspecialchars($application['school_name']) ?></span>
            </div>
        </div>

        <!-- Exam Details -->
        <div class="section-title"><i class="fas fa-file-alt"></i> Programme Details</div>
        <div class="detail-box">
            <div class="detail-item">
                <span class="detail-label">Programme:</span>
                <span class="detail-value"><strong><?= htmlspecialchars($landingTitle) ?></strong></span>
            </div>
        </div>

        <!-- Fee Details -->
        <div class="fee-highlight">
            <div style="font-size:0.95rem;margin-bottom:3px;">Amount Paid</div>
            <div class="fee-amount">₹ <?= number_format($application['exam_fee'], 2) ?></div>
            <?php if ($feeBase !== null && $feePlat !== null): ?>
            <div style="font-size:0.8rem;margin-top:3px;opacity:0.95;">
                <?php if (!empty($application['coupon_code'])): ?>
                Coupon <?= htmlspecialchars((string) $application['coupon_code']) ?> ·
                <?php endif; ?>
                <?php if (function_exists('form_fee_absorbed_by_admin') && form_fee_absorbed_by_admin($application)): ?>
                Fee ₹<?= number_format((float) $feeBase, 2) ?> · gateway/platform ₹<?= number_format((float) $feePlat, 2) ?> paid by organiser
                <?php else: ?>
                Fee ₹<?= number_format((float) $feeBase, 2) ?> + gateway/platform ₹<?= number_format((float) $feePlat, 2) ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div style="font-size:0.8rem;margin-top:3px;opacity:0.9;">
                Payment Status: <strong><?= strtoupper($application['payment_status']) ?></strong>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="section-title"><i class="fas fa-credit-card"></i> Payment Details</div>
        <div class="transaction-box">
            <div class="detail-item">
                <span class="detail-label">Payment Mode:</span>
                <span class="detail-value"><?= htmlspecialchars($payment_mode) ?></span>
            </div>
            
            <?php if (!empty($application['razorpay_order_id'])): ?>
            <div style="margin-top:8px;">
                <strong style="color:#0d6efd;font-size:0.85rem;">Order ID:</strong>
                <div class="transaction-id"><?= htmlspecialchars($application['razorpay_order_id']) ?></div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($application['razorpay_payment_id'])): ?>
            <div style="margin-top:6px;">
                <strong style="color:#0d6efd;font-size:0.85rem;">Payment ID:</strong>
                <div class="transaction-id"><?= htmlspecialchars($application['razorpay_payment_id']) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Contact Details -->
        <div class="section-title"><i class="fas fa-phone"></i> Contact Details</div>
        <div class="detail-box">
            <div class="detail-item">
                <span class="detail-label">Mobile:</span>
                <span class="detail-value"><strong>+91 <?= htmlspecialchars($application['mobile']) ?></strong></span>
            </div>
        </div>

        <!-- Important Note -->
        <div class="note-box">
            <strong style="font-size:0.9rem;"><i class="fas fa-info-circle"></i> Important Note:</strong>
            <ul>
                <li>✔ Registration submitted successfully</li>
                <li>✔ Payment received and verified</li>
                <li>✔ Pass number <?= htmlspecialchars($application_code) ?> is your entry pass</li>
                <li>✔ Show this ticket QR at the gate</li>
            </ul>
        </div>
        <?php require __DIR__ . '/includes/legal_terms_html.php'; ?>

        <!-- Action Buttons (Hidden in print) -->
        <div class="action-buttons">
            <a href="my_profile.php?id=<?= (int) $application_id ?>" class="btn btn-primary">
                <i class="fas fa-id-card"></i> My profile
            </a>
            <a href="icard.php?token=<?= urlencode((string) $token) ?>" class="btn btn-success">
                <i class="fas fa-ticket-alt"></i> Ticket
            </a>
            <a href="my_registrations.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> All my forms
            </a>
            <button class="btn btn-primary btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
        </div>
    </div>

    <!-- Footer -->
    <div class="receipt-footer">
        <p class="footer-text"><strong><?= htmlspecialchars($landingTitle) ?></strong></p>
        <?php if ($landingSubtitle !== ''): ?>
        <p class="footer-text"><?= htmlspecialchars($landingSubtitle) ?></p>
        <?php endif; ?>
        <?php if ($receiptContact !== ''): ?>
        <p class="footer-text"><?= htmlspecialchars($receiptContact) ?></p>
        <?php endif; ?>
        <p class="footer-text" style="margin-top:8px;">
            This is a computer-generated receipt. No signature required.
        </p>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
// Disable right-click
document.addEventListener('contextmenu', function(e) {
    e.preventDefault();
});

// Prevent URL manipulation
if (window.history && window.history.pushState) {
    window.history.pushState('forward', null, '');
    window.addEventListener('popstate', function() {
        window.history.pushState('forward', null, '');
    });
}
</script>
<?php if (function_exists('help_whatsapp_button')) { echo help_whatsapp_button(true); } ?>
</body>
</html>