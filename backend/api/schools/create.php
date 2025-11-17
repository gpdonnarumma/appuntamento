<?php
/**
 * Create School API
 * POST /api/schools/create.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/School.php';
require_once __DIR__ . '/../../utils/helpers.php';

enableCORS();

if (getRequestMethod() !== 'POST') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user - only admins can create schools
    $currentUser = requireRole('admin');

    $input = getJsonInput();

    // Validate required fields
    $requiredFields = ['school_name', 'city'];
    $errors = validateRequiredFields($input, $requiredFields);

    if (!empty($errors)) {
        sendError('Validation failed', 400, $errors);
    }

    // Sanitize input
    $data = sanitizeInput($input);
    $data['admin_id'] = $currentUser['user_id'];

    // Create school
    $schoolModel = new School();
    $result = $schoolModel->create($data);

    // Get created school
    $school = $schoolModel->getById($result['id']);

    sendSuccess('School created successfully', $school, 201);

} catch (Exception $e) {
    sendError('Failed to create school: ' . $e->getMessage(), 500);
}
