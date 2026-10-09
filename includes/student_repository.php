<?php
require_once __DIR__ . '/../config/wa_config.php';
require_once __DIR__ . '/sms_otp.php';

function student_pdo(): PDO {
    return db();
}

function get_student_by_phone(string $phone): ?array {
    $stmt = student_pdo()->prepare("SELECT * FROM students WHERE phone = ? LIMIT 1");
    $stmt->execute([$phone]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function student_create_or_get(string $phone): array {
    $existing = get_student_by_phone($phone);
    if ($existing) {
        return $existing;
    }
    $stmt = student_pdo()->prepare("INSERT INTO students (phone, created_at) VALUES (?, NOW())");
    $stmt->execute([$phone]);
    $created = get_student_by_phone($phone);
    return $created ?: ['id' => (int) student_pdo()->lastInsertId(), 'phone' => $phone, 'name' => null];
}

function mark_student_login(string $phone): void {
    try {
        $stmt = student_pdo()->prepare("UPDATE students SET last_login = NOW(), login_count = login_count + 1 WHERE phone = ?");
        $stmt->execute([$phone]);
    } catch (Throwable $e) {
        // ignore
    }
}

function update_student_profile(int $id, string $name, ?string $email = null): void {
    try {
        $stmt = student_pdo()->prepare("UPDATE students SET name = ?, email = COALESCE(?, email) WHERE id = ?");
        $stmt->execute([$name, $email !== '' ? $email : null, $id]);
    } catch (Throwable $e) {
        // ignore
    }
}

function account_phone10(): string
{
    if (!function_exists('get_form_user')) {
        require_once __DIR__ . '/staff_auth.php';
    }
    $u = get_form_user();
    return function_exists('local_10_digit') ? local_10_digit((string) ($u['phone'] ?? '')) : '';
}

function account_owns_application(array $app): bool
{
    $phone = account_phone10();
    $u = get_form_user();
    if ($u['type'] === 'admin') {
        return true;
    }
    $mob = preg_replace('/\D+/', '', (string) ($app['mobile'] ?? ''));
    $sub = preg_replace('/\D+/', '', (string) ($app['submitted_by_phone'] ?? ''));
    if ($phone !== '') {
        if (strlen($mob) >= 10 && substr($mob, -10) === $phone) {
            return true;
        }
        if (strlen($sub) >= 10 && substr($sub, -10) === $phone) {
            return true;
        }
    }
    $sid = (int) ($u['id'] ?? 0);
    if ($sid > 0 && (int) ($app['submitted_by_student_id'] ?? 0) === $sid) {
        return true;
    }
    return false;
}

function applications_for_account(): array
{
    $phone = account_phone10();
    $u = get_form_user();
    $sid = ($u['type'] === 'student') ? (int) ($u['id'] ?? 0) : 0;
    if ($phone === '' && $sid < 1) {
        return [];
    }
    $pdo = student_pdo();
    $where = [];
    $params = [];
    if ($phone !== '') {
        $where[] = 'mobile = ? OR submitted_by_phone LIKE ? OR submitted_by_phone LIKE ?';
        $params[] = $phone;
        $params[] = '%' . $phone;
        $params[] = '%91' . $phone;
    }
    if ($sid > 0) {
        $where[] = 'submitted_by_student_id = ?';
        $params[] = $sid;
    }
    $sql = 'SELECT * FROM scholarship_applications WHERE (' . implode(') OR (', $where) . ') ORDER BY id DESC LIMIT 200';
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        error_log('applications_for_account: ' . $e->getMessage());
        return [];
    }
}

function account_home_url(): string
{
    return applications_for_account() ? 'my_registrations.php' : 'index.php';
}

function get_active_staff_options(): array {
    try {
        return student_pdo()->query(
            "SELECT id, name, phone FROM staff WHERE status = 'active' ORDER BY name ASC"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}
