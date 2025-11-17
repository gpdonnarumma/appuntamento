<?php
/**
 * Update Course API
 * PUT /api/courses/update.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Course.php';
require_once __DIR__ . '/../../utils/helpers.php';

enableCORS();

if (getRequestMethod() !== 'PUT') {
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

    // Only the course teacher can update
    if ($course['teacher_id'] != $currentUser['user_id']) {
        sendError('Insufficient permissions', 403);
    }

    // Sanitize and update
    $updateData = [];

    if (isset($input['course_name'])) {
        $updateData['course_name'] = sanitizeInput($input['course_name']);
    }

    if (isset($input['description'])) {
        $updateData['description'] = sanitizeInput($input['description']);
    }

    if (empty($updateData)) {
        sendError('No fields to update', 400);
    }

    $courseModel->update($courseId, $updateData);

    // Get updated course
    $updatedCourse = $courseModel->getById($courseId);

    sendSuccess('Course updated successfully', $updatedCourse);

} catch (Exception $e) {
    sendError('Failed to update course: ' . $e->getMessage(), 500);
}
