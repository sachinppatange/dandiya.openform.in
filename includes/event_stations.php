<?php
/**
 * Event-day stations driven from the receipt QR / digital pass.
 */

function ensure_event_stations_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        $pdo = function_exists('db') ? db() : ($GLOBALS['pdo'] ?? null);
        if (!$pdo instanceof PDO) {
            return;
        }
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `event_checkins` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `application_id` int(11) NOT NULL,
              `station` varchar(40) NOT NULL,
              `checked_in_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `checked_in_by` varchar(120) DEFAULT NULL,
              `note` varchar(255) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `app_station` (`application_id`, `station`),
              KEY `station` (`station`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (Throwable $e) {
        error_log('event_stations schema: ' . $e->getMessage());
    }
}

function event_stations(): array
{
    return [
        'icard' => ['label' => 'Digital I-Card', 'hint' => 'Show / verify identity'],
        'qr' => ['label' => 'QR Code', 'hint' => 'Gate / first scan'],
        'kit' => ['label' => 'Kit / materials', 'hint' => 'Course kit given'],
        'breakfast' => ['label' => 'Lunch', 'hint' => 'Meal served'],
        'feedback' => ['label' => 'Feedback', 'hint' => 'Feedback collected'],
        'ecert' => ['label' => 'E-Certificate', 'hint' => 'Certificate issued'],
        'attendance' => ['label' => 'Attendance', 'hint' => 'Present at workshop'],
    ];
}

function event_operator(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!empty($_SESSION['admin_auth_user'])) {
        return [
            'role' => 'admin',
            'name' => (string) ($_SESSION['admin_auth_name'] ?? 'Admin'),
            'phone' => (string) $_SESSION['admin_auth_user'],
        ];
    }
    if (!empty($_SESSION['staff_auth_user'])) {
        return [
            'role' => 'staff',
            'name' => (string) ($_SESSION['staff_auth_name'] ?? 'Staff'),
            'phone' => (string) $_SESSION['staff_auth_user'],
        ];
    }
    return null;
}

function event_checkins_for(int $applicationId): array
{
    ensure_event_stations_schema();
    $pdo = function_exists('db') ? db() : ($GLOBALS['pdo'] ?? null);
    if (!$pdo instanceof PDO) {
        return [];
    }
    $stmt = $pdo->prepare('SELECT station, checked_in_at, checked_in_by FROM event_checkins WHERE application_id = ?');
    $stmt->execute([$applicationId]);
    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $out[(string) $row['station']] = $row;
    }
    return $out;
}

function event_toggle_checkin(int $applicationId, string $station, string $by, bool $mark): bool
{
    ensure_event_stations_schema();
    if (!isset(event_stations()[$station])) {
        return false;
    }
    $pdo = function_exists('db') ? db() : ($GLOBALS['pdo'] ?? null);
    if (!$pdo instanceof PDO) {
        return false;
    }
    if ($mark) {
        $stmt = $pdo->prepare('
            INSERT INTO event_checkins (application_id, station, checked_in_at, checked_in_by)
            VALUES (?, ?, NOW(), ?)
            ON DUPLICATE KEY UPDATE checked_in_at = VALUES(checked_in_at), checked_in_by = VALUES(checked_in_by)
        ');
        return $stmt->execute([$applicationId, $station, $by]);
    }
    $stmt = $pdo->prepare('DELETE FROM event_checkins WHERE application_id = ? AND station = ?');
    return $stmt->execute([$applicationId, $station]);
}

function event_pass_url(string $token): string
{
    if (!function_exists('app_public_base_url')) {
        require_once __DIR__ . '/staff_repository.php';
    }
    return rtrim(app_public_base_url(), '/') . '/pass.php?t=' . rawurlencode($token);
}

function event_application_code(int $id): string
{
    return 'AGS' . str_pad((string) $id, 5, '0', STR_PAD_LEFT);
}

function event_display_value($value): string
{
    $s = trim((string) $value);
    return $s === '' ? '—' : $s;
}

function event_format_datetime($value): string
{
    $s = trim((string) $value);
    if ($s === '' || $s === '0000-00-00 00:00:00') {
        return '—';
    }
    $ts = strtotime($s);
    return $ts ? date('d M Y, h:i A', $ts) : $s;
}

function event_parse_scan_query(string $q): array
{
    $q = trim($q);
    $token = $q;
    if (preg_match('#(?:[?&]t=|/pass\\.php\\?t=)([a-f0-9]{32,64})#i', $q, $m)) {
        $token = $m[1];
    } elseif (preg_match('#^[a-f0-9]{32,64}$#i', $q)) {
        $token = $q;
    }
    $codeId = 0;
    if (preg_match('/^AGS?0*([0-9]+)$/i', $q, $m)) {
        $codeId = (int) $m[1];
    } elseif (ctype_digit($q) && strlen($q) <= 8) {
        $codeId = (int) $q;
    }
    $mobile = preg_replace('/\D+/', '', $q);
    if (strlen($mobile) > 10) {
        $mobile = substr($mobile, -10);
    }
    return [$token, $mobile, $codeId];
}

function event_lookup_application(string $q): ?array
{
    $q = trim($q);
    if ($q === '') {
        return null;
    }
    $pdo = function_exists('db') ? db() : ($GLOBALS['pdo'] ?? null);
    if (!$pdo instanceof PDO) {
        return null;
    }
    [$token, $mobile, $codeId] = event_parse_scan_query($q);
    $stmt = $pdo->prepare('
        SELECT * FROM scholarship_applications
         WHERE receipt_token = ?
            OR (CHAR_LENGTH(?) >= 10 AND mobile = ?)
            OR (? > 0 AND id = ?)
         ORDER BY (payment_status = \'paid\') DESC, id DESC
         LIMIT 1
    ');
    $stmt->execute([$token, $mobile, $mobile, $codeId, $codeId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function event_application_profile(array $app): array
{
    if (!function_exists('form_class_label')) {
        require_once __DIR__ . '/form_catalog.php';
    }
    $id = (int) ($app['id'] ?? 0);
    $type = (string) ($app['institution_type'] ?? '');
    $instLabel = function_exists('form_institution_label') ? form_institution_label($type) : ($type === 'college' ? 'College' : ($type === 'school' ? 'School' : $type));
    $classLabel = function_exists('form_class_label') ? form_class_label((string) ($app['class'] ?? '')) : (string) ($app['class'] ?? '');
    $full = trim(($app['first_name'] ?? '') . ' ' . ($app['middle_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
    $feeBase = $app['fee_base'] ?? null;
    $feePlat = $app['fee_platform'] ?? null;
    $staffName = '';
    if (!empty($app['submitted_by_staff_id']) && function_exists('get_staff_by_id')) {
        $staff = get_staff_by_id((int) $app['submitted_by_staff_id']);
        $staffName = (string) ($staff['name'] ?? '');
    }
    $student = [
        'Application ID' => event_application_code($id) . '  (#' . $id . ')',
        'First name' => event_display_value($app['first_name'] ?? ''),
        'Middle name' => event_display_value($app['middle_name'] ?? ''),
        'Last name' => event_display_value($app['last_name'] ?? ''),
        'Full name' => event_display_value($full),
        'Gender' => event_display_value($app['gender'] ?? ''),
    ];
    $institution = [
        'Organisation Name' => event_display_value($app['school_name'] ?? ''),
        'Role' => event_display_value($classLabel),
    ];
    $contact = [
        'Mobile' => !empty($app['mobile']) ? '+91 ' . $app['mobile'] : '—',
    ];
    $payment = [
        'Payment status' => strtoupper((string) ($app['payment_status'] ?? '')),
        'Registration fee' => $feeBase !== null && $feeBase !== '' ? '₹ ' . number_format((float) $feeBase, 2) : '—',
        'Gateway / platform fee' => $feePlat !== null && $feePlat !== '' ? ('₹ ' . number_format((float) $feePlat, 2) . ((function_exists('form_fee_absorbed_by_admin') && form_fee_absorbed_by_admin($app)) ? ' (organiser)' : '')) : '—',
        'Total paid' => '₹ ' . number_format((float) ($app['exam_fee'] ?? 0), 2),
        'Razorpay order ID' => event_display_value($app['razorpay_order_id'] ?? ''),
        'Razorpay payment ID' => event_display_value($app['razorpay_payment_id'] ?? ''),
        'Registered at' => event_format_datetime($app['created_at'] ?? ''),
        'Payment date' => event_format_datetime($app['payment_date'] ?? ''),
        'Submitted by staff' => event_display_value($staffName !== '' ? $staffName : ($app['submitted_by_phone'] ?? '')),
        'Coupon' => event_display_value($app['coupon_code'] ?? ''),
    ];
    $institution = array_filter($institution, static fn($v, $k) => $v !== '—' || in_array($k, ['Organisation Name', 'Role'], true), ARRAY_FILTER_USE_BOTH);
    return [
        'Participant' => $student,
        'Workshop' => $institution,
        'Contact' => $contact,
        'Payment' => $payment,
    ];
}

function event_full_name(array $app): string
{
    return trim(($app['first_name'] ?? '') . ' ' . ($app['middle_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
}

function event_desk_filters(array $src): array
{
    $station = (string) ($src['station'] ?? 'attendance');
    if (!isset(event_stations()[$station])) {
        $station = 'attendance';
    }
    $view = (string) ($src['view'] ?? 'remaining');
    if (!in_array($view, ['remaining', 'today', 'done'], true)) {
        $view = 'remaining';
    }
    $event = trim((string) ($src['event'] ?? ''));
    if ($event !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $event)) {
        $event = '';
    }
    $inst = trim((string) ($src['inst'] ?? ''));
    if (!in_array($inst, ['academia', 'industry', 'other', 'school', 'college'], true)) {
        $inst = '';
    }
    $q = trim((string) ($src['q'] ?? ''));
    return compact('station', 'view', 'event', 'inst', 'q');
}

function event_paid_filter_sql(array $f): array
{
    $where = ["a.payment_status = 'paid'"];
    $params = [];
    if ($f['inst'] !== '') {
        $where[] = 'a.institution_type = ?';
        $params[] = $f['inst'];
    }
    return [implode(' AND ', $where), $params];
}

function event_desk_stats(array $f): array
{
    ensure_event_stations_schema();
    $pdo = function_exists('db') ? db() : ($GLOBALS['pdo'] ?? null);
    $stations = event_stations();
    $out = [
        'paid' => 0,
        'stations' => [],
    ];
    foreach ($stations as $key => $meta) {
        $out['stations'][$key] = $meta + ['done' => 0, 'today' => 0, 'remaining' => 0];
    }
    if (!$pdo instanceof PDO) {
        return $out;
    }
    [$sql, $params] = event_paid_filter_sql($f);
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM scholarship_applications a WHERE $sql");
    $stmt->execute($params);
    $paid = (int) $stmt->fetchColumn();
    $out['paid'] = $paid;

    $stmt = $pdo->prepare("
        SELECT e.station,
               COUNT(*) done,
               SUM(DATE(e.checked_in_at) = CURDATE()) today_cnt
          FROM event_checkins e
          INNER JOIN scholarship_applications a ON a.id = e.application_id
         WHERE $sql
         GROUP BY e.station
    ");
    $stmt->execute($params);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $key = (string) $row['station'];
        if (!isset($out['stations'][$key])) {
            continue;
        }
        $out['stations'][$key]['done'] = (int) $row['done'];
        $out['stations'][$key]['today'] = (int) $row['today_cnt'];
        $out['stations'][$key]['remaining'] = max(0, $paid - (int) $row['done']);
    }
    foreach ($out['stations'] as $key => $row) {
        if ($row['done'] === 0 && $row['today'] === 0) {
            $out['stations'][$key]['remaining'] = $paid;
        }
    }
    return $out;
}

function event_desk_rows(array $f): array
{
    ensure_event_stations_schema();
    $pdo = function_exists('db') ? db() : ($GLOBALS['pdo'] ?? null);
    if (!$pdo instanceof PDO) {
        return [];
    }
    [$paidSql, $paidParams] = event_paid_filter_sql($f);
    $station = $f['station'];
    $searchSql = '';
    $searchParams = [];
    if ($f['q'] !== '') {
        $searchSql = ' AND (a.first_name LIKE ? OR a.middle_name LIKE ? OR a.last_name LIKE ? OR a.mobile LIKE ? OR a.school_name LIKE ? OR a.id = ?)';
        $like = '%' . $f['q'] . '%';
        $searchParams = [$like, $like, $like, $like, $like, ctype_digit($f['q']) ? (int) $f['q'] : 0];
    }

    if ($f['view'] === 'remaining') {
        $sql = "
            SELECT a.*, NULL AS checked_in_at, NULL AS checked_in_by
              FROM scholarship_applications a
              LEFT JOIN event_checkins e ON e.application_id = a.id AND e.station = ?
             WHERE $paidSql AND e.id IS NULL $searchSql
             ORDER BY a.last_name, a.first_name
             LIMIT 3000
        ";
        $params = array_merge([$station], $paidParams, $searchParams);
    } elseif ($f['view'] === 'today') {
        $sql = "
            SELECT a.*, e.checked_in_at, e.checked_in_by
              FROM event_checkins e
              INNER JOIN scholarship_applications a ON a.id = e.application_id
             WHERE $paidSql AND e.station = ? AND DATE(e.checked_in_at) = CURDATE() $searchSql
             ORDER BY e.checked_in_at DESC
             LIMIT 3000
        ";
        $params = array_merge($paidParams, [$station], $searchParams);
    } else {
        $sql = "
            SELECT a.*, e.checked_in_at, e.checked_in_by
              FROM event_checkins e
              INNER JOIN scholarship_applications a ON a.id = e.application_id
             WHERE $paidSql AND e.station = ? $searchSql
             ORDER BY e.checked_in_at DESC
             LIMIT 3000
        ";
        $params = array_merge($paidParams, [$station], $searchParams);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
