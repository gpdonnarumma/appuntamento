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

    // Get enrolled students for a course
    if (isset($_GET['course_id'])) {
        $courseId = $_GET['course_id'];

        // Verify teacher owns the course
        require_once __DIR__ . '/../../models/Course.php';
        $courseModel = new Course();
        $course = $courseModel->getById($courseId);

        if (!$course) {
            sendError('Course not found', 404);
        }

        if ($currentUser['user_type'] === 'teacher' && $course['teacher_id'] != $currentUser['user_id']) {
            sendError('Insufficient permissions', 403);
        }

        $enrollments = $enrollmentModel->getEnrollmentsByCourse($courseId);
        sendSuccess('Course enrollments retrieved successfully', $enrollments);
    }

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

    sendError('Please provide teacher_id or course_id parameter', 400);

} catch (Exception $e) {
    sendError('Failed to retrieve enrollment requests: ' . $e->getMessage(), 500);
}
