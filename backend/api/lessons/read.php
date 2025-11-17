<?php
/**
 * Get Lesson(s) API
 * GET /api/lessons/read.php?id=1
 * GET /api/lessons/read.php?teacher_id=5
 * GET /api/lessons/read.php?student_id=10
 * GET /api/lessons/read.php?course_id=3
 * GET /api/lessons/read.php?student_id=10&next=true (get next lesson)
 * GET /api/lessons/read.php?student_id=10&history=true (get lesson history)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Lesson.php';
require_once __DIR__ . '/../../utils/helpers.php';

enableCORS();

if (getRequestMethod() !== 'GET') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user
    $currentUser = authenticateUser();

    $lessonModel = new Lesson();

    // Get by ID
    if (isset($_GET['id'])) {
        $lesson = $lessonModel->getById($_GET['id']);

        if (!$lesson) {
            sendError('Lesson not found', 404);
        }

        // Permission check
        if ($currentUser['user_type'] === 'student' && $lesson['student_id'] != $currentUser['user_id']) {
            sendError('Insufficient permissions', 403);
        }

        if ($currentUser['user_type'] === 'teacher' && $lesson['teacher_id'] != $currentUser['user_id']) {
            sendError('Insufficient permissions', 403);
        }

        // Students cannot see private notes
        if ($currentUser['user_type'] === 'student') {
            unset($lesson['private_notes']);
        }

        sendSuccess('Lesson retrieved successfully', $lesson);
    }

    // Get next lesson for student
    if (isset($_GET['student_id']) && isset($_GET['next'])) {
        // Permission check
        if ($currentUser['user_type'] === 'student' && $_GET['student_id'] != $currentUser['user_id']) {
            sendError('Insufficient permissions', 403);
        }

        $nextLesson = $lessonModel->getNextLesson($_GET['student_id']);

        if ($nextLesson && $currentUser['user_type'] === 'student') {
            unset($nextLesson['private_notes']);
        }

        sendSuccess('Next lesson retrieved successfully', $nextLesson);
    }

    // Get lesson history for student
    if (isset($_GET['student_id']) && isset($_GET['history'])) {
        // Permission check
        if ($currentUser['user_type'] === 'student' && $_GET['student_id'] != $currentUser['user_id']) {
            sendError('Insufficient permissions', 403);
        }

        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        $history = $lessonModel->getHistory($_GET['student_id'], $limit);

        // Remove private notes for students
        if ($currentUser['user_type'] === 'student') {
            foreach ($history as &$lesson) {
                unset($lesson['private_notes']);
            }
        }

        sendSuccess('Lesson history retrieved successfully', $history);
    }

    // Get lessons by filters
    $filters = [];

    if (isset($_GET['teacher_id'])) {
        $filters['teacher_id'] = $_GET['teacher_id'];
    }

    if (isset($_GET['student_id'])) {
        $filters['student_id'] = $_GET['student_id'];
    }

    if (isset($_GET['course_id'])) {
        $filters['course_id'] = $_GET['course_id'];
    }

    if (isset($_GET['date_from'])) {
        $filters['date_from'] = $_GET['date_from'];
    }

    if (isset($_GET['date_to'])) {
        $filters['date_to'] = $_GET['date_to'];
    }

    if (isset($_GET['status'])) {
        $filters['status'] = $_GET['status'];
    }

    if (empty($filters)) {
        sendError('Please provide at least one filter parameter', 400);
    }

    // Permission check for students
    if ($currentUser['user_type'] === 'student' &&
        (!isset($filters['student_id']) || $filters['student_id'] != $currentUser['user_id'])) {
        sendError('Insufficient permissions', 403);
    }

    $lessons = $lessonModel->getByFilters($filters);

    // Remove private notes for students
    if ($currentUser['user_type'] === 'student') {
        foreach ($lessons as &$lesson) {
            unset($lesson['private_notes']);
        }
    }

    sendSuccess('Lessons retrieved successfully', $lessons);

} catch (Exception $e) {
    sendError('Failed to retrieve lessons: ' . $e->getMessage(), 500);
}
