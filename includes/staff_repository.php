<?php
/**
 * Staff login accounts created from the admin panel.
 */

require_once __DIR__ . '/../config/wa_config.php';
require_once __DIR__ . '/sms_otp.php';

function staff_pdo(): PDO {
    return db();
}

function get_staff_by_phone(string $phone): ?array {
    $stmt = staff_pdo()->prepare("SELECT * FROM staff WHERE phone = ? LIMIT 1");
    $stmt->execute([$phone]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function get_staff_by_id(int $id): ?array {
    $stmt = staff_pdo()->prepare("SELECT * FROM staff WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function get_active_staff_by_phone(string $phone): ?array {
    $staff = get_staff_by_phone($phone);
    if (!$staff || ($staff['status'] ?? '') !== 'active') {
        return null;
    }
    return $staff;
}

function get_all_staff(): array {
    try {
        return staff_pdo()->query("SELECT * FROM staff ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function add_staff(string $name, string $phone, ?string $createdBy = null): array {
    $name = trim($name);
    $phone = to_e164($phone) ?? '';
    if ($name === '' || strlen($name) < 2) {
        return ['ok' => false, 'error' => 'Please enter a valid staff name.'];
    }
    if ($phone === '') {
        return ['ok' => false, 'error' => 'Please enter a valid 10-digit mobile number.'];
    }
    if (get_staff_by_phone($phone)) {
        return ['ok' => false, 'error' => 'This mobile number is already registered as staff.'];
    }
    try {
        $code = make_staff_referral_code();
        try {
            $stmt = staff_pdo()->prepare("INSERT INTO staff (name, phone, status, created_by, referral_code) VALUES (?, ?, 'active', ?, ?)");
            $stmt->execute([$name, $phone, $createdBy, $code]);
        } catch (Throwable $e) {
            $stmt = staff_pdo()->prepare("INSERT INTO staff (name, phone, status, created_by) VALUES (?, ?, 'active', ?)");
            $stmt->execute([$name, $phone, $createdBy]);
        }
        $id = (int) staff_pdo()->lastInsertId();
        if ($id < 1) {
            $created = get_staff_by_phone($phone);
            $id = (int) ($created['id'] ?? 0);
        }
        if ($id) {
            ensure_staff_referral_code($id);
        }
        return ['ok' => true, 'error' => '', 'id' => $id];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Could not create staff login. Please try again.'];
    }
}

function set_staff_status(int $id, string $status): bool {
    if (!in_array($status, ['active', 'inactive'], true)) {
        return false;
    }
    $stmt = staff_pdo()->prepare("UPDATE staff SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $id]);
}

function delete_staff(int $id): bool {
    $stmt = staff_pdo()->prepare("DELETE FROM staff WHERE id = ?");
    return $stmt->execute([$id]);
}

function mark_staff_login(string $phone): void {
    try {
        $stmt = staff_pdo()->prepare("UPDATE staff SET last_login = NOW(), login_count = login_count + 1 WHERE phone = ?");
        $stmt->execute([$phone]);
    } catch (Throwable $e) {
        // ignore
    }
}

function is_active_staff_id(int $id): bool {
    if ($id < 1) {
        return false;
    }
    $staff = get_staff_by_id($id);
    return $staff && ($staff['status'] ?? '') === 'active';
}

function get_admin_by_phone(string $phone): ?array {
    try {
        $stmt = staff_pdo()->prepare("SELECT * FROM admins WHERE phone = ? LIMIT 1");
        $stmt->execute([$phone]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function get_active_admin_by_phone(string $phone): ?array {
    $admin = get_admin_by_phone($phone);
    if (!$admin || ($admin['status'] ?? '') !== 'active') {
        return null;
    }
    return $admin;
}

function get_admin_phones(): array {
    try {
        $stmt = staff_pdo()->query("SELECT phone FROM admins WHERE status = 'active'");
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function mark_admin_login(string $phone): void {
    try {
        $stmt = staff_pdo()->prepare("UPDATE admins SET last_login = NOW(), login_count = login_count + 1 WHERE phone = ?");
        $stmt->execute([$phone]);
    } catch (Throwable $e) {
        // ignore
    }
}

function get_admin_by_id(int $id): ?array {
    if ($id < 1) {
        return null;
    }
    try {
        $stmt = staff_pdo()->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function get_all_admins(): array {
    try {
        return staff_pdo()->query("SELECT * FROM admins ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function admin_role_is_super(?string $role): bool {
    $n = strtolower(str_replace([' ', '-'], '_', (string) $role));
    return in_array($n, ['superadmin', 'super_admin'], true);
}

function update_admin_profile(int $id, string $name, string $email, string $phone, ?string $photoPath = null): array {
    $name = trim($name);
    $email = trim($email);
    $phone = to_e164($phone) ?? '';
    if ($name === '' || strlen($name) < 2) {
        return ['ok' => false, 'error' => 'Please enter a valid name.'];
    }
    if ($phone === '') {
        return ['ok' => false, 'error' => 'Please enter a valid 10-digit mobile number.'];
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Please enter a valid email address.'];
    }
    $existing = get_admin_by_phone($phone);
    if ($existing && (int) ($existing['id'] ?? 0) !== $id) {
        return ['ok' => false, 'error' => 'This mobile number is already used by another admin.'];
    }
    try {
        if ($photoPath !== null) {
            try {
                $stmt = staff_pdo()->prepare("UPDATE admins SET name = ?, email = ?, phone = ?, photo = ? WHERE id = ?");
                $stmt->execute([$name, $email !== '' ? $email : null, $phone, $photoPath !== '' ? $photoPath : null, $id]);
            } catch (Throwable $e) {
                $stmt = staff_pdo()->prepare("UPDATE admins SET name = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$name, $email !== '' ? $email : null, $phone, $id]);
            }
        } else {
            $stmt = staff_pdo()->prepare("UPDATE admins SET name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $email !== '' ? $email : null, $phone, $id]);
        }
        $row = get_admin_by_id($id);
        return ['ok' => true, 'error' => '', 'admin' => $row];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Could not save profile. Please try again.'];
    }
}

function add_admin_account(string $name, string $phone, string $email = '', string $role = 'admin', ?string $createdBy = null, string $password = ''): array {
    $name = trim($name);
    $phone = to_e164($phone) ?? '';
    $email = trim($email);
    $role = admin_role_is_super($role) ? 'superadmin' : 'admin';
    $plain = trim($password);
    if ($name === '' || strlen($name) < 2) {
        return ['ok' => false, 'error' => 'Please enter a valid name.'];
    }
    if ($phone === '') {
        return ['ok' => false, 'error' => 'Please enter a valid 10-digit mobile number.'];
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Please enter a valid email address.'];
    }
    if ($plain === '') {
        $plain = admin_starter_password();
    } elseif (strlen($plain) < 8) {
        return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
    }
    if (get_admin_by_phone($phone)) {
        return ['ok' => false, 'error' => 'This mobile is already registered as admin.'];
    }
    $hash = password_hash($plain, PASSWORD_DEFAULT);
    try {
        try {
            $stmt = staff_pdo()->prepare("INSERT INTO admins (name, phone, email, role, status, password_hash) VALUES (?, ?, ?, ?, 'active', ?)");
            $stmt->execute([$name, $phone, $email !== '' ? $email : null, $role, $hash]);
        } catch (Throwable $e) {
            $fallbackRole = $role === 'superadmin' ? 'super_admin' : 'admin';
            try {
                $stmt = staff_pdo()->prepare("INSERT INTO admins (name, phone, email, role, status, password_hash) VALUES (?, ?, ?, ?, 'active', ?)");
                $stmt->execute([$name, $phone, $email !== '' ? $email : null, $fallbackRole, $hash]);
            } catch (Throwable $e2) {
                $stmt = staff_pdo()->prepare("INSERT INTO admins (name, phone, email, role, status) VALUES (?, ?, ?, ?, 'active')");
                $stmt->execute([$name, $phone, $email !== '' ? $email : null, $fallbackRole]);
            }
        }
        return ['ok' => true, 'error' => '', 'id' => (int) staff_pdo()->lastInsertId()];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Could not create admin login.'];
    }
}

function set_admin_status(int $id, string $status): bool {
    if (!in_array($status, ['active', 'inactive'], true)) {
        return false;
    }
    $stmt = staff_pdo()->prepare("UPDATE admins SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $id]);
}

function count_admins(?string $status = null): int
{
    try {
        if ($status) {
            $stmt = staff_pdo()->prepare("SELECT COUNT(*) FROM admins WHERE status = ?");
            $stmt->execute([$status]);
            return (int) $stmt->fetchColumn();
        }
        return (int) staff_pdo()->query("SELECT COUNT(*) FROM admins")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function count_super_admins(): int
{
    try {
        return (int) staff_pdo()->query("
            SELECT COUNT(*) FROM admins
            WHERE status = 'active' AND role IN ('superadmin','super_admin')
        ")->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function update_admin_account(int $id, string $name, string $email, string $phone, string $role, string $status, string $password = ''): array
{
    $existing = get_admin_by_id($id);
    if (!$existing) {
        return ['ok' => false, 'error' => 'Admin not found.'];
    }
    $name = trim($name);
    $email = trim($email);
    $phone = to_e164($phone) ?? '';
    $status = $status === 'inactive' ? 'inactive' : 'active';
    $wantSuper = admin_role_is_super($role);
    if ($name === '' || strlen($name) < 2) {
        return ['ok' => false, 'error' => 'Please enter a valid name.'];
    }
    if ($phone === '') {
        return ['ok' => false, 'error' => 'Please enter a valid 10-digit mobile number.'];
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Please enter a valid email address.'];
    }
    $other = get_admin_by_phone($phone);
    if ($other && (int) ($other['id'] ?? 0) !== $id) {
        return ['ok' => false, 'error' => 'This mobile number is already used by another admin.'];
    }
    if ($status === 'inactive' && ($existing['status'] ?? '') === 'active' && count_admins('active') <= 1) {
        return ['ok' => false, 'error' => 'You cannot deactivate the last active admin.'];
    }
    if (admin_role_is_super($existing['role'] ?? '') && !$wantSuper && count_super_admins() <= 1) {
        return ['ok' => false, 'error' => 'You cannot remove the last superadmin.'];
    }
    $roleValue = $wantSuper ? 'superadmin' : 'admin';
    try {
        try {
            $stmt = staff_pdo()->prepare("UPDATE admins SET name = ?, email = ?, phone = ?, role = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $email !== '' ? $email : null, $phone, $roleValue, $status, $id]);
        } catch (Throwable $e) {
            $fallbackRole = $wantSuper ? 'super_admin' : 'admin';
            $stmt = staff_pdo()->prepare("UPDATE admins SET name = ?, email = ?, phone = ?, role = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $email !== '' ? $email : null, $phone, $fallbackRole, $status, $id]);
        }
        $plain = trim($password);
        if ($plain !== '') {
            $pw = set_admin_password($id, $plain);
            if (!$pw['ok']) {
                return $pw;
            }
        }
        return ['ok' => true, 'error' => '', 'admin' => get_admin_by_id($id)];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Could not update admin.'];
    }
}

function delete_admin_account(int $id, int $actorId): array
{
    if ($id < 1) {
        return ['ok' => false, 'error' => 'Admin not found.'];
    }
    if ($id === $actorId) {
        return ['ok' => false, 'error' => 'You cannot delete your own login.'];
    }
    $row = get_admin_by_id($id);
    if (!$row) {
        return ['ok' => false, 'error' => 'Admin not found.'];
    }
    if (count_admins() <= 1) {
        return ['ok' => false, 'error' => 'You cannot delete the last admin.'];
    }
    if (admin_role_is_super($row['role'] ?? '') && count_super_admins() <= 1) {
        return ['ok' => false, 'error' => 'You cannot delete the last superadmin.'];
    }
    try {
        $stmt = staff_pdo()->prepare("DELETE FROM admins WHERE id = ?");
        $stmt->execute([$id]);
        return ['ok' => true, 'error' => ''];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Could not delete admin.'];
    }
}

function admin_starter_password(): string
{
    return 'AgniPanel@2026';
}

function ensure_admin_password_column(): void
{
    try {
        staff_pdo()->exec("ALTER TABLE `admins` ADD COLUMN `password_hash` varchar(255) DEFAULT NULL");
    } catch (Throwable $e) {
        // exists
    }
}

function seed_empty_admin_passwords(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    try {
        ensure_admin_password_column();
        $hash = password_hash(admin_starter_password(), PASSWORD_DEFAULT);
        $stmt = staff_pdo()->prepare("UPDATE admins SET password_hash = ? WHERE password_hash IS NULL OR password_hash = ''");
        $stmt->execute([$hash]);
        $done = true;
    } catch (Throwable $e) {
        // ignore
    }
}

function set_admin_password(int $id, string $plain): array
{
    $plain = trim($plain);
    if (strlen($plain) < 8) {
        return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
    }
    try {
        ensure_admin_password_column();
        $stmt = staff_pdo()->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
        $stmt->execute([password_hash($plain, PASSWORD_DEFAULT), $id]);
        return ['ok' => true, 'error' => ''];
    } catch (Throwable $e) {
        return ['ok' => false, 'error' => 'Could not save password.'];
    }
}

function admin_password_login(string $mobile, string $password): array
{
    seed_empty_admin_passwords();
    $phone = to_e164($mobile);
    if (!$phone) {
        return ['ok' => false, 'error' => 'Please enter a valid 10-digit mobile number.'];
    }
    $admin = get_active_admin_by_phone($phone);
    if (!$admin) {
        return ['ok' => false, 'error' => 'This mobile is not authorized as admin.'];
    }
    $hash = (string) ($admin['password_hash'] ?? '');
    if ($hash === '' || !password_verify($password, $hash)) {
        return ['ok' => false, 'error' => 'Incorrect mobile number or password.'];
    }
    return ['ok' => true, 'error' => '', 'admin' => $admin];
}

function make_staff_referral_code(): string {
    $alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
    for ($try = 0; $try < 12; $try++) {
        $code = '';
        for ($i = 0; $i < 8; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        if (!get_staff_by_referral_code($code)) {
            return $code;
        }
    }
    return substr(bin2hex(random_bytes(6)), 0, 10);
}

function get_staff_by_referral_code(string $code): ?array {
    $code = strtolower(trim($code));
    if ($code === '' || !preg_match('/^[a-z0-9]{6,16}$/', $code)) {
        return null;
    }
    try {
        $stmt = staff_pdo()->prepare("SELECT * FROM staff WHERE referral_code = ? LIMIT 1");
        $stmt->execute([$code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function ensure_staff_referral_code(int $id): string {
    $staff = get_staff_by_id($id);
    if (!$staff) {
        return '';
    }
    $existing = strtolower(trim((string) ($staff['referral_code'] ?? '')));
    if ($existing !== '') {
        return $existing;
    }
    $code = make_staff_referral_code();
    try {
        $stmt = staff_pdo()->prepare("UPDATE staff SET referral_code = ? WHERE id = ?");
        $stmt->execute([$code, $id]);
        return $code;
    } catch (Throwable $e) {
        return '';
    }
}

function ensure_all_staff_referral_codes(): void {
    foreach (get_all_staff() as $row) {
        if (empty($row['referral_code']) && !empty($row['id'])) {
            ensure_staff_referral_code((int) $row['id']);
        }
    }
}

function app_public_base_url(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/login.php'));
    $dir = rtrim(dirname($script), '/');
    if (preg_match('#/(staff|adminpanel)$#', $dir)) {
        $dir = dirname($dir);
    }
    if ($dir === '/' || $dir === '.' || $dir === '\\') {
        $dir = '';
    }
    return ($https ? 'https' : 'http') . '://' . $host . $dir;
}

function staff_student_form_url(?array $staff): string {
    if (!$staff) {
        return app_public_base_url() . '/login.php';
    }
    $code = trim((string) ($staff['referral_code'] ?? ''));
    if ($code === '' && !empty($staff['id'])) {
        $code = ensure_staff_referral_code((int) $staff['id']);
    }
    if ($code === '') {
        return app_public_base_url() . '/login.php';
    }
    return app_public_base_url() . '/login.php?ref=' . rawurlencode($code);
}

function capture_staff_referral(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $code = strtolower(trim((string) ($_GET['ref'] ?? $_POST['ref'] ?? '')));
    if ($code === '') {
        return;
    }
    $staff = get_staff_by_referral_code($code);
    if ($staff && ($staff['status'] ?? '') === 'active') {
        $_SESSION['staff_ref'] = $staff['referral_code'];
        $_SESSION['staff_ref_id'] = (int) $staff['id'];
    }
}

function get_locked_referral_staff(): ?array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $id = (int) ($_SESSION['staff_ref_id'] ?? 0);
    $code = strtolower(trim((string) ($_SESSION['staff_ref'] ?? '')));
    $staff = $id ? get_staff_by_id($id) : ($code !== '' ? get_staff_by_referral_code($code) : null);
    if (!$staff || ($staff['status'] ?? '') !== 'active') {
        unset($_SESSION['staff_ref'], $_SESSION['staff_ref_id']);
        return null;
    }
    return $staff;
}
