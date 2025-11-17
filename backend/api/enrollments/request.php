<?php
/**
 * Create Enrollment Request API (Student -> Course)
 * POST /api/enrollments/request.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Enrollment.php';
require_once __DIR__ . '/../../models/Course.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../utils/helpers.php';
require_once __DIR__ . '/../../utils/email.php';

enableCORS();

if (getRequestMethod() !== 'POST') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user - only students
    $currentUser = requireRole('student');

    $input = getJsonInput();

    // Validate required fields
    $requiredFields = ['course_id'];
    $errors = validateRequiredFields($input, $requiredFields);

    if (!empty($errors)) {
        sendError('Validation failed', 400, $errors);
    }

    $courseId = $input['course_id'];

    // Get course
    $courseModel = new Course();
    $course = $courseModel->getById($courseId);

    if (!$course) {
        sendError('Course not found', 404);
    }

    // Create enrollment request
    $enrollmentModel = new Enrollment();
    $requestId = $enrollmentModel->createEnrollmentRequest(
        $currentUser['user_id'],
        $courseId,
        $course['teacher_id']
    );

    // Get student details
    $userModel = new User();
    $student = $userModel->getById($currentUser['user_id']);

    // Send notification to teacher
    $studentName = $student['first_name'] . ' ' . $student['last_name'];
    EmailService::notifyEnrollmentRequest($course['teacher_id'], $studentName, $course['course_name'], $requestId);

    sendSuccess('Enrollment request sent successfully', [
        'request_id' => $requestId
    ], 201);

} catch (Exception $e) {
    sendError('Failed to create enrollment request: ' . $e->getMessage(), 500);
}
