<?php
/**
 * User Login API
 * POST /api/auth/login.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../utils/helpers.php';

enableCORS();

if (getRequestMethod() !== 'POST') {
    sendError('Method not allowed', 405);
}

try {
    $input = getJsonInput();

    // Validate required fields
    $requiredFields = ['email', 'password'];
    $errors = validateRequiredFields($input, $requiredFields);

    if (!empty($errors)) {
        sendError('Validation failed', 400, $errors);
    }

    $email = sanitizeInput($input['email']);
    $password = $input['password']; // Don't sanitize password

    if (!validateEmail($email)) {
        sendError('Invalid email format', 400);
    }

    // Authenticate user
    $userModel = new User();
    $user = $userModel->authenticate($email, $password);

    if (!$user) {
        sendError('Invalid email or password', 401);
    }

    // Generate token
    $token = generateToken($user['id'], $user['user_type']);

    sendSuccess('Login successful', [
        'user' => $user,
        'token' => $token
    ]);

} catch (Exception $e) {
    sendError('Login failed: ' . $e->getMessage(), 500);
}
