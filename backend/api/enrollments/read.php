<?php
/**
 * Get Enrollment Requests API
 * GET /api/enrollments/read.php?teacher_id=5
 * GET /api/enrollments/read.php?teacher_id=5&pending=true
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Enrollment.php';
require_once __DIR__ . '/../../utils/helpers.php';

enableCORS();

if (getRequestMethod() !== 'GET') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user
    $currentUser = authenticateUser();

    $enrollmentModel = new Enrollment();

    // Get requests for teacher
    if (isset($_GET['teacher_id'])) {
        $teacherId = $_GET['teacher_id'];

        // Permission check for teachers
        if ($currentUser['user_type'] === 'teacher' && $teacherId != $currentUser['user_id']) {
            sendError('Insufficient permissions', 403);
        }

        if (isset($_GET['pending']) && $_GET['pending'] === 'true') {
            $requests = $enrollmentModel->getPendingRequestsForTeacher($teacherId);
        } else {
            $requests = $enrollmentModel->getAllRequestsForTeacher($teacherId);
        }

        sendSuccess('Enrollment requests retrieved successfully', $requests);
    }

    sendError('Please provide teacher_id parameter', 400);

} catch (Exception $e) {
    sendError('Failed to retrieve enrollment requests: ' . $e->getMessage(), 500);
}
