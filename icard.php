<?php
session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/event_stations.php';
require_once __DIR__ . '/includes/form_catalog.php';
require_once __DIR__ . '/includes/app_settings.php';
require_once __DIR__ . '/includes/ticket_card.php';

$token = trim((string) ($_GET['token'] ?? ''));
$stmt = $pdo->prepare("SELECT * FROM scholarship_applications WHERE receipt_token = ? AND payment_status = 'paid'");
$stmt->execute([$token]);
$app = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$app) {
    http_response_code(404);
    echo 'Ticket not found. A pass is issued only after payment.';
    exit;
}
$code = event_application_code((int) $app['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ticket · <?php echo htmlspecialchars($code); ?></title>
<style>
body{margin:0;background:#f6efe6;padding:18px}
</style>
</head>
<body>
<?php event_ticket_render($app, true); ?>
</body>
</html>
