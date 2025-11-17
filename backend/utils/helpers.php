<?php
/**
 * Helper Functions
 * Utility functions for the application
 */

/**
 * Generate unique ID for users
 * Format: 8 alphanumeric characters (e.g., A7X9K2M5)
 */
function generateUniqueUserId() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $length = 8;
    $uniqueId = '';

    for ($i = 0; $i < $length; $i++) {
        $uniqueId .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return $uniqueId;
}

/**
 * Generate unique ID for schools
 * Format: SC + 4 digits + 4 alphanumeric (e.g., SC1234A7X9)
 */
function generateUniqueSchoolId() {
    $number = str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $alpha = '';

    for ($i = 0; $i < 4; $i++) {
        $alpha .= $characters[random_int(0, strlen($characters) - 1)];
    }

    return 'SC' . $number . $alpha;
}

/**
 * Check if unique ID already exists in database
 */
function isUniqueIdAvailable($uniqueId, $table = 'users') {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table WHERE unique_id = ?");
    $stmt->execute([$uniqueId]);
    $result = $stmt->fetch();
    return $result['count'] == 0;
}

/**
 * Generate unique ID with availability check
 */
function generateAvailableUniqueId($type = 'user') {
    $maxAttempts = 10;
    $attempts = 0;

    while ($attempts < $maxAttempts) {
        $uniqueId = ($type === 'school') ? generateUniqueSchoolId() : generateUniqueUserId();
        $table = ($type === 'school') ? 'schools' : 'users';

        if (isUniqueIdAvailable($uniqueId, $table)) {
            return $uniqueId;
        }

        $attempts++;
    }

    throw new Exception("Failed to generate unique ID after $maxAttempts attempts");
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate date format (YYYY-MM-DD)
 */
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Validate time format (HH:MM:SS or HH:MM)
 */
function validateTime($time) {
    $patterns = [
        '/^([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/',  // HH:MM:SS
        '/^([01][0-9]|2[0-3]):([0-5][0-9])$/'                 // HH:MM
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $time)) {
            return true;
        }
    }

    return false;
}

/**
 * Send JSON response
 */
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Send success response
 */
function sendSuccess($message, $data = null, $statusCode = 200) {
    $response = [
        'success' => true,
        'message' => $message
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    sendJsonResponse($response, $statusCode);
}

/**
 * Send error response
 */
function sendError($message, $statusCode = 400, $errors = null) {
    $response = [
        'success' => false,
        'message' => $message
    ];

    if ($errors !== null) {
        $response['errors'] = $errors;
    }

    sendJsonResponse($response, $statusCode);
}

/**
 * Get request method
 */
function getRequestMethod() {
    return $_SERVER['REQUEST_METHOD'];
}

/**
 * Get JSON input from request body
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }

    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate required fields
 */
function validateRequiredFields($data, $requiredFields) {
    $errors = [];

    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $errors[] = "Field '$field' is required";
        }
    }

    return $errors;
}

/**
 * Generate JWT token (simplified version)
 */
function generateToken($userId, $userType) {
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode([
        'user_id' => $userId,
        'user_type' => $userType,
        'exp' => time() + (86400 * 7) // 7 days expiration
    ]));

    $signature = hash_hmac('sha256', "$header.$payload", getSecretKey(), true);
    $signature = base64_encode($signature);

    return "$header.$payload.$signature";
}

/**
 * Verify JWT token
 */
function verifyToken($token) {
    if (!$token) {
        return false;
    }

    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return false;
    }

    list($header, $payload, $signature) = $parts;

    $validSignature = base64_encode(hash_hmac('sha256', "$header.$payload", getSecretKey(), true));

    if ($signature !== $validSignature) {
        return false;
    }

    $payloadData = json_decode(base64_decode($payload), true);

    // Check expiration
    if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
        return false;
    }

    return $payloadData;
}

/**
 * Get secret key for JWT
 */
function getSecretKey() {
    // In production, this should be in environment variables
    return 'your-secret-key-change-this-in-production';
}

/**
 * Get authorization header
 */
function getAuthorizationHeader() {
    $headers = null;

    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        $requestHeaders = array_combine(
            array_map('ucwords', array_keys($requestHeaders)),
            array_values($requestHeaders)
        );

        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }

    return $headers;
}

/**
 * Get bearer token from authorization header
 */
function getBearerToken() {
    $headers = getAuthorizationHeader();

    if (!empty($headers)) {
        if (preg_match('/Bearer\s+(.*)$/i', $headers, $matches)) {
            return $matches[1];
        }
    }

    return null;
}

/**
 * Authenticate user from token
 */
function authenticateUser() {
    $token = getBearerToken();

    if (!$token) {
        sendError('Authorization token required', 401);
    }

    $payload = verifyToken($token);

    if (!$payload) {
        sendError('Invalid or expired token', 401);
    }

    return $payload;
}

/**
 * Check if user has required role
 */
function requireRole($requiredRoles) {
    $user = authenticateUser();

    if (!is_array($requiredRoles)) {
        $requiredRoles = [$requiredRoles];
    }

    if (!in_array($user['user_type'], $requiredRoles)) {
        sendError('Insufficient permissions', 403);
    }

    return $user;
}

/**
 * Enable CORS
 */
function enableCORS() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Access-Control-Max-Age: 86400');

    if (getRequestMethod() === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

/**
 * Format date for display
 */
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Format time for display
 */
function formatTime($time) {
    return date('H:i', strtotime($time));
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * Calculate age from birth date
 */
function calculateAge($birthDate) {
    $birth = new DateTime($birthDate);
    $today = new DateTime();
    $age = $today->diff($birth);
    return $age->y;
}
