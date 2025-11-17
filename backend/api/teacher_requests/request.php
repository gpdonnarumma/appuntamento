<?php
/**
 * Create Teacher-School Request API (Teacher -> School)
 * POST /api/teacher_requests/request.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Enrollment.php';
require_once __DIR__ . '/../../models/School.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../utils/helpers.php';
require_once __DIR__ . '/../../utils/email.php';

enableCORS();

if (getRequestMethod() !== 'POST') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user - only teachers
    $currentUser = requireRole('teacher');

    $input = getJsonInput();

    // Validate required fields
    $requiredFields = ['school_id'];
    $errors = validateRequiredFields($input, $requiredFields);

    if (!empty($errors)) {
        sendError('Validation failed', 400, $errors);
    }

    $schoolId = $input['school_id'];

    // Get school
    $schoolModel = new School();
    $school = $schoolModel->getById($schoolId);

    if (!$school) {
        sendError('School not found', 404);
    }

    // Create teacher-school request
    $enrollmentModel = new Enrollment();
    $requestId = $enrollmentModel->createTeacherSchoolRequest(
        $currentUser['user_id'],
        $schoolId
    );

    // Get teacher details
    $userModel = new User();
    $teacher = $userModel->getById($currentUser['user_id']);
    $teacherName = $teacher['first_name'] . ' ' . $teacher['last_name'];

    // Send notification to school admin
    EmailService::notifyTeacherSchoolRequest(
        $school['admin_id'],
        $teacherName,
        $teacher['email'],
        $school['school_name'],
        $requestId
    );

    sendSuccess('Request sent to school successfully', [
        'request_id' => $requestId
    ], 201);

} catch (Exception $e) {
    sendError('Failed to create teacher-school request: ' . $e->getMessage(), 500);
}
