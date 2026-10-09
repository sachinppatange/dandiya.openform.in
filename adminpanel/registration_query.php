<?php
/**
 * Shared filters, WHERE builder, and export columns for registrations.
 */

function registration_class_labels(): array
{
    return function_exists('form_all_class_labels') ? form_all_class_labels() : [];
}

function registration_read_filters(array $src): array
{
    $status = (string) ($src['filter'] ?? $src['status'] ?? 'all');
    if (!in_array($status, ['all', 'paid', 'pending', 'failed'], true)) {
        $status = 'all';
    }
    $class = trim((string) ($src['class'] ?? ''));
    $inst = trim((string) ($src['inst'] ?? ''));
    if (!in_array($inst, ['academia', 'industry', 'other', 'school', 'college'], true)) {
        $inst = '';
    }
    $size = trim((string) ($src['size'] ?? ''));
    $event = trim((string) ($src['event'] ?? ''));
    if ($event !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $event)) {
        $event = '';
    }
    $range = (string) ($src['range'] ?? 'all');
    if (!in_array($range, ['all', '7', '30'], true)) {
        $range = 'all';
    }
    $q = trim((string) ($src['q'] ?? ''));
    $college = (int) ($src['college'] ?? 0);
    if ($college < 1) {
        $college = 0;
    }
    return compact('status', 'class', 'inst', 'size', 'event', 'range', 'q', 'college');
}

function registration_where(array $f): array
{
    if (!function_exists('ensure_participant_schema')) {
        require_once __DIR__ . '/../includes/participant_cards.php';
    }
    ensure_participant_schema();
    $where = [];
    $params = [];
    if ($f['status'] !== 'all') {
        $where[] = 'payment_status = ?';
        $params[] = $f['status'];
    }
    if ($f['class'] !== '') {
        $where[] = 'class = ?';
        $params[] = $f['class'];
    }
    if ($f['inst'] !== '') {
        $where[] = 'institution_type = ?';
        $params[] = $f['inst'];
    }
    if ($f['range'] === '7') {
        $where[] = 'created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
    } elseif ($f['range'] === '30') {
        $where[] = 'created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
    }
    if ((int) ($f['college'] ?? 0) > 0) {
        $where[] = 'college_id = ?';
        $params[] = (int) $f['college'];
    }
    if ($f['q'] !== '') {
        $like = '%' . $f['q'] . '%';
        $clause = '(id LIKE ? OR first_name LIKE ? OR middle_name LIKE ? OR last_name LIKE ? OR mobile LIKE ? OR school_name LIKE ? OR CAST(IFNULL(participant_no, 0) AS CHAR) LIKE ?)';
        array_push($params, $like, $like, $like, $like, $like, $like, $like);
        if (preg_match('/SVSS-DN-0*(\d+)/i', $f['q'], $passMatch)) {
            $clause = '(id LIKE ? OR first_name LIKE ? OR middle_name LIKE ? OR last_name LIKE ? OR mobile LIKE ? OR school_name LIKE ? OR CAST(IFNULL(participant_no, 0) AS CHAR) LIKE ? OR id = ?)';
            $params[] = (int) $passMatch[1];
        }
        $where[] = $clause;
    }
    $sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    return [$sql, $params];
}

function registration_export_headers(): array
{
    return [
        'ID',
        'Participant number',
        'Pass number',
        'First Name',
        'Middle Name',
        'Last Name',
        'Full Name',
        'College',
        'Ticket type',
        'Mobile',
        'Fee',
        'Gateway / Platform Fee',
        'Coupon',
        'Total Paid',
        'Payment Status',
        'Razorpay Order ID',
        'Razorpay Payment ID',
        'Registered At',
        'Payment Date',
    ];
}

function registration_export_row(array $app, array $classLabels): array
{
    if (!function_exists('participant_number_label')) {
        require_once __DIR__ . '/../includes/participant_cards.php';
    }
    if (!function_exists('event_application_code')) {
        require_once __DIR__ . '/../includes/event_stations.php';
    }
    $full = trim(($app['first_name'] ?? '') . ' ' . ($app['middle_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
    $class = (string) ($app['class'] ?? '');
    $role = function_exists('form_class_label') ? form_class_label($class) : ($classLabels[$class] ?? $class);
    $paid = (($app['payment_status'] ?? '') === 'paid');
    $participantNo = $paid ? participant_number_label($app['participant_no'] ?? 0) : '';
    $passNo = $paid ? event_application_code((int) ($app['id'] ?? 0)) : '';
    return [
        $app['id'] ?? '',
        $participantNo,
        $passNo,
        $app['first_name'] ?? '',
        $app['middle_name'] ?? '',
        $app['last_name'] ?? '',
        $full,
        $app['school_name'] ?? '',
        $role,
        $app['mobile'] ?? '',
        $app['fee_base'] ?? '',
        $app['fee_platform'] ?? '',
        $app['coupon_code'] ?? '',
        $app['exam_fee'] ?? '',
        $app['payment_status'] ?? '',
        $app['razorpay_order_id'] ?? '',
        $app['razorpay_payment_id'] ?? '',
        !empty($app['created_at']) ? date('d-m-Y h:i A', strtotime((string) $app['created_at'])) : '',
        !empty($app['payment_date']) ? date('d-m-Y h:i A', strtotime((string) $app['payment_date'])) : '',
    ];
}
