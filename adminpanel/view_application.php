<?php
/**
 * View Application Details - Admin Panel
 * Shows complete information of a scholarship application
 */

session_start();
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/wa_config.php';
if (!function_exists('form_class_label')) {
    require_once __DIR__ . '/../includes/form_catalog.php';
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

// ========== FETCH APPLICATION ==========
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASSWORD, 
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
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

$full_name = trim($app['first_name'] . ' ' . $app['middle_name'] . ' ' . $app['last_name']);

$status_class = 'status-pending';
$status_text = 'PENDING';
if ($app['payment_status'] === 'paid') {
    $status_class = 'status-paid';
    $status_text = 'PAID';
} elseif ($app['payment_status'] === 'failed') {
    $status_class = 'status-failed';
    $status_text = 'FAILED';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Application #<?php echo $app_id; ?> - AGNIPANKH</title>
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
            max-width: 900px;
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
            border-bottom: 3px solid var(--primary);
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .header h1 {
            font-size: 28px;
            color: var(--primary);
            margin-bottom: 5px;
        }
        
        .header .subtitle {
            color: var(--muted);
            font-size: 14px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .status-paid {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-failed {
            background: #fee2e2;
            color: #991b1b;
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
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 15px;
        }
        
        .info-item {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }
        
        .info-label {
            font-size: 13px;
            color: var(--muted);
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 15px;
            color: var(--text);
            font-weight: 500;
        }
        
        .action-btns {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            display: inline-block;
        }
        
        .btn-edit {
            background: var(--warning);
            color: white;
        }
        
        .btn-edit:hover {
            background: #d97706;
        }
        
        .btn-receipt {
            background: var(--success);
            color: white;
        }
        
        .btn-receipt:hover {
            background: #059669;
        }
        
        .btn-back {
            background: var(--muted);
            color: white;
        }
        
        .btn-back:hover {
            background: #475569;
        }
        
        @media print {
            .back-btn, .action-btns { display: none; }
        }
        
        @media (max-width: 768px) {
            .container { padding: 10px; }
            .card { padding: 20px; }
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="container">
    <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>
    
    <div class="card">
        <div class="header">
            <h1>Application Details #<?php echo h($app['id']); ?></h1>
            <div class="subtitle">Registered on: <?php echo date('d F Y, h:i A', strtotime($app['created_at'])); ?></div>
            <span class="status-badge <?php echo $status_class; ?>">
                <?php echo $status_text; ?>
            </span>
        </div>
        
        <!-- Student Information -->
        <div class="section">
            <div class="section-title">Participant</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">First Name</div>
                    <div class="info-value"><?php echo h($app['first_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Middle Name</div>
                    <div class="info-value"><?php echo h($app['middle_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Last Name</div>
                    <div class="info-value"><?php echo h($app['last_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><strong><?php echo h($full_name); ?></strong></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Role</div>
                    <div class="info-value"><?php echo h(form_class_label($app['class'] ?? '')); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Organisation Name</div>
                    <div class="info-value"><?php echo h($app['school_name']); ?></div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Contact</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Mobile Number</div>
                    <div class="info-value"><strong><?php echo h($app['mobile']); ?></strong></div>
                </div>
            </div>
        </div>
        
        <!-- Payment Information -->
        <div class="section">
            <div class="section-title">💰 Payment Information</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Registration fee</div>
                    <div class="info-value"><strong>₹<?php echo number_format($app['exam_fee'], 2); ?></strong></div>
                </div>
                <?php if (!empty($app['coupon_code'])): ?>
                <div class="info-item">
                    <div class="info-label">Coupon</div>
                    <div class="info-value"><strong><?php echo h($app['coupon_code']); ?></strong></div>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <div class="info-label">Payment Status</div>
                    <div class="info-value">
                        <span class="status-badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                    </div>
                </div>
                <?php if ($app['razorpay_order_id']): ?>
                <div class="info-item">
                    <div class="info-label">Razorpay Order ID</div>
                    <div class="info-value"><?php echo h($app['razorpay_order_id']); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($app['razorpay_payment_id']): ?>
                <div class="info-item">
                    <div class="info-label">Razorpay Payment ID</div>
                    <div class="info-value"><?php echo h($app['razorpay_payment_id']); ?></div>
                </div>
                <?php endif; ?>
                <div class="info-item">
                    <div class="info-label">Created At</div>
                    <div class="info-value"><?php echo date('d F Y, h:i A', strtotime($app['created_at'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Last Updated</div>
                    <div class="info-value"><?php echo date('d F Y, h:i A', strtotime($app['updated_at'])); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-btns">
            <a href="edit_application.php?id=<?php echo $app['id']; ?>" class="btn btn-edit">
                ✏️ Edit Application
            </a>
            <?php if ($app['payment_status'] === 'paid' && !empty($app['receipt_token'])): ?>
                <a href="../payment_success.php?token=<?php echo urlencode($app['receipt_token']); ?>" 
                   class="btn btn-receipt" target="_blank">
                    📄 View Receipt
                </a>
            <?php endif; ?>
            <a href="admin_dashboard.php" class="btn btn-back">
                ← Back to Dashboard
            </a>
        </div>
    </div>
</div>
</body>
</html>