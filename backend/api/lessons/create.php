<?php
/**
 * Create Lesson API
 * POST /api/lessons/create.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Lesson.php';
require_once __DIR__ . '/../../models/Course.php';
require_once __DIR__ . '/../../utils/helpers.php';
require_once __DIR__ . '/../../utils/email.php';

enableCORS();

if (getRequestMethod() !== 'POST') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user - only teachers and admins
    $currentUser = requireRole(['teacher', 'admin']);

    $input = getJsonInput();

    // Validate required fields
    $requiredFields = ['course_id', 'student_id', 'lesson_date', 'start_time', 'end_time'];
    $errors = validateRequiredFields($input, $requiredFields);

    if (!empty($errors)) {
        sendError('Validation failed', 400, $errors);
    }

    // Validate date and time formats
    if (!validateDate($input['lesson_date'])) {
        sendError('Invalid date format. Use YYYY-MM-DD', 400);
    }

    if (!validateTime($input['start_time']) || !validateTime($input['end_time'])) {
        sendError('Invalid time format. Use HH:MM or HH:MM:SS', 400);
    }

    // Check if student is enrolled in course
    $courseModel = new Course();
    if (!$courseModel->isStudentEnrolled($input['course_id'], $input['student_id'])) {
        sendError('Student is not enrolled in this course', 400);
    }

    // Get course and verify teacher
    $course = $courseModel->getById($input['course_id']);
    if (!$course) {
        sendError('Course not found', 404);
    }

    // For teachers, verify they own the course
    if ($currentUser['user_type'] === 'teacher' && $course['teacher_id'] != $currentUser['user_id']) {
        sendError('Insufficient permissions', 403);
    }

    // Sanitize input
    $data = sanitizeInput($input);
    $data['teacher_id'] = $course['teacher_id'];

    // Check for schedule conflicts
    $lessonModel = new Lesson();
    if ($lessonModel->hasConflict($data['teacher_id'], $data['lesson_date'], $data['start_time'], $data['end_time'])) {
        sendError('Schedule conflict: Teacher already has a lesson at this time', 400);
    }

    // Create lesson
    $lessonId = $lessonModel->create($data);

    // Get created lesson
    $lesson = $lessonModel->getById($lessonId);

    // Send notification to student (if not skipped)
    if (!isset($data['skip_notification']) || $data['skip_notification'] != 1) {
        $lessonDetails = [
            'lesson_id' => $lessonId,
            'course_name' => $course['course_name'],
            'lesson_date' => $data['lesson_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'classroom' => $data['classroom'] ?? '',
            'objectives' => $data['objectives'] ?? ''
        ];
        EmailService::notifyLessonCreated($data['student_id'], $lessonDetails);
    }

    sendSuccess('Lesson created successfully', $lesson, 201);

} catch (Exception $e) {
    sendError('Failed to create lesson: ' . $e->getMessage(), 500);
}
