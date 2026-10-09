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
    return compact('status', 'class', 'inst', 'size', 'event', 'range', 'q');
}

function registration_where(array $f): array
{
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
    if ($f['q'] !== '') {
        $where[] = '(id LIKE ? OR first_name LIKE ? OR middle_name LIKE ? OR last_name LIKE ? OR mobile LIKE ? OR school_name LIKE ?)';
        $like = '%' . $f['q'] . '%';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }
    $sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
    return [$sql, $params];
}

function registration_export_headers(): array
{
    return [
        'ID',
        'First Name',
        'Middle Name',
        'Last Name',
        'Full Name',
        'Organisation Name',
        'Role',
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
    $full = trim(($app['first_name'] ?? '') . ' ' . ($app['middle_name'] ?? '') . ' ' . ($app['last_name'] ?? ''));
    $class = (string) ($app['class'] ?? '');
    $role = function_exists('form_class_label') ? form_class_label($class) : ($classLabels[$class] ?? $class);
    return [
        $app['id'] ?? '',
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
