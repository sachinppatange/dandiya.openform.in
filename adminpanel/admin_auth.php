<?php
// Admin authentication helper functions
// Use in all adminpanel pages for session check, logout, etc.

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if the current session is a valid logged-in admin.
 * @return bool
 */
function is_admin_logged_in(): bool {
    return !empty($_SESSION['admin_auth_user']);
}

/**
 * Gets the current admin's phone (E.164 format) if logged in, else null.
 * @return string|null
 */
function get_admin_phone(): ?string {
    return $_SESSION['admin_auth_user'] ?? null;
}

/**
 * Logs out the admin by clearing session.
 */
function get_admin_name(): string {
    return (string) ($_SESSION['admin_auth_name'] ?? 'Admin');
}

function get_admin_role(): string {
    return (string) ($_SESSION['admin_auth_role'] ?? 'admin');
}

function is_super_admin(): bool {
    return function_exists('admin_role_is_super')
        ? admin_role_is_super(get_admin_role())
        : in_array(strtolower((string) get_admin_role()), ['superadmin', 'super_admin'], true);
}

function get_admin_photo(): string {
    return (string) ($_SESSION['admin_auth_photo'] ?? '');
}

function refresh_admin_session(array $admin): void {
    $_SESSION['admin_auth_user'] = (string) ($admin['phone'] ?? ($_SESSION['admin_auth_user'] ?? ''));
    $_SESSION['admin_auth_id'] = (int) ($admin['id'] ?? ($_SESSION['admin_auth_id'] ?? 0));
    $_SESSION['admin_auth_name'] = (string) ($admin['name'] ?? 'Admin');
    $_SESSION['admin_auth_role'] = (string) ($admin['role'] ?? 'admin');
    $_SESSION['admin_auth_photo'] = (string) ($admin['photo'] ?? '');
}

/**
 * Logs out the admin by clearing session.
 */
function admin_logout(): void {
    unset(
        $_SESSION['admin_auth_user'],
        $_SESSION['admin_auth_id'],
        $_SESSION['admin_auth_name'],
        $_SESSION['admin_auth_role'],
        $_SESSION['admin_auth_photo'],
        $_SESSION['admin_otp_ctx']
    );
    session_destroy();
}