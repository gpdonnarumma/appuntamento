<?php
/**
 * Get Course(s) API
 * GET /api/courses/read.php?id=1
 * GET /api/courses/read.php?teacher_id=5
 * GET /api/courses/read.php?student_id=10
 * GET /api/courses/read.php?teacher_unique_id=ABC123
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Course.php';
require_once __DIR__ . '/../../utils/helpers.php';

enableCORS();

if (getRequestMethod() !== 'GET') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user
    $currentUser = authenticateUser();

    $courseModel = new Course();

    // Get by ID
    if (isset($_GET['id'])) {
        $course = $courseModel->getById($_GET['id']);

        if (!$course) {
            sendError('Course not found', 404);
        }

        // Get enrolled students
        $course['students'] = $courseModel->getEnrolledStudents($course['id']);

        sendSuccess('Course retrieved successfully', $course);
    }

    // Get courses by teacher
    if (isset($_GET['teacher_id'])) {
        $courses = $courseModel->getByTeacher($_GET['teacher_id']);
        sendSuccess('Courses retrieved successfully', $courses);
    }

    // Get courses by student
    if (isset($_GET['student_id'])) {
        $courses = $courseModel->getByStudent($_GET['student_id']);
        sendSuccess('Courses retrieved successfully', $courses);
    }

    // Search courses by teacher unique ID
    if (isset($_GET['teacher_unique_id'])) {
        $courses = $courseModel->searchByTeacherUniqueId($_GET['teacher_unique_id']);
        sendSuccess('Courses retrieved successfully', $courses);
    }

    // Get available instruments
    if (isset($_GET['instruments'])) {
        $instruments = $courseModel->getAvailableInstruments();
        sendSuccess('Instruments retrieved successfully', $instruments);
    }

    sendError('Please provide id, teacher_id, student_id, teacher_unique_id, or instruments parameter', 400);

} catch (Exception $e) {
    sendError('Failed to retrieve courses: ' . $e->getMessage(), 500);
}
