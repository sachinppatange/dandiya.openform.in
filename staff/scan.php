<?php
session_start();
require_once __DIR__ . '/../includes/staff_auth.php';
require_once __DIR__ . '/../includes/staff_repository.php';
require_once __DIR__ . '/../includes/panel_layout.php';
require_once __DIR__ . '/../includes/form_catalog.php';
require_once __DIR__ . '/../includes/event_stations.php';

require_staff_login();
$scanBackHref = 'scan.php';
require __DIR__ . '/../includes/scan_desk_process.php';

panel_start([
    'title' => 'QR scan',
    'role' => 'staff',
    'active' => 'scan',
    'name' => (string) ($_SESSION['staff_auth_name'] ?? 'Staff'),
    'phone' => local_phone_display($_SESSION['staff_auth_user'] ?? ''),
    'asset_prefix' => '../',
    'links' => staff_nav_links(),
]);
require __DIR__ . '/../includes/scan_desk.php';
$scripts = '<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function(){
  var el = document.getElementById("reader");
  if (!el || typeof Html5QrcodeScanner === "undefined") return;
  var scanner = new Html5QrcodeScanner("reader", { fps: 8, qrbox: 220 }, false);
  scanner.render(function(text){
    window.location.href = "scan.php?q=" + encodeURIComponent(text);
  }, function(){});
})();
</script>';
panel_end($scripts);
