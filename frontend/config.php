<?php
/**
 * Configuration File
 * Frontend configuration for Music School Scheduler
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * BASE PATH CONFIGURATION
 * Auto-detect base path for subdirectory deployment support
 */

// Auto-detect protocol and host
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

// Auto-detect base path from script location
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
// Remove role-specific subdirectories (admin, teacher, student) if present
$basePath = preg_replace('#/(admin|teacher|student)$#', '', $scriptPath);

// Define BASE_PATH (path only, no protocol/host)
define('BASE_PATH', $basePath);

// Auto-detect API URL (assumes backend is parallel to frontend)
$apiPath = str_replace('/frontend', '/backend', $basePath);
define('API_BASE_URL', $protocol . '://' . $host . $apiPath . '/api');

// Manual override (uncomment and modify if auto-detection doesn't work)
// define('BASE_PATH', '/appuntamento/frontend');  // For http://example.com/appuntamento/frontend/
// define('API_BASE_URL', 'http://www.beesmart.cloud/appuntamento/backend/api');

// Application settings
define('APP_NAME', 'Music School Scheduler');
define('APP_VERSION', '1.0.0');

// Session timeout (7 days - same as JWT)
define('SESSION_TIMEOUT', 7 * 24 * 60 * 60);

/**
 * Get base URL for a path
 */
function baseUrl($path = '') {
    $path = ltrim($path, '/');
    $base = rtrim(BASE_PATH, '/');

    if (empty($path)) {
        return $base ?: '/';
    }

    return $base . '/' . $path;
}

/**
 * Get asset URL (for CSS, JS, images)
 */
function assetUrl($path) {
    $path = ltrim($path, '/');
    $base = rtrim(BASE_PATH, '/');
    return $base . '/assets/' . $path;
}

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_TEACHER', 'teacher');
define('ROLE_STUDENT', 'student');

// Role colors
define('COLOR_ADMIN_PRIMARY', '#2C3E50');
define('COLOR_ADMIN_SECONDARY', '#3498DB');
define('COLOR_TEACHER_PRIMARY', '#27AE60');
define('COLOR_TEACHER_SECONDARY', '#16A085');
define('COLOR_STUDENT_PRIMARY', '#E67E22');
define('COLOR_STUDENT_SECONDARY', '#F39C12');

// Date/Time formats
define('DATE_FORMAT', 'd/m/Y');
define('TIME_FORMAT', 'H:i');
define('DATETIME_FORMAT', 'd/m/Y H:i');

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user']) && isset($_SESSION['token']);
}

/**
 * Get current user
 */
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

/**
 * Get JWT token
 */
function getToken() {
    return $_SESSION['token'] ?? null;
}

/**
 * Get user role
 */
function getUserRole() {
    $user = getCurrentUser();
    return $user['user_type'] ?? null;
}

/**
 * Check if user has role
 */
function hasRole($role) {
    return getUserRole() === $role;
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . baseUrl('index.php'));
        exit;
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ' . baseUrl('index.php'));
        exit;
    }
}

/**
 * Logout user
 */
function logout() {
    session_destroy();
    header('Location: ' . baseUrl('index.php'));
    exit;
}

/**
 * Get role color
 */
function getRoleColor($role = null) {
    if ($role === null) {
        $role = getUserRole();
    }

    switch ($role) {
        case ROLE_ADMIN:
            return COLOR_ADMIN_PRIMARY;
        case ROLE_TEACHER:
            return COLOR_TEACHER_PRIMARY;
        case ROLE_STUDENT:
            return COLOR_STUDENT_PRIMARY;
        default:
            return '#333333';
    }
}

/**
 * Get role secondary color
 */
function getRoleSecondaryColor($role = null) {
    if ($role === null) {
        $role = getUserRole();
    }

    switch ($role) {
        case ROLE_ADMIN:
            return COLOR_ADMIN_SECONDARY;
        case ROLE_TEACHER:
            return COLOR_TEACHER_SECONDARY;
        case ROLE_STUDENT:
            return COLOR_STUDENT_SECONDARY;
        default:
            return '#666666';
    }
}

/**
 * Get role name in Italian
 */
function getRoleName($role = null) {
    if ($role === null) {
        $role = getUserRole();
    }

    switch ($role) {
        case ROLE_ADMIN:
            return 'Amministratore';
        case ROLE_TEACHER:
            return 'Docente';
        case ROLE_STUDENT:
            return 'Studente';
        default:
            return 'Utente';
    }
}

/**
 * Format date
 */
function formatDate($date) {
    if (empty($date)) return '';
    return date(DATE_FORMAT, strtotime($date));
}

/**
 * Format time
 */
function formatTime($time) {
    if (empty($time)) return '';
    return date(TIME_FORMAT, strtotime($time));
}

/**
 * Format datetime
 */
function formatDateTime($datetime) {
    if (empty($datetime)) return '';
    return date(DATETIME_FORMAT, strtotime($datetime));
}

/**
 * Show success message
 */
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Show error message
 */
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Get and clear success message
 */
function getSuccessMessage() {
    $message = $_SESSION['success_message'] ?? null;
    unset($_SESSION['success_message']);
    return $message;
}

/**
 * Get and clear error message
 */
function getErrorMessage() {
    $message = $_SESSION['error_message'] ?? null;
    unset($_SESSION['error_message']);
    return $message;
}
