<?php
/**
 * Approve Enrollment Request API
 * POST /api/enrollments/approve.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Enrollment.php';
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

    if (!isset($input['request_id'])) {
        sendError('Request ID is required', 400);
    }

    $requestId = $input['request_id'];

    // Get request
    $enrollmentModel = new Enrollment();
    $request = $enrollmentModel->getEnrollmentRequestById($requestId);

    if (!$request) {
        sendError('Enrollment request not found', 404);
    }

    // Permission check
    if ($request['teacher_id'] != $currentUser['user_id']) {
        sendError('Insufficient permissions', 403);
    }

    // Approve request
    $enrollmentModel->approveEnrollmentRequest($requestId);

    // Get teacher details
    $userModel = new User();
    $teacher = $userModel->getById($currentUser['user_id']);
    $teacherName = $teacher['first_name'] . ' ' . $teacher['last_name'];

    // Send notification to student
    EmailService::notifyEnrollmentApproved($request['student_id'], $teacherName, $request['course_name']);

    sendSuccess('Enrollment request approved successfully');

} catch (Exception $e) {
    sendError('Failed to approve enrollment request: ' . $e->getMessage(), 500);
}
