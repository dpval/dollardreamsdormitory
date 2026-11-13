<?php
/*
 * Sessions, admin checks and CSRF protection.
 *   require_once "auth.php"; require_admin_page();   // admin pages
 *   require_once "auth.php"; require_admin_api();    // admin POST/AJAX handlers (also checks CSRF)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function is_admin() {
    return !empty($_SESSION['admin']);
}

function require_admin_page() {
    if (!is_admin()) {
        header('Location: signin.php');
        exit();
    }
}

function require_admin_api() {
    if (!is_admin()) {
        http_response_code(403);
        exit('unauthorized');
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('invalid_method');
    }
    require_csrf();
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function require_csrf() {
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(403);
        exit('Your session expired. Please reload the page and try again.');
    }
}
