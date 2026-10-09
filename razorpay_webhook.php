<?php
/**
 * Razorpay webhook — updates payment status when checkout callback is missed
 * or the payment is captured later on the Razorpay dashboard.
 *
 * Configure at: Razorpay Dashboard → Account & Settings → Webhooks
 */
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/app_settings.php';
require_once __DIR__ . '/includes/payment_service.php';

ensure_razorpay_webhook_schema();

$body = file_get_contents('php://input') ?: '';
$signature = (string) ($_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '');
$secret = trim((string) get_app_setting('razorpay_webhook_secret', ''));

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST only']);
    exit;
}

if ($secret === '') {
    razorpay_webhook_log(null, null, null, null, 'error', 'Webhook secret not saved in Settings → Payments');
    http_response_code(503);
    echo json_encode(['ok' => false, 'error' => 'Webhook secret not configured']);
    exit;
}

if (!verify_razorpay_webhook_signature($body, $signature, $secret)) {
    razorpay_webhook_log(null, null, null, null, 'error', 'Invalid webhook signature');
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid signature']);
    exit;
}

$payload = json_decode($body, true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

$result = process_razorpay_webhook_payload($payload);
http_response_code((int) ($result['http'] ?? 200));
echo json_encode(['ok' => !empty($result['ok']), 'message' => $result['message'] ?? 'ok']);
exit;
