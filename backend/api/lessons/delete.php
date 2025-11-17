<?php
/**
 * Delete/Cancel Lesson API
 * DELETE /api/lessons/delete.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Lesson.php';
require_once __DIR__ . '/../../utils/helpers.php';
require_once __DIR__ . '/../../utils/email.php';

enableCORS();

if (getRequestMethod() !== 'DELETE') {
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

    // Delete lesson (and optionally recurring lessons)
    $deleteRecurring = isset($input['delete_recurring']) ? $input['delete_recurring'] : false;
    $skipNotification = isset($input['skip_notification']) ? $input['skip_notification'] : false;

    $lessonModel->delete($lessonId, $deleteRecurring, $skipNotification);

    // Send notifications
    if (!$skipNotification) {
        // Notify student about cancellation
        $lessonDetails = [
            'lesson_id' => $lessonId,
            'course_name' => $lesson['course_name'],
            'lesson_date' => $lesson['lesson_date'],
            'start_time' => $lesson['start_time'],
            'end_time' => $lesson['end_time']
        ];
        EmailService::notifyLessonCancelled($lesson['student_id'], $lessonDetails);

        // Notify other students about free slot (if option enabled and not marked as "non assegnare")
        if (!isset($input['no_assign']) || $input['no_assign'] != 1) {
            EmailService::notifyFreeSlot(
                $lesson['course_id'],
                $lesson['teacher_id'],
                $lesson['lesson_date'],
                $lesson['start_time'],
                $lesson['end_time']
            );
        }
    }

    sendSuccess('Lesson cancelled successfully');

} catch (Exception $e) {
    sendError('Failed to cancel lesson: ' . $e->getMessage(), 500);
}
