<?php
/**
 * Delete Course API (Soft Delete)
 * DELETE /api/courses/delete.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Course.php';
require_once __DIR__ . '/../../utils/helpers.php';

enableCORS();

if (getRequestMethod() !== 'DELETE') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user - only teachers
    $currentUser = requireRole('teacher');

    $input = getJsonInput();

    if (!isset($input['id'])) {
        sendError('Course ID is required', 400);
    }

    $courseId = $input['id'];

    $courseModel = new Course();
    $course = $courseModel->getById($courseId);

    if (!$course) {
        sendError('Course not found', 404);
    }

    // Only the course teacher can delete
    if ($course['teacher_id'] != $currentUser['user_id']) {
        sendError('Insufficient permissions', 403);
    }

    $courseModel->delete($courseId);

    sendSuccess('Course deleted successfully');

} catch (Exception $e) {
    sendError('Failed to delete course: ' . $e->getMessage(), 500);
}
