<?php
/**
 * Registration coupon codes: fixed fee, usage limit, admin on/off.
 */

function coupons_on_form(): bool
{
    return function_exists('get_app_setting') && get_app_setting('coupons_on_form', '0') === '1';
}

function coupon_normalize(string $code): string
{
    $code = strtoupper(trim($code));
    $code = preg_replace('/[^A-Z0-9]/', '', $code) ?? '';
    return substr($code, 0, 24);
}

function ensure_coupons_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    try {
        $pdo = db();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `coupons` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `code` varchar(24) NOT NULL,
              `fee_amount` decimal(10,2) NOT NULL DEFAULT 1.00,
              `max_uses` int(11) NOT NULL DEFAULT 1,
              `used_count` int(11) NOT NULL DEFAULT 0,
              `status` enum('active','inactive') NOT NULL DEFAULT 'active',
              `note` varchar(120) DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Coupon codes that set the registration fee'
        ");
        if (function_exists('db_ensure_column')) {
            db_ensure_column($pdo, 'scholarship_applications', 'coupon_id', 'int(11) DEFAULT NULL');
            db_ensure_column($pdo, 'scholarship_applications', 'coupon_code', 'varchar(24) DEFAULT NULL');
        }
    } catch (Throwable $e) {
        error_log('coupons schema: ' . $e->getMessage());
    }
    $done = true;
}

function coupon_remaining(array $row): int
{
    $max = max(0, (int) ($row['max_uses'] ?? 0));
    $used = max(0, (int) ($row['used_count'] ?? 0));
    return max(0, $max - $used);
}

function coupon_is_usable(array $row): bool
{
    return ($row['status'] ?? '') === 'active' && coupon_remaining($row) > 0;
}

function coupon_by_code(string $code): ?array
{
    $code = coupon_normalize($code);
    if ($code === '') {
        return null;
    }
    ensure_coupons_schema();
    try {
        $stmt = db()->prepare('SELECT * FROM coupons WHERE code = ? LIMIT 1');
        $stmt->execute([$code]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function coupon_find_usable(string $code): ?array
{
    $row = coupon_by_code($code);
    if (!$row || !coupon_is_usable($row)) {
        return null;
    }
    return $row;
}

function coupon_list(): array
{
    ensure_coupons_schema();
    try {
        return db()->query('SELECT * FROM coupons ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function coupon_save(array $data, int $id = 0): array
{
    ensure_coupons_schema();
    $code = coupon_normalize((string) ($data['code'] ?? ''));
    $fee = (float) ($data['fee_amount'] ?? 0);
    $max = (int) ($data['max_uses'] ?? 0);
    $note = trim((string) ($data['note'] ?? ''));
    $statusPosted = array_key_exists('status', $data);
    $status = (($data['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active';
    if ($code === '' || strlen($code) < 3) {
        return ['ok' => false, 'error' => 'Code must be at least 3 letters or numbers.'];
    }
    if ($fee < 1 || $fee > 99999) {
        return ['ok' => false, 'error' => 'Fee must be between ₹1 and ₹99999.'];
    }
    if ($max < 1 || $max > 99999) {
        return ['ok' => false, 'error' => 'How many times this code can be used must be 1 to 99999.'];
    }
    $pdo = db();
    try {
        if ($id > 0) {
            $cur = $pdo->prepare('SELECT used_count FROM coupons WHERE id = ?');
            $cur->execute([$id]);
            $used = (int) $cur->fetchColumn();
            if ($max < $used) {
                return ['ok' => false, 'error' => 'Max uses cannot be less than already used (' . $used . ').'];
            }
            if (!$statusPosted) {
                $st = $pdo->prepare('SELECT status FROM coupons WHERE id = ?');
                $st->execute([$id]);
                $status = (string) ($st->fetchColumn() ?: 'active');
            }
            $stmt = $pdo->prepare('UPDATE coupons SET code = ?, fee_amount = ?, max_uses = ?, note = ?, status = ? WHERE id = ?');
            $stmt->execute([$code, number_format($fee, 2, '.', ''), $max, $note !== '' ? substr($note, 0, 120) : null, $status, $id]);
            return ['ok' => true];
        }
        $stmt = $pdo->prepare('INSERT INTO coupons (code, fee_amount, max_uses, note, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$code, number_format($fee, 2, '.', ''), $max, $note !== '' ? substr($note, 0, 120) : null, $status]);
        return ['ok' => true, 'id' => (int) $pdo->lastInsertId()];
    } catch (PDOException $e) {
        if ((int) $e->errorInfo[1] === 1062) {
            return ['ok' => false, 'error' => 'That coupon code already exists.'];
        }
        return ['ok' => false, 'error' => 'Could not save the coupon.'];
    }
}

function coupon_set_status(int $id, string $status): bool
{
    if ($id < 1) {
        return false;
    }
    $status = $status === 'inactive' ? 'inactive' : 'active';
    $stmt = db()->prepare('UPDATE coupons SET status = ? WHERE id = ?');
    return $stmt->execute([$status, $id]);
}

function coupon_delete(int $id): bool
{
    if ($id < 1) {
        return false;
    }
    $stmt = db()->prepare('DELETE FROM coupons WHERE id = ?');
    return $stmt->execute([$id]);
}

function coupon_increment_use(int $id): bool
{
    if ($id < 1) {
        return false;
    }
    $stmt = db()->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = ? AND used_count < max_uses AND status = ?');
    $stmt->execute([$id, 'active']);
    return $stmt->rowCount() > 0;
}
