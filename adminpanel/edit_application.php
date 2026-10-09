<?php
/**
 * Edit Application - Admin Panel
 * Allows admin to edit scholarship application details
 */

session_start();
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/wa_config.php';
if (!function_exists('form_class_label')) {
    require_once __DIR__ . '/../includes/form_catalog.php';
    ensure_form_catalog_schema();
}
if (!function_exists('college_apply_post')) {
    require_once __DIR__ . '/../includes/colleges.php';
}

// ========== AUTHENTICATION ==========
if (empty($_SESSION['admin_auth_user'])) {
    header('Location: admin_login.php');
    exit;
}

$admin_phone = $_SESSION['admin_auth_user'];

// ========== GET APPLICATION ID ==========
$app_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$app_id) {
    die('Invalid Application ID');
}

// ========== CONNECT DATABASE ==========
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASSWORD, 
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Throwable $e) {
    die('Database connection error: ' . $e->getMessage());
}

// ========== HANDLE FORM SUBMISSION ==========
$msg_success = '';
$msg_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_application'])) {
    
    // Collect form data
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $institution_type = 'academia';
    $class = trim($_POST['class'] ?? '');
    $errors = [];
    $school_name = '';
    $college_id = null;
    ensure_colleges_schema();
    $currentCollegeId = 0;
    try {
        $cur = $pdo->prepare('SELECT college_id FROM scholarship_applications WHERE id = ?');
        $cur->execute([$app_id]);
        $currentCollegeId = (int) ($cur->fetchColumn() ?: 0);
    } catch (Throwable $e) {
        $currentCollegeId = 0;
    }
    $collegePick = college_apply_post($_POST, $currentCollegeId);
    if (!$collegePick['ok']) {
        $errors[] = $collegePick['error'];
    } else {
        $school_name = (string) $collegePick['school_name'];
        $college_id = $collegePick['college_id'];
    }
    $mobile = trim($_POST['mobile'] ?? '');
    $payment_status = trim($_POST['payment_status'] ?? '');
    
    if (empty($first_name)) $errors[] = "First Name is required";
    if (empty($last_name)) $errors[] = "Last Name is required";
    if (empty($class)) $errors[] = "Ticket type is required";
    if (!preg_match('/^\d{10}$/', $mobile)) $errors[] = "Valid 10-digit Mobile Number is required";
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE scholarship_applications 
                SET 
                    first_name = ?,
                    middle_name = ?,
                    last_name = ?,
                    class = ?,
                    school_name = ?,
                    college_id = ?,
                    mobile = ?,
                    institution_type = ?,
                    payment_status = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $first_name,
                $middle_name,
                $last_name,
                $class,
                $school_name,
                $college_id,
                $mobile,
                $institution_type,
                $payment_status,
                $app_id
            ]);
            
            if ($result) {
                $msg_success = 'Application updated successfully!';
            } else {
                $msg_error = 'Failed to update application.';
            }
            
        } catch (Exception $e) {
            $msg_error = 'Database error: ' . $e->getMessage();
        }
    } else {
        $msg_error = implode('<br>', $errors);
    }
}

// ========== FETCH APPLICATION DATA ==========
try {
    $stmt = $pdo->prepare("SELECT * FROM scholarship_applications WHERE id = ?");
    $stmt->execute([$app_id]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$app) {
        die('Application not found');
    }
    
} catch (Throwable $e) {
    die('Database error: ' . $e->getMessage());
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Application #<?php echo $app_id; ?> - AGNIPANKH</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <style>
        :root {
            --primary: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg: #f8fafc;
            --card: #fff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: var(--muted);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .back-btn:hover {
            background: #475569;
        }
        
        .card {
            background: var(--card);
            border-radius: 14px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(2,8,23,.08);
            margin-bottom: 20px;
        }
        
        .header {
            border-bottom: 3px solid var(--warning);
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .header h1 {
            font-size: 28px;
            color: var(--warning);
            margin-bottom: 5px;
        }
        
        .header .subtitle {
            color: var(--muted);
            font-size: 14px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--success);
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--border);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text);
            font-size: 14px;
        }
        
        label .required {
            color: var(--danger);
        }
        
        input[type="text"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .radio-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .radio-label input[type="radio"] {
            width: auto;
            cursor: pointer;
        }
        
        .btn-container {
            display: flex;
            gap: 12px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #1d4ed8;
        }
        
        .btn-success {
            background: var(--success);
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-secondary {
            background: var(--muted);
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-secondary:hover {
            background: #475569;
        }
        
        .helper-text {
            font-size: 12px;
            color: var(--muted);
            margin-top: 4px;
        }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .card { padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .btn-container { flex-direction: column; }
            .btn { width: 100%; }
        }
    </style>
</head>
<body>
<div class="container">
    <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
    
    <div class="card">
        <div class="header">
            <h1>✏️ Edit Application #<?php echo h($app['id']); ?></h1>
            <div class="subtitle">Last updated: <?php echo date('d F Y, h:i A', strtotime($app['updated_at'])); ?></div>
        </div>
        
        <?php if ($msg_success): ?>
            <div class="alert alert-success">
                ✅ <?php echo h($msg_success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($msg_error): ?>
            <div class="alert alert-error">
                ❌ <?php echo $msg_error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="" autocomplete="off">
            
            <!-- Student Information -->
            <div class="section">
                <div class="section-title">Participant</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name <span class="required">*</span></label>
                        <input type="text" name="first_name" value="<?php echo h($app['first_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" value="<?php echo h($app['middle_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Last Name <span class="required">*</span></label>
                        <input type="text" name="last_name" value="<?php echo h($app['last_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Ticket type <span class="required">*</span></label>
                        <select name="class" required>
                            <option value="">Select role</option>
                            <?php
                            $roleLabels = form_all_class_labels();
                            $curClass = (string) ($app['class'] ?? '');
                            if ($curClass !== '' && !isset($roleLabels[$curClass])) {
                                $roleLabels[$curClass] = form_class_label($curClass);
                            }
                            foreach ($roleLabels as $ck => $cl):
                            ?>
                                <option value="<?php echo h($ck); ?>" <?php echo $curClass === (string) $ck ? 'selected' : ''; ?>><?php echo h($cl); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Organisation</div>
                <div class="form-grid">
                    <div class="form-group full-width">
                        <?php college_field((int) ($app['college_id'] ?? 0), (string) ($app['college_other'] ?? ''), (string) ($app['school_name'] ?? '')); ?>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <div class="section-title">Contact</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Mobile Number <span class="required">*</span></label>
                        <input type="text" name="mobile" value="<?php echo h($app['mobile']); ?>" 
                               maxlength="10" pattern="\d{10}" required>
                        <div class="helper-text">10-digit mobile number</div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Information -->
            <div class="section">
                <div class="section-title">💰 Payment Information</div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Payment Status <span class="required">*</span></label>
                        <select name="payment_status" required>
                            <option value="pending" <?php echo ($app['payment_status'] === 'pending') ? 'selected' : ''; ?>>
                                Pending
                            </option>
                            <option value="paid" <?php echo ($app['payment_status'] === 'paid') ? 'selected' : ''; ?>>
                                Paid
                            </option>
                            <option value="failed" <?php echo ($app['payment_status'] === 'failed') ? 'selected' : ''; ?>>
                                Failed
                            </option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Registration fee</label>
                        <input type="text" value="₹<?php echo number_format($app['exam_fee'], 2); ?>" disabled>
                        <div class="helper-text">Fee cannot be edited</div>
                    </div>
                    
                    <?php if ($app['razorpay_order_id']): ?>
                    <div class="form-group">
                        <label>Razorpay Order ID</label>
                        <input type="text" value="<?php echo h($app['razorpay_order_id']); ?>" disabled>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($app['razorpay_payment_id']): ?>
                    <div class="form-group">
                        <label>Razorpay Payment ID</label>
                        <input type="text" value="<?php echo h($app['razorpay_payment_id']); ?>" disabled>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Submit Buttons -->
            <div class="btn-container">
                <button type="submit" name="update_application" class="btn btn-success">
                    💾 Save Changes
                </button>
                <a href="view_application.php?id=<?php echo $app['id']; ?>" class="btn btn-primary">
                    👁️ View Details
                </a>
                <a href="admin_dashboard.php" class="btn btn-secondary">
                    ← Back to Dashboard
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-format Aadhar and Mobile inputs
document.addEventListener('DOMContentLoaded', function() {
    // Aadhar: only digits
    const aadharInput = document.querySelector('input[name="aadhar"]');
    if (aadharInput) {
        aadharInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0, 12);
        });
    }
    
    // Mobile: only digits
    const mobileInputs = document.querySelectorAll('input[name="mobile"]');
    mobileInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '').slice(0, 10);
        });
    });
});
</script>
</body>
</html>