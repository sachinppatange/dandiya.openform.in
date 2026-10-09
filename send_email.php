<?php
/**
 * Email Sending Functions using Google Workspace
 * From: admin@agnipankh.in
 */

// Use Composer autoload
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'email_config.php';

/**
 * Send Payment Success Email to Parent
 * @param array $application - Application data from database
 * @return bool - Success status
 */
function sendPaymentSuccessEmail($application) {
    $mail = new PHPMailer(true);

    try {
        // ==================== SMTP Configuration (Google Workspace) ====================
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;              // smtp.gmail.com
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;          // admin@agnipankh.in
        $mail->Password   = SMTP_PASSWORD;          // App Password
        $mail->SMTPSecure = SMTP_SECURE;            // tls
        $mail->Port       = SMTP_PORT;              // 587
        $mail->CharSet    = 'UTF-8';

        // Enable verbose debug output (uncomment for troubleshooting)
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = 'html';

        // ==================== Recipients ====================
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);  // From: admin@agnipankh.in
        
        // Check if email exists
        if (empty($application['email'])) {
            error_log("Email Error: No email address provided for Application ID: " . $application['id']);
            return false;
        }

        $student_name = trim(($application['first_name'] ?? '') . ' ' . ($application['middle_name'] ?? '') . ' ' . ($application['last_name'] ?? ''));
        $mail->addAddress($application['email'], $student_name);
        
        // Reply-To: admin email (so they can reply)
        if (!function_exists('landing_page_title')) {
            require_once __DIR__ . '/includes/app_settings.php';
        }
        $brand = function_exists('panel_brand_name') ? panel_brand_name() : 'Registration';
        $replyTo = '';
        if (function_exists('get_app_setting')) {
            $replyTo = trim((string) get_app_setting('zepto_reply_to_email', ''));
        }
        if ($replyTo !== '' && stripos($replyTo, 'agnipankh') === false) {
            $mail->addReplyTo($replyTo, $brand);
        }

        $application_code = (function_exists('event_application_code') ? event_application_code((int) $application['id']) : ('REG' . str_pad($application['id'], 5, '0', STR_PAD_LEFT)));
        $receipt_no = (function_exists('public_receipt_prefix') ? public_receipt_prefix() : 'REG') . '/2026/' . str_pad($application['id'], 5, '0', STR_PAD_LEFT);
        
        // Receipt URL with secure token
        if (!empty($application['receipt_token'])) {
            if (!function_exists('app_public_base_url')) {
                require_once __DIR__ . '/includes/staff_repository.php';
            }
            $receipt_url = rtrim(app_public_base_url(), '/') . '/payment_success.php?token=' . urlencode($application['receipt_token']);
        } else {
            $receipt_url = function_exists('app_public_base_url') ? rtrim(app_public_base_url(), '/') . '/' : '';
        }
        
        if (!function_exists('form_class_label')) {
            require_once __DIR__ . '/includes/form_catalog.php';
        }
        if (!function_exists('landing_page_title')) {
            require_once __DIR__ . '/includes/app_settings.php';
        }
        $class_label = form_class_label($application['class'] ?? '');
        $inst_label = form_institution_label($application['institution_type'] ?? 'school');
        $landingTitle = function_exists('landing_page_title') ? landing_page_title() : 'Registration';
        $landingSubtitle = function_exists('landing_page_subtitle') ? landing_page_subtitle() : '';
        $logoSrc = function_exists('panel_logo_src') ? panel_logo_src('') : '';
        if ($logoSrc !== '' && strpos($logoSrc, 'http') !== 0 && function_exists('app_public_base_url')) {
            $logoSrc = rtrim(app_public_base_url(), '/') . '/' . ltrim($logoSrc, '/');
        }
        $contactLine = function_exists('public_contact_oneline') ? public_contact_oneline() : '';
        $contactLis = '';
        if ($contactLine !== '') {
            $contactLis = '<li>✔ ' . htmlspecialchars($contactLine) . '</li>';
        }
        
        $payment_date = date('d M Y, h:i A');

        // ==================== Email Content ====================
        $mail->isHTML(true);
        $mail->Subject = 'Payment Successful - ' . $landingTitle;

        // HTML Body
        $mail->Body = '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 0; padding: 0; }
        .email-container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #0058F0 0%, #0046C7 100%); color: #ffffff; padding: 30px 20px; text-align: center; }
        .header img { width: 70px; height: 70px; margin-bottom: 10px; background: #fff; border-radius: 50%; padding: 8px; }
        .header h1 { margin: 0; font-size: 1.4rem; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 5px 0 0 0; font-size: 0.9rem; opacity: 0.9; }
        .content { padding: 30px 20px; }
        .success-badge { background: #d4edda; border: 2px solid #28a745; color: #155724; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
        .success-badge h2 { margin: 0; font-size: 1.3rem; font-weight: 700; }
        .success-badge p { margin: 5px 0 0 0; }
        .info-box { background: #f8f9fa; border: 2px dashed #dee2e6; border-radius: 8px; padding: 15px; margin: 20px 0; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e9ecef; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: 600; color: #6c757d; font-size: 0.9rem; }
        .info-value { font-weight: 700; color: #212529; font-size: 0.9rem; }
        .section-title { font-size: 1.1rem; font-weight: 700; color: #0058F0; margin: 20px 0 12px 0; padding-bottom: 8px; border-bottom: 2px solid #0058F0; }
        .detail-box { background: #ffffff; border: 1px solid #dee2e6; border-radius: 6px; padding: 12px; margin-bottom: 15px; }
        .detail-item { padding: 6px 0; border-bottom: 1px dotted #dee2e6; font-size: 0.9rem; }
        .detail-item:last-child { border-bottom: none; }
        .detail-label { font-weight: 600; color: #495057; display: inline-block; min-width: 130px; }
        .detail-value { color: #212529; }
        .fee-highlight { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #ffffff; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0; }
        .fee-amount { font-size: 2rem; font-weight: 700; margin: 10px 0; }
        .btn-primary { display: inline-block; background: #0d6efd; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 15px 0; }
        .note-box { background: #fff3cd; border: 2px solid #ffc107; border-radius: 6px; padding: 15px; margin: 20px 0; }
        .note-box strong { font-size: 1rem; }
        .note-box ul { margin: 10px 0 0 0; padding-left: 20px; }
        .note-box li { margin-bottom: 5px; font-size: 0.9rem; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-top: 2px solid #dee2e6; }
        .footer p { margin: 5px 0; color: #6c757d; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            ' . ($logoSrc !== '' ? '<img src="' . htmlspecialchars($logoSrc) . '" alt="">' : '') . '
            <h1>' . htmlspecialchars($landingTitle) . '</h1>
            <p>' . htmlspecialchars($landingSubtitle !== '' ? $landingSubtitle : 'Application Receipt') . '</p>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Success Badge -->
            <div class="success-badge">
                <h2>✅ Payment Successful!</h2>
                <p>Your application has been confirmed</p>
            </div>

            <!-- Greeting -->
            <p style="font-size:1rem;margin-bottom:15px;">
                Dear <strong>' . htmlspecialchars($student_name) . '</strong>,
            </p>
            <p style="margin-bottom:20px;">
                Thank you for registering <strong>' . htmlspecialchars($student_name) . '</strong> for 
                <strong>' . htmlspecialchars($landingTitle) . '</strong>. 
                Your payment has been received successfully and the application is confirmed.
            </p>

            <!-- Application Info -->
            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">Application ID:</span>
                    <span class="info-value">' . htmlspecialchars($application_code) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Receipt No.:</span>
                    <span class="info-value">' . htmlspecialchars($receipt_no) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Payment Date:</span>
                    <span class="info-value">' . htmlspecialchars($payment_date) . '</span>
                </div>
            </div>

            <!-- Student Details -->
            <div class="section-title">👤 Student Details</div>
            <div class="detail-box">
                <div class="detail-item">
                    <span class="detail-label">Student Name:</span>
                    <span class="detail-value"><strong>' . htmlspecialchars($student_name) . '</strong></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Class:</span>
                    <span class="detail-value">' . htmlspecialchars($class_label) . '</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">' . htmlspecialchars($inst_label) . ' Name:</span>
                    <span class="detail-value">' . htmlspecialchars($application['school_name']) . '</span>
                </div>
            </div>

            <!-- Fee Details -->
            <div class="fee-highlight">
                <div style="font-size:1rem;margin-bottom:5px;">Amount Paid</div>
                <div class="fee-amount">₹ ' . number_format($application['exam_fee'], 2) . '</div>
                ' . (!empty($application['coupon_code']) ? '<div style="font-size:0.9rem;margin-top:5px;">Coupon: <strong>' . htmlspecialchars((string) $application['coupon_code']) . '</strong></div>' : '') . '
                ' . ((function_exists('form_fee_absorbed_by_admin') && form_fee_absorbed_by_admin($application)) ? '<div style="font-size:0.85rem;margin-top:5px;">Gateway fee paid by organiser</div>' : '') . '
                <div style="font-size:0.9rem;margin-top:5px;">Payment Status: <strong>SUCCESSFUL</strong></div>
            </div>

            <!-- Contact Details -->
            <div class="section-title">📞 Contact Details</div>
            <div class="detail-box">
                <div class="detail-item">
                    <span class="detail-label">Mobile:</span>
                    <span class="detail-value">+91 ' . htmlspecialchars($application['mobile']) . '</span>
                </div>
            </div>

            <!-- View Receipt Button -->
            <div style="text-align:center;margin:25px 0;">
                <a href="' . $receipt_url . '" class="btn-primary" style="color:#ffffff;">
                    📄 View Full Receipt Online
                </a>
            </div>

            <!-- Important Note -->
            <div class="note-box">
                <strong>📌 Important Information:</strong>
                <ul>
                    <li>✔ Keep your Application ID (<strong>' . htmlspecialchars($application_code) . '</strong>) safe</li>
                    <li>✔ Details will be sent via SMS/Email</li>
                    ' . $contactLis . '
                </ul>
            </div>

            <p style="margin-top:20px;font-size:0.95rem;">
                Wishing you all the best.<br><br>
                <strong>Warm regards,<br>' . htmlspecialchars($landingTitle) . '</strong>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p style="font-weight:700;font-size:1rem;margin-bottom:8px;">' . htmlspecialchars($landingTitle) . '</p>
            ' . ($landingSubtitle !== '' ? '<p>' . htmlspecialchars($landingSubtitle) . '</p>' : '') . '
            ' . ($contactLine !== '' ? '<p>' . htmlspecialchars($contactLine) . '</p>' : '') . '
            <p style="margin-top:12px;font-size:0.75rem;color:#999;">
                This is a computer-generated receipt.
            </p>
        </div>
    </div>
</body>
</html>
        ';

        // Plain text version
        $mail->AltBody = "
{$landingTitle} - Payment Receipt
" . ($landingSubtitle !== '' ? $landingSubtitle . "\n" : "") . "
Dear {$student_name},

Payment Successful!

Application ID: {$application_code}
Receipt No.: {$receipt_no}
Payment Date: {$payment_date}

STUDENT DETAILS:
Name: {$student_name}
Class: {$class_label}
{$inst_label}: {$application['school_name']}

AMOUNT PAID: ₹{$application['exam_fee']}
" . (!empty($application['coupon_code']) ? "Coupon: {$application['coupon_code']}\n" : "") . "
Payment Status: SUCCESSFUL

Contact: +91 {$application['mobile']}
Email: {$application['email']}

View Receipt: {$receipt_url}

Important:
- Keep Application ID safe
- Details via SMS/Email
" . ($contactLine !== '' ? "- {$contactLine}\n" : "") . "

Best wishes
{$landingTitle}
        ";

        // Send email
        $mail->send();
        
        // Log success
        error_log("Email sent successfully to: {$application['email']} (ID: {$application_code})");
        
        return true;

    } catch (Exception $e) {
        // Log error
        error_log("❌ Email sending failed to {$application['email']}: {$mail->ErrorInfo}");
        error_log("Exception: " . $e->getMessage());
        
        return false;
    }
}
?>