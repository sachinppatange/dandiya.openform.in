<?php
/**
 * Email Configuration for Google Workspace
 * Using admin@agnipankh.in
 */

// Google Workspace SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);                        // TLS साठी 587 (Recommended)
define('SMTP_SECURE', 'tls');                    // ssl ऐवजी tls वापरा
define('SMTP_USERNAME', 'admin@agnipankh.in');   // तुमचा Google Workspace email
define('SMTP_PASSWORD', 'dpsohowkfklxkwha');     // Gmail App Password
define('SMTP_FROM_EMAIL', 'admin@agnipankh.in'); 
define('SMTP_FROM_NAME', 'AGNIPANKH Scholarship');

/**
 * Note: If using regular Gmail (not Google Workspace):
 * 1. Enable 2-Step Verification in Gmail
 * 2. Generate App Password: https://myaccount.google.com/apppasswords
 * 3. Use that 16-character password above
 */

/**
 * Alternative SSL settings (if TLS doesn't work):
 * define('SMTP_PORT', 465);
 * define('SMTP_SECURE', 'ssl');
 */
?>