<?php
session_start();
require_once __DIR__ . '/../includes/staff_auth.php';
staff_logout();
header('Location: staff/login.php');
exit;
