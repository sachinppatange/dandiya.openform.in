<?php
/**
 * Registration form options: academia/industry roles, workshop dates, meal preference, fees.
 */

if (!function_exists('coupon_normalize')) {
    require_once __DIR__ . '/coupons.php';
}

function ensure_form_catalog_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    try {
        $pdo = db();
        foreach (['parent_name', 'parent_address', 'alt_mobile', 'email', 'tshirt_size', 'event_date'] as $dropCol) {
            db_drop_column($pdo, 'scholarship_applications', $dropCol);
        }
        try {
            $pdo->exec('DROP TABLE IF EXISTS `tshirt_sizes`');
        } catch (PDOException $e) {
            // ignore
        }
        db_ensure_column($pdo, 'scholarship_applications', 'institution_type', "varchar(20) DEFAULT 'academia' COMMENT 'academia'");
        db_ensure_column($pdo, 'scholarship_applications', 'fee_base', "decimal(10,2) DEFAULT NULL COMMENT 'Registration fee before gateway'");
        db_ensure_column($pdo, 'scholarship_applications', 'fee_platform', "decimal(10,2) DEFAULT NULL COMMENT 'Payment gateway / platform fee'");
        db_ensure_column($pdo, 'scholarship_applications', 'photo', "varchar(255) DEFAULT NULL COMMENT 'I-Card photo relative path'");
        if (!function_exists('ensure_coupons_schema')) {
            require_once __DIR__ . '/coupons.php';
        }
        ensure_coupons_schema();
        try {
            $pdo->exec("ALTER TABLE `scholarship_applications` MODIFY `class` varchar(40) NOT NULL");
        } catch (PDOException $e) {
            // ignore
        }
        $done = true;
    } catch (Throwable $e) {
        error_log('form_catalog schema: ' . $e->getMessage());
    }
}

function form_class_options(): array
{
    return [
        'academia' => [
            'm_pharm' => 'M.Pharm Students',
            'b_pharm' => 'B.Pharm Students',
            'faculty' => 'Faculty Members',
            'academic_researcher' => 'Academic Researchers',
            'research_scholar' => 'Research Scholars',
            'other' => 'Other',
        ],
    ];
}

function form_all_class_labels(): array
{
    $out = [];
    foreach (form_class_options() as $map) {
        foreach ($map as $k => $v) {
            $out[(string) $k] = $v;
        }
    }
    return $out;
}

function form_class_label($class): string
{
    $class = (string) $class;
    $all = form_all_class_labels();
    if (isset($all[$class])) {
        return $all[$class];
    }
    $legacy = [
        'student_ug' => 'UG student',
        'student_pg' => 'PG student',
        'chemist' => 'Chemist / analyst',
        'qc_qa' => 'QC / QA',
        'rnd' => 'R&D',
        'lab_mgr' => 'Lab manager',
        'faculty' => 'Faculty Members',
        'other' => 'Other',
    ];
    if (isset($legacy[$class])) {
        return $legacy[$class];
    }
    if (ctype_digit($class)) {
        $n = (int) $class;
        if ($n === 1) {
            return '1st';
        }
        if ($n === 2) {
            return '2nd';
        }
        if ($n === 3) {
            return '3rd';
        }
        return $n . 'th';
    }
    return $class;
}

function form_event_dates(): array
{
    $d = [form_workshop_start_date()];
    return [
        'academia' => $d,
    ];
}

function form_workshop_start_date(): string
{
    return '2026-09-28';
}

function form_workshop_period_label(): string
{
    return '28 September – 3 October 2026';
}

function form_event_date_label(?string $ymd): string
{
    if (!$ymd) {
        return '';
    }
    $ts = strtotime($ymd);
    return $ts ? date('j F Y', $ts) : $ymd;
}

function form_classes_for(string $type): array
{
    $opts = form_class_options();
    return $opts[$type] ?? [];
}

function form_dates_for(string $type): array
{
    $opts = form_event_dates();
    return $opts[$type] ?? [];
}

function form_valid_class(string $type, string $class): bool
{
    return array_key_exists($class, form_classes_for($type));
}

function form_valid_event_date(string $type, string $date): bool
{
    return in_array($date, form_dates_for($type), true);
}

function get_tshirt_sizes(bool $activeOnly = true): array
{
    try {
        ensure_form_catalog_schema();
        $sql = "SELECT * FROM tshirt_sizes";
        if ($activeOnly) {
            $sql .= " WHERE status = 'active'";
        }
        $sql .= " ORDER BY sort_order ASC, id ASC";
        return db()->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function add_tshirt_size(string $label): array
{
    $label = strtoupper(trim($label));
    if ($label === '' || strlen($label) > 20) {
        return ['ok' => false, 'error' => 'Enter a T-shirt size (max 20 characters).'];
    }
    try {
        ensure_form_catalog_schema();
        $max = (int) db()->query("SELECT COALESCE(MAX(sort_order), 0) FROM tshirt_sizes")->fetchColumn();
        $stmt = db()->prepare("INSERT INTO tshirt_sizes (label, sort_order, status) VALUES (?, ?, 'active')");
        $stmt->execute([$label, $max + 10]);
        return ['ok' => true, 'error' => ''];
    } catch (PDOException $e) {
        return ['ok' => false, 'error' => 'This size already exists.'];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Could not add T-shirt size.'];
    }
}

function delete_tshirt_size(int $id): array
{
    if ($id < 1) {
        return ['ok' => false, 'error' => 'Invalid size.'];
    }
    try {
        $stmt = db()->prepare("DELETE FROM tshirt_sizes WHERE id = ?");
        $stmt->execute([$id]);
        return ['ok' => true, 'error' => ''];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Could not delete size.'];
    }
}

function set_tshirt_size_status(int $id, string $status): bool
{
    $status = $status === 'inactive' ? 'inactive' : 'active';
    try {
        $stmt = db()->prepare("UPDATE tshirt_sizes SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function form_fee_breakdown(?array $coupon = null): array
{
    $s = function_exists('get_app_settings') ? get_app_settings() : [];
    $base = max(1, (int) ($s['exam_fee_flat'] ?? 5000));
    if ($coupon && isset($coupon['fee_amount'])) {
        $base = max(1, (int) round((float) $coupon['fee_amount']));
    }
    $pct = max(0, min(30, (float) ($s['platform_fee_percent'] ?? 4)));
    $payer = (($s['platform_fee_payer'] ?? 'user') === 'admin') ? 'admin' : 'user';
    $test = (($s['payment_test_mode'] ?? '0') === '1');
    if ($test) {
        $base = 1;
    }
    $platform = round($base * $pct / 100, 2);
    $total = $payer === 'admin' ? (float) $base : round($base + $platform, 2);
    return [
        'base' => $base,
        'percent' => $pct,
        'platform' => $platform,
        'total' => $total,
        'payer' => $payer,
        'test_mode' => $test,
        'paise' => (int) round($total * 100),
        'coupon_code' => (string) ($coupon['code'] ?? ''),
        'coupon_id' => (int) ($coupon['id'] ?? 0),
    ];
}

function form_fee_percent_label(array $break): string
{
    return rtrim(rtrim(number_format((float) ($break['percent'] ?? 0), 2), '0'), '.');
}

function form_fee_summary_html(array $break, bool $forParent = true): string
{
    $pct = form_fee_percent_label($break);
    $base = number_format((float) ($break['base'] ?? 0), 2);
    $plat = number_format((float) ($break['platform'] ?? 0), 2);
    $total = number_format((float) ($break['total'] ?? 0), 2);
    $payer = ($break['payer'] ?? 'user') === 'admin' ? 'admin' : 'user';
    $html = 'Registration fee: ₹' . $base . '<br>';
    if ($payer === 'admin') {
        $html .= 'Payment gateway &amp; platform fee (' . htmlspecialchars($pct) . '%): ₹' . $plat . ' — organiser pays this<br>';
        $html .= '<strong>' . ($forParent ? 'You pay' : 'Amount charged') . ': ₹' . $total . '</strong>';
    } else {
        $html .= 'Payment gateway &amp; platform fee (' . htmlspecialchars($pct) . '%): ₹' . $plat . '<br>';
        $html .= '<strong>Total payable: ₹' . $total . '</strong>';
    }
    return $html;
}

function form_fee_absorbed_by_admin(?array $app): bool
{
    if (!$app) {
        return false;
    }
    $base = (float) ($app['fee_base'] ?? 0);
    $plat = (float) ($app['fee_platform'] ?? 0);
    $total = (float) ($app['exam_fee'] ?? 0);
    return $plat > 0.009 && abs($total - $base) < 0.05;
}

function form_institution_label(string $type): string
{
    return 'Academia';
}
