<?php
/**
 * Student, staff and form authentication helpers.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_student_logged_in(): bool {
    return !empty($_SESSION['student_auth_user']);
}

function is_staff_logged_in(): bool {
    return !empty($_SESSION['staff_auth_user']);
}

function is_form_user_logged_in(): bool {
    return is_student_logged_in() || !empty($_SESSION['admin_auth_user']);
}

function get_form_user(): array {
    if (is_student_logged_in()) {
        return [
            'type' => 'student',
            'id' => (int) ($_SESSION['student_auth_id'] ?? 0),
            'phone' => (string) ($_SESSION['student_auth_user'] ?? ''),
            'name' => (string) ($_SESSION['student_auth_name'] ?? 'Student'),
        ];
    }
    if (!empty($_SESSION['admin_auth_user'])) {
        return [
            'type' => 'admin',
            'id' => (int) ($_SESSION['admin_auth_id'] ?? 0),
            'phone' => (string) $_SESSION['admin_auth_user'],
            'name' => (string) ($_SESSION['admin_auth_name'] ?? 'Admin'),
        ];
    }
    return ['type' => '', 'id' => 0, 'phone' => '', 'name' => ''];
}

function require_form_login(): void {
    if (!is_form_user_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_staff_login(): void {
    if (!is_staff_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function student_logout(): void {
    unset($_SESSION['student_auth_user'], $_SESSION['student_auth_id'], $_SESSION['student_auth_name'], $_SESSION['student_otp_ctx']);
}

function staff_logout(): void {
    unset($_SESSION['staff_auth_user'], $_SESSION['staff_auth_id'], $_SESSION['staff_auth_name'], $_SESSION['staff_otp_ctx']);
}
