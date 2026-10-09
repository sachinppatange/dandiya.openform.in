<?php
session_start();
$q = $_GET;
header('Location: login.php' . ($q ? ('?' . http_build_query($q)) : ''));
exit;
