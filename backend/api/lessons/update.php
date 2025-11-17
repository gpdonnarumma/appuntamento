<?php
/**
 * Update Lesson API
 * PUT /api/lessons/update.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Lesson.php';
require_once __DIR__ . '/../../utils/helpers.php';
require_once __DIR__ . '/../../utils/email.php';

enableCORS();

if (getRequestMethod() !== 'PUT') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user - only teachers and admins
    $currentUser = requireRole(['teacher', 'admin']);

    $input = getJsonInput();

    if (!isset($input['id'])) {
        sendError('Lesson ID is required', 400);
    }

    $lessonId = $input['id'];

    $lessonModel = new Lesson();
    $lesson = $lessonModel->getById($lessonId);

    if (!$lesson) {
        sendError('Lesson not found', 404);
    }

    // Permission check for teachers
    if ($currentUser['user_type'] === 'teacher' && $lesson['teacher_id'] != $currentUser['user_id']) {
        sendError('Insufficient permissions', 403);
    }

    // Validate date and time if provided
    if (isset($input['lesson_date']) && !validateDate($input['lesson_date'])) {
        sendError('Invalid date format. Use YYYY-MM-DD', 400);
    }

    if (isset($input['start_time']) && !validateTime($input['start_time'])) {
        sendError('Invalid time format. Use HH:MM or HH:MM:SS', 400);
    }

    if (isset($input['end_time']) && !validateTime($input['end_time'])) {
        sendError('Invalid time format. Use HH:MM or HH:MM:SS', 400);
    }

    // Check for schedule conflicts if date/time changed
    if (isset($input['lesson_date']) || isset($input['start_time']) || isset($input['end_time'])) {
        $checkDate = isset($input['lesson_date']) ? $input['lesson_date'] : $lesson['lesson_date'];
        $checkStart = isset($input['start_time']) ? $input['start_time'] : $lesson['start_time'];
        $checkEnd = isset($input['end_time']) ? $input['end_time'] : $lesson['end_time'];

        if ($lessonModel->hasConflict($lesson['teacher_id'], $checkDate, $checkStart, $checkEnd, $lessonId)) {
            sendError('Schedule conflict: Teacher already has a lesson at this time', 400);
        }
    }

    // Sanitize and update
    $updateData = [];

    if (isset($input['lesson_date'])) {
        $updateData['lesson_date'] = $input['lesson_date'];
    }

    if (isset($input['start_time'])) {
        $updateData['start_time'] = $input['start_time'];
    }

    if (isset($input['end_time'])) {
        $updateData['end_time'] = $input['end_time'];
    }

    if (isset($input['classroom'])) {
        $updateData['classroom'] = sanitizeInput($input['classroom']);
    }

    if (isset($input['private_notes'])) {
        $updateData['private_notes'] = sanitizeInput($input['private_notes']);
    }

    if (isset($input['objectives'])) {
        $updateData['objectives'] = sanitizeInput($input['objectives']);
    }

    if (isset($input['status'])) {
        $updateData['status'] = $input['status'];
    }

    if (empty($updateData)) {
        sendError('No fields to update', 400);
    }

    // Update lesson (and optionally recurring lessons)
    $updateRecurring = isset($input['update_recurring']) ? $input['update_recurring'] : false;
    $lessonModel->update($lessonId, $updateData, $updateRecurring);

    // Get updated lesson
    $updatedLesson = $lessonModel->getById($lessonId);

    // Send notification to student (if not skipped and date/time changed)
    if (!isset($input['skip_notification']) || $input['skip_notification'] != 1) {
        if (isset($input['lesson_date']) || isset($input['start_time']) || isset($input['end_time'])) {
            $lessonDetails = [
                'lesson_id' => $lessonId,
                'course_name' => $lesson['course_name'],
                'lesson_date' => $updatedLesson['lesson_date'],
                'start_time' => $updatedLesson['start_time'],
                'end_time' => $updatedLesson['end_time'],
                'classroom' => $updatedLesson['classroom'] ?? ''
            ];
            EmailService::notifyLessonModified($lesson['student_id'], $lessonDetails);
        }
    }

    sendSuccess('Lesson updated successfully', $updatedLesson);

} catch (Exception $e) {
    sendError('Failed to update lesson: ' . $e->getMessage(), 500);
}
