<?php
/**
 * Get Teacher-School Requests API
 * GET /api/teacher_requests/read.php?school_id=5
 * GET /api/teacher_requests/read.php?school_id=5&pending=true
 * GET /api/teacher_requests/read.php?teacher_id=10 (get teacher's requests)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Enrollment.php';
require_once __DIR__ . '/../../models/School.php';
require_once __DIR__ . '/../../utils/helpers.php';

enableCORS();

if (getRequestMethod() !== 'GET') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user
    $currentUser = authenticateUser();

    $enrollmentModel = new Enrollment();

    // Get requests for school (admin view)
    if (isset($_GET['school_id'])) {
        $schoolId = $_GET['school_id'];

        // Permission check - only school admin
        $schoolModel = new School();
        $school = $schoolModel->getById($schoolId);

        if (!$school) {
            sendError('School not found', 404);
        }

        if ($currentUser['user_type'] !== 'admin' || $school['admin_id'] != $currentUser['user_id']) {
            sendError('Insufficient permissions', 403);
        }

        if (isset($_GET['pending']) && $_GET['pending'] === 'true') {
            $requests = $enrollmentModel->getPendingTeacherRequestsForSchool($schoolId);
        } else {
            $requests = $enrollmentModel->getAllTeacherRequestsForSchool($schoolId);
        }

        sendSuccess('Teacher requests retrieved successfully', $requests);
    }

    // Get teacher's pending requests
    if (isset($_GET['teacher_id'])) {
        $teacherId = $_GET['teacher_id'];

        // Permission check
        if ($currentUser['user_type'] === 'teacher' && $teacherId != $currentUser['user_id']) {
            sendError('Insufficient permissions', 403);
        }

        $requests = $enrollmentModel->getTeacherPendingRequests($teacherId);
        sendSuccess('Teacher requests retrieved successfully', $requests);
    }

    sendError('Please provide school_id or teacher_id parameter', 400);

} catch (Exception $e) {
    sendError('Failed to retrieve teacher requests: ' . $e->getMessage(), 500);
}
