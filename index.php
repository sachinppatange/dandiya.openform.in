<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // Production: 0, Development: 1
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// ============================================
// CONFIGURATION
// ============================================

// Database Connection
require_once 'db.php';
require_once __DIR__ . '/includes/sms_otp.php';
require_once __DIR__ . '/includes/staff_repository.php';
require_once __DIR__ . '/includes/student_repository.php';
require_once __DIR__ . '/includes/staff_auth.php';
if (is_staff_logged_in() && !is_student_logged_in() && empty($_SESSION['admin_auth_user'])) {
    header('Location: staff/dashboard.php');
    exit;
}
require_form_login();
capture_staff_referral();
$formUser = get_form_user();
$lockedStaff = get_locked_referral_staff();
$staffOptions = get_active_staff_options();

require_once __DIR__ . '/includes/form_catalog.php';
require_once __DIR__ . '/includes/coupons.php';
require_once __DIR__ . '/includes/colleges.php';
require_once __DIR__ . '/includes/payment_service.php';
ensure_form_catalog_schema();

// Check Razorpay Availability
$razorpay_available = false;
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
    if (class_exists('Razorpay\Api\Api')) {
        $razorpay_available = true;
    }
}

// Razorpay credentials come from Admin → Settings → Payments
$rzp = razorpay_config();
$razor_key_id = $rzp['key_id'];
$razor_key_secret = $rzp['key_secret'];
$razorpay_available = $razorpay_available && $rzp['ready'];

if (!defined('TEST_MODE')) {
    define('TEST_MODE', $rzp['test_mode']);
}

$feeStructure = $rzp['fees'];
$feeBreak = form_fee_breakdown();
$classLabels = form_all_class_labels();
$landingTitle = landing_page_title();
$landingSubtitle = landing_page_subtitle();

// Initialize Variables
$errors = [];
$success = false;
$applicationData = [];
$couponNotice = '';
$couponError = '';
$appliedCoupon = null;

// ============================================
// DATABASE CONNECTION CHECK
// ============================================
if (!isset($pdo) || !($pdo instanceof PDO)) {
    die("❌ Database Error: Connection not established. Please check db.php file.");
}

// ============================================
// HANDLE BACK TO EDIT / APPLY COUPON
// ============================================
if (isset($_POST['back_to_edit']) || isset($_POST['apply_coupon'])) {
    $applicationData = $_POST;
    $success = false;
    unset($_POST['submit']);
}

if (coupons_on_form()) {
    $postedCoupon = coupon_normalize((string) ($_POST['coupon_code'] ?? $applicationData['coupon_code'] ?? ''));
    if ($postedCoupon !== '') {
        $appliedCoupon = coupon_find_usable($postedCoupon);
        if ($appliedCoupon) {
            $feeBreak = form_fee_breakdown($appliedCoupon);
            $couponNotice = 'Coupon ' . $appliedCoupon['code'] . ' applied. Registration fee is ₹' . number_format((float) $appliedCoupon['fee_amount'], 0) . '.';
        } else {
            $couponError = 'This coupon is not valid, or it has no uses left.';
        }
    }
}

// ============================================
// HANDLE FORM SUBMISSION
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    
    // Sanitize and collect input data
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $institution_type = 'academia';
    $class = trim($_POST['class'] ?? '');
    $school_name = '';
    $college_id = null;
    $collegePick = college_apply_post($_POST);
    if (!$collegePick['ok']) {
        $errors[] = $collegePick['error'];
    } else {
        $school_name = (string) $collegePick['school_name'];
        $college_id = $collegePick['college_id'];
    }
    $mobile = local_10_digit((string) ($formUser['phone'] ?? ''));
    $staff_referred = strtolower(trim((string) ($_POST['staff_referred'] ?? '')));
    $staff_id = (int) ($_POST['staff_id'] ?? 0);
    if ($lockedStaff) {
        $staff_id = (int) $lockedStaff['id'];
        $staff_referred = 'yes';
    } elseif (function_exists('staff_ask_on_form') && staff_ask_on_form()) {
        if (!in_array($staff_referred, ['yes', 'no'], true)) {
            $errors[] = "Please choose Yes or No: did someone give you this form?";
        } elseif ($staff_referred === 'no') {
            $staff_id = 0;
        } elseif ($staff_id < 1) {
            $errors[] = "Please select the staff member who gave you this form";
        }
    } else {
        $staff_id = 0;
        $staff_referred = 'no';
    }
    
    if (empty($first_name)) {
        $errors[] = "First Name is required";
    } elseif (strlen($first_name) < 2) {
        $errors[] = "First Name must be at least 2 characters";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last Name is required";
    } elseif (strlen($last_name) < 2) {
        $errors[] = "Last Name must be at least 2 characters";
    }

    if (!form_valid_class($institution_type, $class)) {
        $errors[] = "Please select a ticket type";
    }

    if (empty($mobile) || !preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
        $errors[] = "Your login mobile number is required. Please log in again.";
    }

    if ($staff_id > 0 && !is_active_staff_id($staff_id)) {
        $errors[] = "Please select a valid staff member";
    }

    if (empty($_POST['declaration_accept'])) {
        $errors[] = "Please read the declaration and rules, then tick the acceptance checkbox";
    }

    if (function_exists('icard_photo_on_form') && icard_photo_on_form()) {
        if (empty($_FILES['photo']['tmp_name']) || (int) ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $errors[] = "Please upload a passport-style photo for the Digital I-Card";
        }
    }

    if (coupons_on_form()) {
        $postedCoupon = coupon_normalize((string) ($_POST['coupon_code'] ?? ''));
        if ($postedCoupon !== '' && !$appliedCoupon) {
            $errors[] = $couponError !== '' ? $couponError : 'This coupon is not valid, or it has no uses left.';
        }
    }
    
    $exam_fee = $feeBreak['total'];
    $fee_base = $feeBreak['base'];
    $fee_platform = $feeBreak['platform'];
    
    // ============================================
    // DATABASE INSERTION
    // ============================================
    if (empty($errors)) {
        try {
            $studentId = ($formUser['type'] === 'student' ? (int) $formUser['id'] : null);
            $submitPhone = $formUser['phone'] ?? '';
            $application_id = save_scholarship_application($pdo, [
                'first_name' => $first_name,
                'middle_name' => $middle_name,
                'last_name' => $last_name,
                'class' => $class,
                'school_name' => $school_name,
                'college_id' => $college_id,
                'mobile' => $mobile,
                'exam_fee' => $exam_fee,
                'submitted_by_staff_id' => $staff_id ?: null,
                'submitted_by_student_id' => $studentId ?: null,
                'submitted_by_phone' => $submitPhone,
                'institution_type' => $institution_type,
                'fee_base' => $fee_base,
                'fee_platform' => $fee_platform,
                'coupon_id' => $appliedCoupon ? (int) $appliedCoupon['id'] : null,
                'coupon_code' => $appliedCoupon ? (string) $appliedCoupon['code'] : null,
            ]);

            if ($appliedCoupon) {
                coupon_increment_use((int) $appliedCoupon['id']);
            }

            if (!empty($_FILES['photo']['tmp_name']) && function_exists('save_icard_photo')) {
                $photoSave = save_icard_photo($_FILES['photo'], (int) $application_id);
                if (!$photoSave['ok'] && empty($photoSave['skipped'])) {
                    $errors[] = $photoSave['error'] ?? 'Could not save the I-Card photo';
                }
            }
            
            // ============================================
            // RAZORPAY ORDER CREATION
            // ============================================
            $razorpay_order_id = null;
            if ($razorpay_available) {
                try {
                    $api = new Razorpay\Api\Api($razor_key_id, $razor_key_secret);
                    $orderData = [
                        'receipt'         => (TEST_MODE ? 'AGNIP_TEST_' : 'AGNIP_') . $application_id,
                        'amount'          => $feeBreak['paise'],
                        'currency'        => 'INR',
                        'payment_capture' => 1
                    ];
                    $razorpayOrder = $api->order->create($orderData);
                    $razorpay_order_id = $razorpayOrder['id'];
                    
                    // Update order_id in database
                    $stmt = $pdo->prepare("UPDATE scholarship_applications SET razorpay_order_id = ? WHERE id = ?");
                    $updateResult = $stmt->execute([$razorpay_order_id, $application_id]);
                    
                    if (!$updateResult) {
                        throw new Exception("Failed to update Razorpay order ID");
                    }
                    
                } catch (Exception $e) {
                    error_log("Razorpay Order Creation Failed: " . $e->getMessage());
                    $errors[] = "Payment gateway error: " . $e->getMessage();
                }
            }
            
            // Store data for confirmation display
            if (empty($errors)) {
                $applicationData = [
                    'application_id' => $application_id,
                    'first_name' => $first_name,
                    'middle_name' => $middle_name,
                    'last_name' => $last_name,
                    'full_name' => trim($first_name . ' ' . $middle_name . ' ' . $last_name),
                    'class' => $class,
                    'class_label' => form_class_label($class),
                    'institution_type' => $institution_type,
                    'institution_label' => form_institution_label($institution_type),
                    'school_name' => $school_name,
                    'college_id' => $college_id,
                    'college_other' => trim((string) ($_POST['college_other'] ?? '')),
                    'mobile' => $mobile,
                    'exam_fee' => $exam_fee,
                    'fee_base' => $fee_base,
                    'fee_platform' => $fee_platform,
                    'fee_percent' => $feeBreak['percent'],
                    'fee_payer' => $feeBreak['payer'] ?? 'user',
                    'razorpay_order_id' => $razorpay_order_id,
                    'staff_id' => $staff_id,
                    'staff_name' => $staff_id ? (get_staff_by_id($staff_id)['name'] ?? '') : '',
                    'staff_referred' => $staff_referred,
                    'coupon_code' => $appliedCoupon ? (string) $appliedCoupon['code'] : '',
                ];

                if ($formUser['type'] === 'student' && !empty($formUser['id'])) {
                    update_student_profile((int) $formUser['id'], $applicationData['full_name']);
                    $_SESSION['student_auth_name'] = $applicationData['full_name'];
                    $formUser['name'] = $applicationData['full_name'];
                }
                
                $success = true;
            }
            
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            $errors[] = "Database Error: Unable to save application. Please try again.";
            if (strpos($e->getMessage(), 'Unknown column') !== false) {
                $errors[] = "Database needs an update. Ask admin to run schema_updates.sql.";
            }
            $applicationData = $_POST;
        } catch (Exception $e) {
            error_log("General Error: " . $e->getMessage());
            $errors[] = "Error: " . $e->getMessage();
            $applicationData = $_POST;
        }
    } else {
        $applicationData = $_POST;
    }
}

// ============================================
// HANDLE PAYMENT VERIFICATION
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['razorpay_payment_id'])) {
    $razorpay_order_id = $_POST['razorpay_order_id'] ?? '';
    $razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
    $razorpay_signature = $_POST['razorpay_signature'] ?? '';
    $application_id = $_POST['application_id'] ?? '';
    
    if (empty($razorpay_order_id) || empty($razorpay_payment_id) || empty($razorpay_signature) || empty($application_id)) {
        error_log("❌ Payment verification failed: Missing parameters");
        header('Location: payment_failed.php?error=missing_parameters');
        exit;
    }
    
    try {
        if ($razorpay_available) {
            $api = new Razorpay\Api\Api($razor_key_id, $razor_key_secret);
            $attributes = [
                'razorpay_order_id' => $razorpay_order_id,
                'razorpay_payment_id' => $razorpay_payment_id,
                'razorpay_signature' => $razorpay_signature
            ];
            
            // Verify payment signature
            $api->utility->verifyPaymentSignature($attributes);

            $stmt = $pdo->prepare('SELECT * FROM scholarship_applications WHERE id = ?');
            $stmt->execute([(int) $application_id]);
            $app_row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$app_row) {
                throw new Exception('Application data not found');
            }
            if (!empty($app_row['razorpay_order_id']) && $app_row['razorpay_order_id'] !== $razorpay_order_id) {
                throw new Exception('Order ID does not match this registration');
            }

            $paid = mark_application_paid($app_row, $razorpay_payment_id, $razorpay_signature, 'checkout');
            if (!$paid['ok'] || empty($paid['token'])) {
                throw new Exception($paid['message'] ?? 'Failed to update payment status');
            }

            header('Location: payment_success.php?token=' . $paid['token']);
            exit;
            
        } else {
            throw new Exception("Payment gateway not available");
        }
        
    } catch (Exception $e) {
        error_log("❌ Payment verification failed: " . $e->getMessage());
        
        // Update payment status to failed
        try {
            $stmt = $pdo->prepare("UPDATE scholarship_applications SET payment_status = 'failed' WHERE id = ?");
            $stmt->execute([$application_id]);
        } catch (Exception $dbError) {
            error_log("❌ Failed to update payment status: " . $dbError->getMessage());
        }
        
        header('Location: payment_failed.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($landingTitle . ($landingSubtitle !== '' ? ' — ' . $landingSubtitle : '')); ?>">
    <title><?php echo htmlspecialchars($landingTitle); ?><?php echo TEST_MODE ? ' [TEST MODE]' : ''; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Razorpay Script -->
    <?php if ($razorpay_available): ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <?php endif; ?>
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="assets/css/theme.css?v=2">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f4f6f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        /* Test Mode Banner */
        .test-banner {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            color: #fff;
            padding: 12px;
            text-align: center;
            font-weight: bold;
            font-size: 1.1rem;
            border-bottom: 3px solid #e65100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .security-badge {
            background: #d4edda;
            border: 2px solid #28a745;
            color: #155724;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 0;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        /* Header */
        .user-bar {
            background: #0058F0;
            color: #fff;
            padding: 10px 0;
            font-size: 0.95rem;
        }
        .user-bar-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 0.9rem;
        }
        .user-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: flex-end;
            flex: 1 1 240px;
            min-width: 0;
            max-width: 100%;
        }
        .user-bar a {
            color: #fff;
            font-weight: 800;
            font-size: 13px;
            text-decoration: none;
            background: rgba(255,255,255,.16);
            padding: 8px 12px;
            border-radius: 999px;
        }
        .user-bar a.on {
            background: #fff;
            color: #0058F0;
        }

        .header {
            background: #ffffff;
            border-bottom: 3px solid #0058F0;
            padding: 20px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .logo {
            height: 72px;
            width: 72px;
            object-fit: contain;
            background: #fff;
            border-radius: 16px;
            padding: 6px;
            box-shadow: 0 8px 20px rgba(0, 88, 240, .12);
        }
        
        .header h4 {
            color: #0058F0;
            font-weight: 700;
            margin: 10px 0 5px 0;
        }
        
        /* Card Styles */
        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 16px rgba(0, 88, 240, 0.10);
        }
        
        .section-title {
            font-weight: 700;
            color: #0058F0;
            border-bottom: 3px solid #0058F0;
            padding-bottom: 10px;
            margin-bottom: 25px;
            font-size: 1.2rem;
        }
        .declaration-box {
            background: #f7f9fc;
            border: 1px solid #dbe3ec;
            border-radius: 10px;
            padding: 16px 18px;
            margin-bottom: 18px;
        }
        .declaration-box p { margin-bottom: 10px; color: #0058F0; }
        .declaration-links {
            list-style: none;
            padding: 0;
            margin: 0 0 16px;
        }
        .declaration-links li { margin-bottom: 8px; }
        .declaration-links a {
            color: #0058F0;
            font-weight: 700;
            text-decoration: underline;
        }
        .declaration-links a:hover { color: #3D7FFF; }
        .declaration-check {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        .declaration-check .form-check-input {
            width: 18px;
            height: 18px;
            margin-top: 4px;
            flex: 0 0 18px;
        }
        .declaration-check .form-check-label {
            font-size: 14px;
            line-height: 1.55;
            color: #222;
        }
        
        /* Instructions Box */
        .instructions-box {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            border-left: 5px solid #2196f3;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(33, 150, 243, 0.2);
        }
        
        .instructions-box h5 {
            color: #0d47a1;
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .instructions-box ul {
            margin-bottom: 0;
            padding-left: 20px;
            color: #1565c0;
        }
        
        .instructions-box li {
            margin-bottom: 12px;
            line-height: 1.6;
        }
        
        .instructions-box strong {
            display: block;
            margin-bottom: 3px;
        }
        
        .instructions-box small {
            color: #1976d2;
            display: block;
            margin-top: 3px;
        }
        
        .instructions-box .warning {
            color: #d32f2f;
        }
        
        .instructions-box .warning strong {
            color: #d32f2f;
        }
        
        .instructions-box .warning small {
            color: #c62828;
        }
        
        /* Form Elements */
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #0058F0;
            box-shadow: 0 0 0 0.2rem rgba(13, 59, 102, 0.25);
        }
        
        /* Error Messages */
        .error-message {
            background: #f8d7da;
            color: #842029;
            border: 2px solid #f5c2c7;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .error-message ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        
        .error-message li {
            margin-bottom: 5px;
        }
        
        /* Confirmation Section */
        .confirmation-section {
            background: linear-gradient(135deg, #e7f3ff 0%, #f0f8ff 100%);
            border: 3px solid #0d6efd;
            padding: 30px;
            border-radius: 12px;
        }
        
        .confirmation-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #0d6efd;
        }
        
        .confirmation-header h4 {
            color: #0058F0;
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 1.5rem;
        }
        
        /* Detail Groups */
        .detail-group {
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .detail-group-title {
            font-weight: 700;
            color: #0058F0;
            font-size: 1.1rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            font-weight: 600;
            min-width: 200px;
            color: #6c757d;
            font-size: 0.95rem;
        }
        
        .detail-value {
            flex: 1;
            color: #212529;
            font-size: 0.95rem;
        }
        
        /* Fee Highlight */
        .fee-highlight {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 25px 0;
            font-size: 1.3rem;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        /* Buttons */
        .btn-payment {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            padding: 14px 35px;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            transition: all 0.3s;
            border-radius: 8px;
        }
        
        .btn-payment:hover {
            background: linear-gradient(135deg, #218838 0%, #1ea87a 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(40, 167, 69, 0.4);
            color: white;
        }
        
        .btn-edit {
            background: #6c757d;
            border: none;
            padding: 14px 35px;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            border-radius: 8px;
        }
        
        .btn-edit:hover {
            background: #5a6268;
            color: white;
        }
        
        .btn-primary {
            background: #0058F0;
            border: none;
            padding: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: #0a2d4d;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(13, 59, 102, 0.3);
        }
        
        /* Footer */
        .footer {
            background: #0058F0;
            color: #fff;
            padding: 25px 0;
            margin-top: 50px;
        }
        
        /* Alerts */
        .alert {
            border-radius: 8px;
            padding: 15px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .detail-label {
                min-width: 150px;
                font-size: 0.85rem;
            }
            
            .detail-value {
                font-size: 0.85rem;
            }
            
            .logo {
                height: 50px;
            }
            
            .header h4 {
                font-size: 1.1rem;
            }
            
            .fee-highlight {
                font-size: 1.1rem;
            }
            
            .instructions-box {
                padding: 15px;
            }
            
            .instructions-box h5 {
                font-size: 1.1rem;
            }
        }
    </style>
</head>

<body>

<div class="user-bar">
    <div class="container user-bar-inner">
        <div>
            Logged in:
            <strong><?php echo htmlspecialchars($formUser['name'] ?: 'Student'); ?></strong>
            (<?php echo htmlspecialchars($formUser['type'] === 'admin' ? 'Admin' : 'Student'); ?>
            · +91 <?php echo htmlspecialchars(local_10_digit($formUser['phone'])); ?>)
        </div>
        <div class="user-nav">
            <a href="my_registrations.php">My registrations</a>
            <a class="on" href="index.php">Registration form</a>
            <?php if (function_exists('help_whatsapp_url') && help_whatsapp_url() !== ''): ?>
            <a href="<?php echo htmlspecialchars(help_whatsapp_url()); ?>" target="_blank" rel="noopener">Help</a>
            <?php endif; ?>
            <a href="<?php echo $formUser['type'] === 'admin' ? 'adminpanel/admin_logout.php' : 'student_logout.php'; ?>">Logout</a>
        </div>
    </div>
</div>

<!-- Test Mode Banner -->
<?php if (TEST_MODE): ?>
<div class="test-banner">
    ⚠️ TEST MODE ACTIVE - Payment: ₹1 Only - FOR TESTING PURPOSE ONLY
    <div class="security-badge">
        🔐 Secure Token-Based Receipt System | 📧 Email Notifications Enabled
    </div>
</div>
<?php endif; ?>

<!-- Header -->
<div class="header text-center">
    <div class="container">
        <img src="<?php echo htmlspecialchars(panel_logo_src('')); ?>" class="logo mb-2" alt="">
        <h4 class="mb-0">
            <?php echo htmlspecialchars($landingTitle); ?>
        </h4>
        <?php if ($landingSubtitle !== ''): ?>
        <p class="mb-0 mt-2" style="font-size:0.95rem;opacity:.9;"><?php echo htmlspecialchars($landingSubtitle); ?></p>
        <?php endif; ?>
        <?php if (TEST_MODE): ?>
        <small class="text-danger fw-bold">[TEST MODE]</small>
        <?php endif; ?>
    </div>
</div>

<!-- Main Content -->
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <?php
            $myApps = function_exists('applications_for_account') ? applications_for_account() : [];
            if ($myApps && empty($success)):
            ?>
            <div class="alert alert-info" style="border-radius:12px;">
                This mobile already has <strong><?php echo count($myApps); ?></strong> registration(s).
                You can open them or fill another form below (another child / student).
                <div class="mt-2">
                    <a class="btn btn-sm btn-primary" href="my_registrations.php">View my forms</a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
            <div class="error-message">
                <strong>⚠️ Please fix the following errors:</strong>
                <ul class="mt-2">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <!-- =============================================== -->
            <!-- PAYMENT CONFIRMATION PAGE -->
            <!-- =============================================== -->
            <div class="card shadow">
                <div class="card-body p-4">
                    
                    <div class="confirmation-section">
                        
                        <div class="confirmation-header">
                            <p class="mb-0">Please review your details carefully before proceeding to payment</p>
                            <?php if (TEST_MODE): ?>
                            <small class="text-danger fw-bold">[TEST MODE - Payment: ₹<?php echo $applicationData['exam_fee']; ?>]</small>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Student Details -->
                        <div class="detail-group">
                            <div class="detail-group-title">👤 Guest details</div>
                            
                            <div class="detail-row">
                                <div class="detail-label">Full Name:</div>
                                <div class="detail-value"><strong><?php echo htmlspecialchars($applicationData['full_name']); ?></strong></div>
                            </div>
                            
                            <div class="detail-row">
                                <div class="detail-label">College / organisation:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($applicationData['school_name']); ?></div>
                            </div>
                            
                            <div class="detail-row">
                                <div class="detail-label">Ticket type:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($applicationData['class_label']); ?></div>
                            </div>
                        </div>
                        
                        <?php if (!empty($applicationData['staff_name']) || (($applicationData['staff_referred'] ?? '') === 'yes')): ?>
                        <div class="detail-group">
                            <div class="detail-group-title">Staff</div>
                            <div class="detail-row">
                                <div class="detail-label">Referred by staff:</div>
                                <div class="detail-value"><?php echo htmlspecialchars((string) ($applicationData['staff_name'] ?: 'Yes')); ?></div>
                            </div>
                        </div>
                        <?php elseif (($applicationData['staff_referred'] ?? '') === 'no'): ?>
                        <div class="detail-group">
                            <div class="detail-group-title">Staff</div>
                            <div class="detail-row">
                                <div class="detail-label">Referred by staff:</div>
                                <div class="detail-value">No</div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Exam Fee -->
                        <div class="fee-highlight">
                            <?php if (!empty($applicationData['coupon_code'])): ?>
                            Coupon: <strong><?php echo htmlspecialchars((string) $applicationData['coupon_code']); ?></strong><br>
                            <?php endif; ?>
                            <?php echo form_fee_summary_html([
                                'base' => $applicationData['fee_base'],
                                'percent' => $applicationData['fee_percent'],
                                'platform' => $applicationData['fee_platform'],
                                'total' => $applicationData['exam_fee'],
                                'payer' => $applicationData['fee_payer'] ?? 'user',
                            ]); ?>
                            <?php if (TEST_MODE): ?>
                            <small style="display:block;font-size:0.9rem;margin-top:8px;font-weight:400;">(Test mode — base fee is ₹1)</small>
                            <?php endif; ?>
                        </div>
                        
                    </div>
                    
                    <div class="declaration-box mt-4 mb-0">
                        <p class="fw-semibold">Please read these documents before payment:</p>
                        <ul class="declaration-links">
                            <li>
                                <a href="<?php echo htmlspecialchars(legal_declaration_url()); ?>" target="_blank" rel="noopener">Parents/Guardian’s Declaration</a>
                            </li>
                            <li>
                                <a href="<?php echo htmlspecialchars(legal_rules_url()); ?>" target="_blank" rel="noopener">Rules and Regulation</a>
                            </li>
                        </ul>
                        <div class="form-check declaration-check">
                            <input class="form-check-input"
                                   type="checkbox"
                                   name="declaration_accept_pay"
                                   id="declaration_accept_pay"
                                   value="1"
                                   <?php echo !empty($_POST['declaration_accept']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="declaration_accept_pay">
                                <span class="text-danger">*</span>
                                <?php echo htmlspecialchars(legal_checkbox_text()); ?>
                            </label>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-3 mt-4 flex-wrap">
                        <form method="post" class="flex-fill">
                            <?php foreach ($_POST as $key => $value): ?>
                                <?php if ($key !== 'submit' && $key !== 'back_to_edit'): ?>
                                    <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <button type="submit" name="back_to_edit" class="btn btn-edit btn-lg w-100">
                                ← Back to Edit
                            </button>
                        </form>
                        
                        <?php if ($razorpay_available && !empty($applicationData['razorpay_order_id'])): ?>
                        <button id="rzp-button" class="btn btn-payment btn-lg flex-fill">
                            <?php echo TEST_MODE ? 'Test' : ''; ?> Pay ₹<?php echo number_format($applicationData['exam_fee'], 2); ?> →
                        </button>
                        <?php else: ?>
                        <div class="alert alert-warning flex-fill mb-0">
                            <strong>⚠️ Payment Gateway:</strong> Not configured. Please install Razorpay SDK.
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Information Alert -->
                    <div class="alert alert-info mt-4 mb-0">
                        <small>
                            <?php if (TEST_MODE): ?>
                            <strong>📌 TEST MODE:</strong> Payment ₹1 for testing. Switch to production mode in code.<br>
                            <?php endif; ?>
                            <strong>🔐 SECURITY:</strong> Receipt uses secure token-based access.
                        </small>
                    </div>
                </div>
            </div>
            
            <?php if ($razorpay_available && !empty($applicationData['razorpay_order_id'])): ?>
            <script>
            var rzpOptions = {
                "key": "<?php echo $razor_key_id; ?>",
                "amount": "<?php echo (int) round((float) $applicationData['exam_fee'] * 100); ?>",
                "currency": "INR",
                "name": <?php echo json_encode($landingTitle, JSON_UNESCAPED_UNICODE); ?>,
                "description": <?php echo json_encode(($landingSubtitle !== '' ? $landingSubtitle . ' · ' : '') . 'Ticket ' . ($applicationData['class_label'] ?? $applicationData['class']), JSON_UNESCAPED_UNICODE); ?>,
                "order_id": "<?php echo $applicationData['razorpay_order_id']; ?>",
                "handler": function (response) {
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '';
                    
                    var fields = {
                        'razorpay_payment_id': response.razorpay_payment_id,
                        'razorpay_order_id': response.razorpay_order_id,
                        'razorpay_signature': response.razorpay_signature,
                        'application_id': '<?php echo $applicationData['application_id']; ?>'
                    };
                    
                    for (var key in fields) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = fields[key];
                        form.appendChild(input);
                    }
                    
                    document.body.appendChild(form);
                    form.submit();
                },
                "prefill": {
                    "name": "<?php echo htmlspecialchars($applicationData['full_name']); ?>",
                    "email": "",
                    "contact": "<?php echo htmlspecialchars($applicationData['mobile']); ?>"
                },
                "notes": {
                    "application_id": "<?php echo $applicationData['application_id']; ?>",
                    "class": "<?php echo $applicationData['class']; ?>"
                },
                "theme": {
                    "color": "#0058F0"
                },
                "modal": {
                    "ondismiss": function() {
                        alert('Payment cancelled. You can retry payment anytime.');
                    }
                }
            };
            
            var rzp1 = new Razorpay(rzpOptions);
            
            rzp1.on('payment.failed', function (response) {
                alert('Payment Failed: ' + response.error.description);
            });
            
            document.getElementById('rzp-button').onclick = function(e) {
                e.preventDefault();
                var payBox = document.getElementById('declaration_accept_pay');
                if (payBox && !payBox.checked) {
                    alert('Please tick the declaration checkbox before proceeding to payment.');
                    payBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    payBox.focus();
                    return;
                }
                rzp1.open();
            };
            </script>
            <?php endif; ?>
            
            <?php else: ?>
            <!-- =============================================== -->
            <!-- APPLICATION FORM -->
            <!-- =============================================== -->
            <div class="card shadow">
                <div class="card-body p-4">
                    
                    <!-- INSTRUCTIONS BOX -->
                    
                    <?php if (TEST_MODE): ?>
                    <div class="alert alert-warning">
                        <strong>⚠️ TEST MODE ACTIVE</strong><br>
                        All payments set to ₹1 for testing. Receipt access secured with tokens. Email notifications enabled.
                    </div>
                    <?php endif; ?>
                    
                    <form method="post" id="applicationForm" enctype="multipart/form-data" novalidate>
                        <?php if ($lockedStaff): ?>
                        <input type="hidden" name="staff_id" value="<?php echo (int) $lockedStaff['id']; ?>">
                        <?php endif; ?>

                        <div class="section-title">👤 Guest details</div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($applicationData['first_name'] ?? ''); ?>" required minlength="2" maxlength="50" placeholder="Enter first name">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control" value="<?php echo htmlspecialchars($applicationData['middle_name'] ?? ''); ?>" maxlength="50" placeholder="Enter middle name (optional)">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($applicationData['last_name'] ?? ''); ?>" required minlength="2" maxlength="50" placeholder="Enter last name">
                            </div>
                        </div>

                        <div class="mb-3">
                            <?php college_field((int) ($applicationData['college_id'] ?? 0), (string) ($applicationData['college_other'] ?? ''), (string) ($applicationData['school_name'] ?? '')); ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Ticket type <span class="text-danger">*</span></label>
                            <select name="class" id="classSelect" class="form-select" required>
                                <option value="">-- Select ticket type --</option>
                                <?php foreach (form_classes_for('academia') as $ck => $cl): ?>
                                <option value="<?php echo htmlspecialchars($ck); ?>" <?php echo (($applicationData['class'] ?? '') === $ck) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cl); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (function_exists('icard_photo_on_form') && icard_photo_on_form()): ?>
                        <div class="mb-3">
                            <label class="form-label">Photo for Digital I-Card <span class="text-danger">*</span></label>
                            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp" required>
                            <small class="text-muted">Clear face photo, JPG/PNG, max 3 MB. You can also add or change it later on My profile.</small>
                        </div>
                        <?php endif; ?>

                        <hr class="my-4">

                        <?php if (coupons_on_form()): ?>
                        <div class="section-title">Coupon code</div>
                        <div class="mb-3">
                            <label class="form-label">Coupon Code (optional)</label>
                            <div class="input-group">
                                <input type="text" name="coupon_code" class="form-control" maxlength="24" placeholder="Enter code" value="<?php echo htmlspecialchars(coupon_normalize((string) ($applicationData['coupon_code'] ?? ''))); ?>" style="text-transform:uppercase;">
                                <button class="btn btn-outline-primary" type="submit" name="apply_coupon" value="1" formnovalidate>Apply</button>
                            </div>
                            <?php if ($couponNotice): ?>
                            <small class="text-success d-block mt-1"><?php echo htmlspecialchars($couponNotice); ?></small>
                            <?php elseif ($couponError): ?>
                            <small class="text-danger d-block mt-1"><?php echo htmlspecialchars($couponError); ?></small>
                            <?php else: ?>
                            <small class="text-muted">If you have a code, enter it and tap Apply to see the new fee.</small>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="section-title">💰 Fee<?php echo TEST_MODE ? ' (TEST MODE)' : ''; ?></div>
                        <div class="alert alert-light border">
                            <?php if (!empty($feeBreak['coupon_code'])): ?>
                            Coupon <strong><?php echo htmlspecialchars((string) $feeBreak['coupon_code']); ?></strong> applied.<br>
                            <?php endif; ?>
                            <?php echo form_fee_summary_html($feeBreak); ?>
                        </div>
                        
                        <div class="section-title">📋 Declaration</div>
                        <div class="declaration-box">
                            <div class="form-check declaration-check">
                                <input class="form-check-input" type="checkbox" name="declaration_accept" id="declaration_accept" value="1" <?php echo !empty($applicationData['declaration_accept']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="declaration_accept">
                                    <span class="text-danger">*</span>
                                    <?php echo htmlspecialchars(legal_checkbox_text()); ?>
                                </label>
                            </div>
                            <div class="mt-3" style="max-height:280px;overflow:auto;font-size:0.85rem;border:1px solid #e2e8f0;border-radius:8px;padding:12px;">
                                <?php require __DIR__ . '/includes/legal_terms_html.php'; ?>
                            </div>
                        </div>

                        <?php
                        $askStaff = function_exists('staff_ask_on_form') && staff_ask_on_form();
                        $postedRef = (string) ($applicationData['staff_referred'] ?? '');
                        $postedStaffId = (int) ($applicationData['staff_id'] ?? 0);
                        if ($lockedStaff) {
                            $postedRef = 'yes';
                            $postedStaffId = (int) $lockedStaff['id'];
                        }
                        ?>
                        <?php if ($askStaff || $lockedStaff): ?>
                        <div class="section-title mt-4">👤 Staff</div>
                        <div class="declaration-box">
                            <label class="form-label">Did someone give this form to you? <span class="text-danger">*</span></label>
                            <?php if ($lockedStaff): ?>
                            <p class="mb-2">Yes — <?php echo htmlspecialchars((string) $lockedStaff['name']); ?></p>
                            <input type="hidden" name="staff_referred" value="yes">
                            <?php else: ?>
                            <div class="mb-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="staff_referred" id="staffRefYes" value="yes" <?php echo $postedRef === 'yes' ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="staffRefYes">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="staff_referred" id="staffRefNo" value="no" <?php echo $postedRef === 'no' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="staffRefNo">No</label>
                                </div>
                            </div>
                            <div id="staffNameWrap" style="<?php echo $postedRef === 'yes' ? '' : 'display:none;'; ?>">
                                <label class="form-label">Staff name <span class="text-danger">*</span></label>
                                <?php if (!$staffOptions): ?>
                                <p class="text-muted small mb-0">No staff names are available yet.</p>
                                <?php else: ?>
                                <?php foreach ($staffOptions as $st): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="staff_id" id="staffOpt<?php echo (int) $st['id']; ?>" value="<?php echo (int) $st['id']; ?>" <?php echo $postedStaffId === (int) $st['id'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="staffOpt<?php echo (int) $st['id']; ?>"><?php echo htmlspecialchars((string) $st['name']); ?></label>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" name="submit" class="btn btn-primary btn-lg">
                                Submit Application<?php echo TEST_MODE ? ' (Test Mode)' : ''; ?> →
                            </button>
                        </div>
                        
                    </form>
                    
                </div>
            </div>
            <?php endif; ?>
            
        </div>
    </div>
</div>

<!-- Footer -->
<div class="footer text-center">
    <div class="container">
        <?php foreach (public_contact_lines() as $line): ?>
        <p class="mb-1 fw-semibold"><?php echo htmlspecialchars($line); ?></p>
        <?php endforeach; ?>
        <?php if (TEST_MODE): ?>
        <p class="mt-2 mb-0"><small>⚠️ TEST MODE ACTIVE | 🔐 Secure Token System | 📧 Email Notifications</small></p>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript -->
<script>
function syncStaffReferralUi() {
    var wrap = document.getElementById('staffNameWrap');
    var yes = document.getElementById('staffRefYes');
    if (!wrap) {
        return;
    }
    var show = !!(yes && yes.checked);
    wrap.style.display = show ? '' : 'none';
    wrap.querySelectorAll('input[name="staff_id"]').forEach(function(el) {
        el.disabled = !show;
    });
}
document.querySelectorAll('input[name="staff_referred"]').forEach(function(el) {
    el.addEventListener('change', syncStaffReferralUi);
});
syncStaffReferralUi();

var applicationForm = document.getElementById('applicationForm');
if (applicationForm) {
    applicationForm.addEventListener('submit', function(e) {
        var isValid = true;
        var errorMessage = '';
        var declaration = document.getElementById('declaration_accept');
        if (declaration && !declaration.checked) {
            isValid = false;
            errorMessage += '• Please tick the declaration checkbox after reading the rules\n';
        }
        var staffYes = document.getElementById('staffRefYes');
        var staffNo = document.getElementById('staffRefNo');
        if (staffYes && staffNo) {
            if (!staffYes.checked && !staffNo.checked) {
                isValid = false;
                errorMessage += '• Please choose Yes or No: did someone give you this form?\n';
            } else if (staffYes.checked) {
                var staffPicked = document.querySelector('input[name="staff_id"]:checked');
                if (!staffPicked) {
                    isValid = false;
                    errorMessage += '• Please select the staff member who gave you this form\n';
                }
            }
        }
        var classSelect = document.getElementById('classSelect');
        if (classSelect && !classSelect.value) {
            isValid = false;
            errorMessage += '• Please select a role / designation\n';
        }
        if (!isValid) {
            e.preventDefault();
            alert('Please fix the following errors:\n\n' + errorMessage);
            if (declaration && !declaration.checked) {
                declaration.scrollIntoView({ behavior: 'smooth', block: 'center' });
                declaration.focus();
            }
            return false;
        }
    });
}
</script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php if (function_exists('help_whatsapp_button')) { echo help_whatsapp_button(true); } ?>
</body>
</html>