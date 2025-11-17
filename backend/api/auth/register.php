<?php
/**
 * User Registration API
 * POST /api/auth/register.php
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
    $requiredFields = ['email', 'password', 'user_type', 'first_name', 'last_name', 'birth_date'];
    $errors = validateRequiredFields($input, $requiredFields);

    if (!empty($errors)) {
        sendError('Validation failed', 400, $errors);
    }

    // Sanitize input
    $data = sanitizeInput($input);

    // Additional validations
    if (!validateEmail($data['email'])) {
        sendError('Invalid email format', 400);
    }

    if (!in_array($data['user_type'], ['admin', 'teacher', 'student'])) {
        sendError('Invalid user type. Must be: admin, teacher, or student', 400);
    }

    if (!validateDate($data['birth_date'])) {
        sendError('Invalid birth date format. Use YYYY-MM-DD', 400);
    }

    if (strlen($data['password']) < 6) {
        sendError('Password must be at least 6 characters long', 400);
    }

    // Check if email already exists
    $userModel = new User();
    $existingUser = $userModel->getByEmail($data['email']);

    if ($existingUser) {
        sendError('Email already registered', 400);
    }

    // Create user
    $result = $userModel->create($data);

    // Get created user
    $user = $userModel->getById($result['id']);

    // Generate token
    $token = generateToken($user['id'], $user['user_type']);

    sendSuccess('User registered successfully', [
        'user' => $user,
        'token' => $token
    ], 201);

} catch (Exception $e) {
    sendError('Registration failed: ' . $e->getMessage(), 500);
}
