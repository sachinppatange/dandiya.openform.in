<?php
/**
 * Database Configuration File
 * AGNIPANKH Scholarship System
 * Server: agnipankh.in (Live)
 * Timezone: IST (Asia/Kolkata)
 */

// ============================================
// SET INDIAN STANDARD TIME (IST)
// ============================================
date_default_timezone_set('Asia/Kolkata');

require_once __DIR__ . '/config/database.php';

// Global PDO variable
$pdo = null;

try {
    // ============================================
    // PDO CONNECTION OPTIONS
    // ============================================
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => false,
    ];
    
    // ============================================
    // CREATE PDO CONNECTION
    // ============================================
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
    
    // ============================================
    // SET MYSQL TIMEZONE TO IST (+05:30)
    // ============================================
    $pdo->exec("SET time_zone = '+05:30'");
    $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
    
    // ============================================
    // CREATE TABLE IF NOT EXISTS
    // ============================================
    $createTableQuery = "
    CREATE TABLE IF NOT EXISTS `scholarship_applications` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `first_name` varchar(100) NOT NULL,
      `middle_name` varchar(100) DEFAULT NULL,
      `last_name` varchar(100) NOT NULL,
      `class` varchar(10) NOT NULL,
      `division` varchar(50) DEFAULT NULL,
      `board` varchar(50) NOT NULL,
      `medium` varchar(50) NOT NULL,
      `aadhar` varchar(12) NOT NULL,
      `school_name` varchar(255) NOT NULL,
      `mobile` varchar(10) NOT NULL,
      `exam_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
      `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
      `razorpay_order_id` varchar(255) DEFAULT NULL,
      `razorpay_payment_id` varchar(255) DEFAULT NULL,
      `razorpay_signature` varchar(255) DEFAULT NULL,
      `receipt_token` varchar(64) DEFAULT NULL COMMENT 'Secure token for receipt access',
      `payment_date` datetime DEFAULT NULL COMMENT 'Payment completion timestamp (IST)',
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Application created (IST)',
      `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last updated (IST)',
      PRIMARY KEY (`id`),
      UNIQUE KEY `receipt_token` (`receipt_token`),
      KEY `mobile` (`mobile`),
      KEY `payment_status` (`payment_status`),
      KEY `class` (`class`),
      KEY `created_at` (`created_at`),
      KEY `payment_date` (`payment_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
    COMMENT='Scholarship Applications - AGNIPANKH 2026-27';
    ";
    
    $pdo->exec($createTableQuery);
    
    // ============================================
    // ALTER TABLE - ADD MISSING COLUMNS (IF ANY)
    // ============================================
    try {
        // Check and add receipt_token column if not exists
        $pdo->exec("ALTER TABLE `scholarship_applications` 
                    ADD COLUMN IF NOT EXISTS `receipt_token` varchar(64) DEFAULT NULL 
                    COMMENT 'Secure token for receipt access' AFTER `razorpay_signature`");
        
        // Add unique index if not exists
        $pdo->exec("ALTER TABLE `scholarship_applications` 
                    ADD UNIQUE KEY IF NOT EXISTS `receipt_token` (`receipt_token`)");
    } catch (PDOException $e) {
        // Column already exists or other error - ignore
    }
    
    try {
        // Check and add payment_date column if not exists
        $pdo->exec("ALTER TABLE `scholarship_applications` 
                    ADD COLUMN IF NOT EXISTS `payment_date` datetime DEFAULT NULL 
                    COMMENT 'Payment completion timestamp (IST)' AFTER `receipt_token`");
    } catch (PDOException $e) {
        // Column already exists or other error - ignore
    }
    
    try {
        // Update created_at to datetime if it's timestamp
        $pdo->exec("ALTER TABLE `scholarship_applications` 
                    MODIFY COLUMN `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP 
                    COMMENT 'Application created (IST)'");
    } catch (PDOException $e) {
        // Column already correct or other error - ignore
    }
    
    try {
        // Update updated_at to datetime if it's timestamp
        $pdo->exec("ALTER TABLE `scholarship_applications` 
                    MODIFY COLUMN `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP 
                    COMMENT 'Last updated (IST)'");
    } catch (PDOException $e) {
        // Column already correct or other error - ignore
    }
    
    // ============================================
    // CREATE ADMINS TABLE IF NOT EXISTS
    // ============================================
    $createAdminsTable = "
    CREATE TABLE IF NOT EXISTS `admins` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `phone` varchar(20) NOT NULL COMMENT 'Admin phone number for SMS OTP login',
      `name` varchar(100) DEFAULT NULL COMMENT 'Admin name',
      `email` varchar(100) DEFAULT NULL COMMENT 'Admin email',
      `role` enum('super_admin','admin','viewer') DEFAULT 'admin' COMMENT 'Admin role',
      `status` enum('active','inactive') DEFAULT 'active' COMMENT 'Account status',
      `last_login` datetime DEFAULT NULL COMMENT 'Last login timestamp (IST)',
      `login_count` int(11) DEFAULT 0 COMMENT 'Total login count',
      `password_hash` varchar(255) DEFAULT NULL COMMENT 'Password login hash',
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Created at (IST)',
      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated at (IST)',
      PRIMARY KEY (`id`),
      UNIQUE KEY `phone` (`phone`),
      KEY `idx_status` (`status`),
      KEY `idx_role` (`role`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Admin Users - AGNIPANKH System';
    ";
    
    $pdo->exec($createAdminsTable);

    // ============================================
    // CREATE STAFF TABLE (form login accounts)
    // ============================================
    $createStaffTable = "
    CREATE TABLE IF NOT EXISTS `staff` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(100) NOT NULL COMMENT 'Staff display name',
      `phone` varchar(20) NOT NULL COMMENT 'Mobile number used as login username (E.164)',
      `status` enum('active','inactive') DEFAULT 'active' COMMENT 'Account status',
      `last_login` datetime DEFAULT NULL COMMENT 'Last login timestamp (IST)',
      `login_count` int(11) DEFAULT 0 COMMENT 'Total login count',
      `created_by` varchar(20) DEFAULT NULL COMMENT 'Admin phone who created this staff',
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Created at (IST)',
      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated at (IST)',
      PRIMARY KEY (`id`),
      UNIQUE KEY `phone` (`phone`),
      KEY `idx_status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Staff users created by admin; forms can be attributed to them';
    ";
    $pdo->exec($createStaffTable);

    $createStudentsTable = "
    CREATE TABLE IF NOT EXISTS `students` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `phone` varchar(20) NOT NULL COMMENT 'Mobile used as login username (E.164)',
      `name` varchar(150) DEFAULT NULL COMMENT 'Student name from profile/form',
      `email` varchar(150) DEFAULT NULL,
      `last_login` datetime DEFAULT NULL,
      `login_count` int(11) DEFAULT 0,
      `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
      `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `phone` (`phone`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='Students who log in with SMS OTP to fill the form';
    ";
    $pdo->exec($createStudentsTable);
    
    try {
        $pdo->exec("ALTER TABLE `admins` ADD COLUMN IF NOT EXISTS `last_login` datetime DEFAULT NULL");
        $pdo->exec("ALTER TABLE `admins` ADD COLUMN IF NOT EXISTS `login_count` int(11) DEFAULT 0");
    } catch (PDOException $e) {
        // ignore
    }
    
    // ============================================
    // INSERT DEFAULT ADMIN (IF NOT EXISTS)
    // ============================================
    try {
        $adminsToSeed = [
            ['919130831517', 'Sachin Patange', 'admin@agnipankh.in', 'super_admin'],
            ['919096463943', 'Admin', 'admin@agnipankh.in', 'admin'],
        ];
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE phone = ?");
        $ins = $pdo->prepare("INSERT INTO admins (phone, name, email, role, status) VALUES (?, ?, ?, ?, 'active')");
        foreach ($adminsToSeed as $adminRow) {
            $stmt->execute([$adminRow[0]]);
            if ((int) $stmt->fetchColumn() === 0) {
                try {
                    $ins->execute($adminRow);
                } catch (PDOException $e) {
                    $ins->execute([$adminRow[0], $adminRow[1], $adminRow[2], 'admin']);
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Admin insertion: " . $e->getMessage());
    }
    
    // ============================================
    // LOG SUCCESSFUL CONNECTION (OPTIONAL)
    // ============================================
    $connectionTime = date('Y-m-d H:i:s');
    @error_log("[$connectionTime IST] Database connected successfully\n", 3, __DIR__ . '/db_connection.log');
    
} catch (PDOException $e) {
    // ============================================
    // ERROR HANDLING
    // ============================================
    $errorCode = $e->getCode();
    $errorMsg = $e->getMessage();
    $errorTime = date('Y-m-d H:i:s');
    
    // Log error to file (for admin debugging)
    @error_log("[$errorTime IST] DB Error [$errorCode]: $errorMsg\n", 3, __DIR__ . '/error_log.txt');
    
    // User-friendly error messages
    if ($errorCode == 1045 || strpos($errorMsg, 'Access denied') !== false) {
        die("❌ Database Error: Authentication failed. Please contact the administrator.");
    } elseif ($errorCode == 2002 || strpos($errorMsg, "Can't connect") !== false) {
        die("❌ Database Error: Cannot connect to server. Please try again later.");
    } elseif ($errorCode == 1049 || strpos($errorMsg, 'Unknown database') !== false) {
        die("❌ Database Error: Database configuration error. Please contact administrator.");
    } else {
        die("❌ Database Error: System error. Please contact support. (Error logged at $errorTime IST)");
    }
}

// ============================================
// MYSQLI CONNECTION (Backward Compatibility)
// ============================================
$GLOBALS['conn'] = null;
try {
    $GLOBALS['conn'] = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if ($GLOBALS['conn']) {
        mysqli_set_charset($GLOBALS['conn'], 'utf8mb4');
        mysqli_query($GLOBALS['conn'], "SET time_zone = '+05:30'");
    }
} catch (Exception $e) {
    // Ignore mysqli connection errors if PDO works
    error_log("MySQLi connection failed (PDO is primary): " . $e->getMessage());
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function db_table_has_column(PDO $pdo, string $table, string $column): bool {
    if (!isset($GLOBALS['_db_col_cache']) || !is_array($GLOBALS['_db_col_cache'])) {
        $GLOBALS['_db_col_cache'] = [];
    }
    $key = $table . '.' . $column;
    if (array_key_exists($key, $GLOBALS['_db_col_cache'])) {
        return $GLOBALS['_db_col_cache'][$key];
    }
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    $GLOBALS['_db_col_cache'][$key] = (int) $stmt->fetchColumn() > 0;
    return $GLOBALS['_db_col_cache'][$key];
}

function db_ensure_column(PDO $pdo, string $table, string $column, string $definition): void {
    try {
        if (!db_table_has_column($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
            $GLOBALS['_db_col_cache'][$table . '.' . $column] = true;
        }
    } catch (PDOException $e) {
        error_log("Schema ensure {$table}.{$column}: " . $e->getMessage());
    }
}

function db_drop_column(PDO $pdo, string $table, string $column): void {
    try {
        if (db_table_has_column($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE `{$table}` DROP COLUMN `{$column}`");
            unset($GLOBALS['_db_col_cache'][$table . '.' . $column]);
        }
    } catch (PDOException $e) {
        error_log("Schema drop {$table}.{$column}: " . $e->getMessage());
    }
}

function save_scholarship_application(PDO $pdo, array $data): int {
    $core = [
        'first_name' => $data['first_name'] ?? '',
        'middle_name' => $data['middle_name'] ?? '',
        'last_name' => $data['last_name'] ?? '',
        'class' => $data['class'] ?? '',
        'school_name' => $data['school_name'] ?? '',
        'mobile' => $data['mobile'] ?? '',
        'exam_fee' => $data['exam_fee'] ?? 0,
    ];
    $extra = [
        'submitted_by_staff_id' => $data['submitted_by_staff_id'] ?? null,
        'submitted_by_student_id' => $data['submitted_by_student_id'] ?? null,
        'submitted_by_phone' => $data['submitted_by_phone'] ?? '',
        'institution_type' => $data['institution_type'] ?? 'academia',
        'fee_base' => $data['fee_base'] ?? null,
        'fee_platform' => $data['fee_platform'] ?? null,
        'coupon_id' => $data['coupon_id'] ?? null,
        'coupon_code' => $data['coupon_code'] ?? null,
        'division' => $data['division'] ?? '',
        'board' => $data['board'] ?? '',
        'medium' => $data['medium'] ?? '',
        'aadhar' => $data['aadhar'] ?? '',
        'school_address' => $data['school_address'] ?? '',
        'district' => $data['district'] ?? '',
        'city' => $data['city'] ?? '',
    ];

    $includeExtra = true;
    if (function_exists('db_table_has_column')) {
        $includeExtra = db_table_has_column($pdo, 'scholarship_applications', 'submitted_by_staff_id');
    }

    $started = false;
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
        $started = true;
    }

    try {
        try {
            $pdo->query("SELECT id FROM scholarship_applications ORDER BY id DESC LIMIT 1 FOR UPDATE");
        } catch (PDOException $e) {
            // lock not available
        }

        $nextId = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM scholarship_applications")->fetchColumn();
        if ($nextId < 1) {
            $nextId = 1;
        }

        $attempt = 0;
        while ($attempt < 3) {
            $attempt++;
            $cols = ['id'];
            $params = [$nextId];
            foreach ($core as $ck => $cv) {
                if (!function_exists('db_table_has_column') || db_table_has_column($pdo, 'scholarship_applications', $ck)) {
                    $cols[] = $ck;
                    $params[] = $cv;
                }
            }
            if ($includeExtra) {
                foreach ($extra as $ek => $ev) {
                    if (!function_exists('db_table_has_column') || db_table_has_column($pdo, 'scholarship_applications', $ek)) {
                        $cols[] = $ek;
                        $params[] = $ev;
                    }
                }
            }
            $colSql = implode(', ', array_map(static function ($c) {
                return '`' . $c . '`';
            }, $cols));
            $placeholders = implode(', ', array_fill(0, count($params), '?'));
            $sql = "INSERT INTO scholarship_applications ({$colSql}, created_at) VALUES ({$placeholders}, NOW())";
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                if ($started && $pdo->inTransaction()) {
                    $pdo->commit();
                }
                return $nextId;
            } catch (PDOException $e) {
                $msg = $e->getMessage();
                if ($includeExtra && (strpos($msg, 'Unknown column') !== false || strpos($msg, 'submitted_by_') !== false)) {
                    $includeExtra = false;
                    continue;
                }
                if (strpos($msg, 'Duplicate') !== false || (string) $e->getCode() === '23000') {
                    $nextId++;
                    continue;
                }
                throw $e;
            }
        }
        throw new Exception("Failed to save application");
    } catch (Throwable $e) {
        if ($started && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function pdo_last_insert_id(PDO $pdo, ?string $table = null, array $lookup = []): int {
    $id = (int) $pdo->lastInsertId();
    if ($id > 0) {
        return $id;
    }
    try {
        $id = (int) $pdo->query('SELECT LAST_INSERT_ID()')->fetchColumn();
        if ($id > 0) {
            return $id;
        }
    } catch (PDOException $e) {
        // continue to lookup
    }
    if ($table && $lookup) {
        $where = [];
        $params = [];
        foreach ($lookup as $col => $val) {
            $where[] = "`{$col}` = ?";
            $params[] = $val;
        }
        $sql = "SELECT id FROM `{$table}` WHERE " . implode(' AND ', $where) . " ORDER BY id DESC LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $id = (int) $stmt->fetchColumn();
    }
    return $id;
}

/**
 * Get current IST timestamp
 * @return string Current datetime in IST
 */
function getCurrentISTTime() {
    return date('Y-m-d H:i:s');
}

/**
 * Format datetime for display
 * @param string $datetime Database datetime
 * @return string Formatted datetime
 */
function formatDateTime($datetime) {
    if (empty($datetime)) return 'N/A';
    return date('d M Y, h:i A', strtotime($datetime));
}

/**
 * Format date only
 * @param string $datetime Database datetime
 * @return string Formatted date
 */
function formatDate($datetime) {
    if (empty($datetime)) return 'N/A';
    return date('d-m-Y', strtotime($datetime));
}

/**
 * Get database connection status
 * @return array Connection info
 */
function getDBStatus() {
    global $pdo;
    
    if ($pdo instanceof PDO) {
        try {
            $stmt = $pdo->query("SELECT 
                COUNT(*) as total_applications,
                SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                NOW() as server_time
                FROM scholarship_applications
            ");
            $stats = $stmt->fetch();
            
            return [
                'status' => 'connected',
                'timezone' => 'Asia/Kolkata (IST)',
                'server_time' => $stats['server_time'],
                'total_applications' => $stats['total_applications'],
                'paid' => $stats['paid_count'],
                'pending' => $stats['pending_count']
            ];
        } catch (PDOException $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    return [
        'status' => 'disconnected',
        'message' => 'PDO not initialized'
    ];
}

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $pdo->exec("ALTER TABLE `scholarship_applications` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT");
    } catch (PDOException $e) {
        // already auto increment
    }
    db_ensure_column($pdo, 'scholarship_applications', 'submitted_by_staff_id', "int(11) DEFAULT NULL COMMENT 'Staff through whom this form was filled'");
    db_ensure_column($pdo, 'scholarship_applications', 'submitted_by_student_id', "int(11) DEFAULT NULL COMMENT 'Logged-in student who submitted this form'");
    db_ensure_column($pdo, 'scholarship_applications', 'submitted_by_phone', "varchar(20) DEFAULT NULL COMMENT 'Logged-in student mobile at submit time'");
    db_ensure_column($pdo, 'staff', 'referral_code', "varchar(16) DEFAULT NULL COMMENT 'Public student form link code'");
    try {
        $pdo->exec("ALTER TABLE `staff` ADD UNIQUE KEY `referral_code` (`referral_code`)");
    } catch (PDOException $e) {
        // index already exists
    }
    require_once __DIR__ . '/includes/app_settings.php';
    require_once __DIR__ . '/includes/form_catalog.php';
    ensure_app_settings_schema();
    ensure_form_catalog_schema();
}

// ============================================
// OPTIONAL: Test query to verify IST
// ============================================
if (defined('DB_TEST_MODE') && DB_TEST_MODE === true) {
    try {
        $stmt = $pdo->query("SELECT NOW() as current_time, @@session.time_zone as timezone");
        $result = $stmt->fetch();
        echo "<!-- DB Test: Server Time: {$result['current_time']} | Timezone: {$result['timezone']} -->\n";
    } catch (PDOException $e) {
        error_log("DB Test failed: " . $e->getMessage());
    }
}
?>