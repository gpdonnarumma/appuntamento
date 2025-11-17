<?php
/**
 * Create Course API
 * POST /api/courses/create.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Course.php';
require_once __DIR__ . '/../../utils/helpers.php';

enableCORS();

if (getRequestMethod() !== 'POST') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user - only teachers can create courses
    $currentUser = requireRole('teacher');

    $input = getJsonInput();

    // Validate required fields
    $requiredFields = ['course_name'];
    $errors = validateRequiredFields($input, $requiredFields);

    if (!empty($errors)) {
        sendError('Validation failed', 400, $errors);
    }

    // Sanitize input
    $data = sanitizeInput($input);
    $data['teacher_id'] = $currentUser['user_id'];

    // Create course
    $courseModel = new Course();
    $courseId = $courseModel->create($data);

    // Get created course
    $course = $courseModel->getById($courseId);

    sendSuccess('Course created successfully', $course, 201);

} catch (Exception $e) {
    sendError('Failed to create course: ' . $e->getMessage(), 500);
}
