<?php
// C:\xampp\htdocs\pos\includes\auth_middleware.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Ensures the user is logged in. Redirects to login page if not.
 */
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /pos/index.php");
        exit;
    }
}

/**
 * Ensures the user has one of the allowed roles. Redirects or outputs JSON error.
 */
function require_role($allowed_roles) {
    require_login();
    
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        // If it's an API request (expecting JSON)
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized access.']);
            exit;
        } else {
            // If it's a normal page request
            die('Unauthorized access. You do not have permission to view this page.');
        }
    }
}

/**
 * Ensures the route is only accessible to super_admin
 */
function require_super_admin() {
    require_role(['super_admin']);
}

/**
 * Activity Logging Helper
 */
function log_activity($action, $description = null) {
    global $pdo;
    if (!isset($_SESSION['user_id'])) return;
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, description) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $action, $description]);
    } catch (Exception $e) {
        // Silent fail for logging to prevent crashing main thread
    }
}

?>
