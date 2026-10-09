<?php
/**
 * Stable participant numbers and 3×3 inch judging cards.
 * A 12×18 inch sheet holds 24 cards (4 across × 6 down).
 */

function participant_pdo(): PDO
{
    if (!function_exists('db')) {
        require_once __DIR__ . '/../config/wa_config.php';
    }
    return db();
}

function ensure_participant_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    try {
        if (!function_exists('db_ensure_column')) {
            require_once __DIR__ . '/../db.php';
        }
        $pdo = participant_pdo();
        db_ensure_column($pdo, 'scholarship_applications', 'participant_no', 'int(11) DEFAULT NULL');
        $has = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'scholarship_applications' AND INDEX_NAME = 'uniq_participant_no'");
        $has->execute();
        if ((int) $has->fetchColumn() === 0) {
            $pdo->exec('ALTER TABLE scholarship_applications ADD UNIQUE KEY uniq_participant_no (participant_no)');
        }
    } catch (Throwable $e) {
        error_log('participant schema: ' . $e->getMessage());
    }
}

function participant_number_label($number): string
{
    $n = (int) $number;
    if ($n < 1) {
        return '';
    }
    $width = $n > 999 ? 4 : 3;
    return str_pad((string) $n, $width, '0', STR_PAD_LEFT);
}

function participant_event_title(): string
{
    if (!function_exists('landing_page_title')) {
        require_once __DIR__ . '/app_settings.php';
    }
    $title = trim(landing_page_title());
    if ($title === '') {
        $title = 'SVSS Dandiya Night';
    }
    if (!preg_match('/20\d{2}/', $title) && function_exists('form_workshop_period_label')) {
        $when = form_workshop_period_label();
        if (preg_match('/20\d{2}/', $when, $m)) {
            $title .= ' ' . $m[0];
        }
    }
    return function_exists('mb_strtoupper') ? mb_strtoupper($title, 'UTF-8') : strtoupper($title);
}

function participant_guest_name(array $app): string
{
    return trim(preg_replace('/\s+/', ' ', ($app['first_name'] ?? '') . ' ' . ($app['middle_name'] ?? '') . ' ' . ($app['last_name'] ?? '')));
}

function participant_assign_paid(): int
{
    ensure_participant_schema();
    $pdo = participant_pdo();
    $assigned = 0;
    try {
        $pdo->beginTransaction();
        $max = (int) $pdo->query('SELECT COALESCE(MAX(participant_no), 0) FROM scholarship_applications')->fetchColumn();
        $rows = $pdo->query("SELECT id FROM scholarship_applications WHERE payment_status = 'paid' AND (participant_no IS NULL OR participant_no = 0) ORDER BY COALESCE(payment_date, created_at), id")->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare('UPDATE scholarship_applications SET participant_no = ? WHERE id = ? AND (participant_no IS NULL OR participant_no = 0)');
        foreach ($rows as $row) {
            $max++;
            $stmt->execute([$max, (int) $row['id']]);
            $assigned++;
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('participant assign: ' . $e->getMessage());
    }
    return $assigned;
}

function participant_paid_rows(int $from = 0, int $to = 0): array
{
    ensure_participant_schema();
    participant_assign_paid();
    $sql = "SELECT * FROM scholarship_applications WHERE payment_status = 'paid' AND participant_no IS NOT NULL";
    $params = [];
    if ($from > 0) {
        $sql .= ' AND participant_no >= ?';
        $params[] = $from;
    }
    if ($to > 0) {
        $sql .= ' AND participant_no <= ?';
        $params[] = $to;
    }
    $sql .= ' ORDER BY participant_no ASC';
    $stmt = participant_pdo()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function participant_sheet_size(): int
{
    return 24;
}
