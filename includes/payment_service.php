<?php
/**
 * Mark registrations paid from checkout or Razorpay webhooks.
 */

function ensure_razorpay_webhook_schema(): void
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
            CREATE TABLE IF NOT EXISTS `razorpay_webhook_logs` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `event_name` varchar(80) DEFAULT NULL,
              `payment_id` varchar(64) DEFAULT NULL,
              `order_id` varchar(64) DEFAULT NULL,
              `application_id` int(11) DEFAULT NULL,
              `result` varchar(40) DEFAULT NULL,
              `message` varchar(500) DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `order_id` (`order_id`),
              KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (Throwable $e) {
        error_log('razorpay webhook schema: ' . $e->getMessage());
    }
}

function razorpay_webhook_log(?string $event, ?string $paymentId, ?string $orderId, ?int $appId, string $result, string $message = ''): void
{
    ensure_razorpay_webhook_schema();
    try {
        $pdo = function_exists('db') ? db() : ($GLOBALS['pdo'] ?? null);
        if (!$pdo instanceof PDO) {
            return;
        }
        $stmt = $pdo->prepare('
            INSERT INTO razorpay_webhook_logs (event_name, payment_id, order_id, application_id, result, message)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$event, $paymentId, $orderId, $appId, $result, substr($message, 0, 500)]);
    } catch (Throwable $e) {
        error_log('razorpay_webhook_log: ' . $e->getMessage());
    }
    $line = date('c') . " {$result} event={$event} pay={$paymentId} order={$orderId} app={$appId} {$message}\n";
    $dir = dirname(__DIR__) . '/storage/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    @file_put_contents($dir . '/razorpay_webhook.log', $line, FILE_APPEND);
}

function razorpay_webhook_url(): string
{
    if (!function_exists('app_public_base_url')) {
        require_once __DIR__ . '/staff_repository.php';
    }
    return rtrim(app_public_base_url(), '/') . '/razorpay_webhook.php';
}

function find_application_by_order_id(string $orderId): ?array
{
    if ($orderId === '') {
        return null;
    }
    $pdo = function_exists('db') ? db() : ($GLOBALS['pdo'] ?? null);
    if (!$pdo instanceof PDO) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM scholarship_applications WHERE razorpay_order_id = ? LIMIT 1');
    $stmt->execute([$orderId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function find_application_by_payment_id(string $paymentId): ?array
{
    if ($paymentId === '') {
        return null;
    }
    $pdo = function_exists('db') ? db() : ($GLOBALS['pdo'] ?? null);
    if (!$pdo instanceof PDO) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM scholarship_applications WHERE razorpay_payment_id = ? LIMIT 1');
    $stmt->execute([$paymentId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function send_paid_email_if_needed(array $app): void
{
    if (empty($app['email'])) {
        return;
    }
    try {
        $emailFile = dirname(__DIR__) . '/send_email.php';
        if (!is_file($emailFile)) {
            return;
        }
        require_once $emailFile;
        if (function_exists('sendPaymentSuccessEmail')) {
            sendPaymentSuccessEmail($app);
        }
    } catch (Throwable $e) {
        error_log('paid email: ' . $e->getMessage());
    }
}

/**
 * @return array{ok:bool,status:string,token:?string,application:?array,message:string}
 */
function mark_application_paid(array $app, string $paymentId, string $signature = '', string $source = 'checkout'): array
{
    $pdo = function_exists('db') ? db() : ($GLOBALS['pdo'] ?? null);
    if (!$pdo instanceof PDO) {
        return ['ok' => false, 'status' => 'error', 'token' => null, 'application' => null, 'message' => 'No database'];
    }
    $id = (int) ($app['id'] ?? 0);
    if ($id < 1) {
        return ['ok' => false, 'status' => 'error', 'token' => null, 'application' => null, 'message' => 'Invalid application'];
    }

    $wasPaid = (($app['payment_status'] ?? '') === 'paid');
    $token = trim((string) ($app['receipt_token'] ?? ''));
    if ($token === '') {
        $token = bin2hex(random_bytes(32));
    }

    $stmt = $pdo->prepare('
        UPDATE scholarship_applications
           SET payment_status = ?,
               razorpay_payment_id = COALESCE(NULLIF(?, ""), razorpay_payment_id),
               razorpay_signature = COALESCE(NULLIF(?, ""), razorpay_signature),
               receipt_token = ?,
               payment_date = COALESCE(payment_date, NOW())
         WHERE id = ?
    ');
    $stmt->execute(['paid', $paymentId, $signature, $token, $id]);

    $fresh = $pdo->prepare('SELECT * FROM scholarship_applications WHERE id = ?');
    $fresh->execute([$id]);
    $app = $fresh->fetch(PDO::FETCH_ASSOC) ?: $app;

    if (!$wasPaid) {
        send_paid_email_if_needed($app);
        if (!function_exists('participant_assign_paid')) {
            require_once __DIR__ . '/participant_cards.php';
        }
        participant_assign_paid();
    }

    return [
        'ok' => true,
        'status' => $wasPaid ? 'already_paid' : 'paid',
        'token' => $token,
        'application' => $app,
        'message' => $wasPaid ? 'Already marked paid (' . $source . ')' : 'Marked paid via ' . $source,
    ];
}

function mark_application_failed(array $app, string $source = 'webhook'): bool
{
    if (($app['payment_status'] ?? '') === 'paid') {
        return false;
    }
    $pdo = function_exists('db') ? db() : ($GLOBALS['pdo'] ?? null);
    if (!$pdo instanceof PDO) {
        return false;
    }
    $stmt = $pdo->prepare("UPDATE scholarship_applications SET payment_status = 'failed' WHERE id = ? AND payment_status <> 'paid'");
    return $stmt->execute([(int) $app['id']]);
}

function verify_razorpay_webhook_signature(string $body, string $signature, string $secret): bool
{
    if ($body === '' || $signature === '' || $secret === '') {
        return false;
    }
    $expected = hash_hmac('sha256', $body, $secret);
    return hash_equals($expected, $signature);
}

function process_razorpay_webhook_payload(array $payload): array
{
    $event = (string) ($payload['event'] ?? '');
    $payment = $payload['payload']['payment']['entity'] ?? [];
    $order = $payload['payload']['order']['entity'] ?? [];
    $paymentId = (string) ($payment['id'] ?? '');
    $orderId = (string) ($payment['order_id'] ?? $order['id'] ?? '');
    $payStatus = strtolower((string) ($payment['status'] ?? ''));

    $app = $orderId !== '' ? find_application_by_order_id($orderId) : null;
    if (!$app && $paymentId !== '') {
        $app = find_application_by_payment_id($paymentId);
    }

    if (!$app) {
        razorpay_webhook_log($event, $paymentId, $orderId, null, 'ignored', 'No matching registration for this order');
        return ['ok' => true, 'http' => 200, 'message' => 'No matching registration'];
    }

    $appId = (int) $app['id'];

    if (in_array($event, ['payment.captured', 'order.paid'], true) || $payStatus === 'captured') {
        $result = mark_application_paid($app, $paymentId, 'webhook:' . $event, 'webhook');
        razorpay_webhook_log($event, $paymentId, $orderId, $appId, $result['status'], $result['message']);
        return ['ok' => true, 'http' => 200, 'message' => $result['message']];
    }

    if ($event === 'payment.failed' || $payStatus === 'failed') {
        if (($app['payment_status'] ?? '') === 'paid') {
            razorpay_webhook_log($event, $paymentId, $orderId, $appId, 'ignored', 'Already paid; failed event ignored');
            return ['ok' => true, 'http' => 200, 'message' => 'Already paid'];
        }
        mark_application_failed($app, 'webhook');
        razorpay_webhook_log($event, $paymentId, $orderId, $appId, 'failed', 'Marked failed');
        return ['ok' => true, 'http' => 200, 'message' => 'Marked failed'];
    }

    razorpay_webhook_log($event, $paymentId, $orderId, $appId, 'ignored', 'Unhandled event');
    return ['ok' => true, 'http' => 200, 'message' => 'Ignored'];
}
