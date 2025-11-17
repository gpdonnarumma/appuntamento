<?php
/**
 * Get School(s) API
 * GET /api/schools/read.php
 * GET /api/schools/read.php?id=1
 * GET /api/schools/read.php?unique_id=SC1234ABCD
 * GET /api/schools/read.php?search=rome
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/School.php';
require_once __DIR__ . '/../../utils/helpers.php';

enableCORS();

if (getRequestMethod() !== 'GET') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user
    $currentUser = authenticateUser();

    $schoolModel = new School();

    // Get schools for a teacher
    if (isset($_GET['teacher_id'])) {
        $teacherId = $_GET['teacher_id'];

        // Permission check
        if ($currentUser['user_type'] === 'teacher' && $teacherId != $currentUser['user_id']) {
            sendError('Insufficient permissions', 403);
        }

        $schools = $schoolModel->getTeacherSchools($teacherId);
        sendSuccess('Schools retrieved successfully', $schools);
    }

    // Get by ID
    if (isset($_GET['id'])) {
        $school = $schoolModel->getById($_GET['id']);

        if (!$school) {
            sendError('School not found', 404);
        }

        // Get additional data for admins
        if ($currentUser['user_type'] === 'admin' && $school['admin_id'] == $currentUser['user_id']) {
            $school['teachers'] = $schoolModel->getTeachers($school['id']);
            $school['students'] = $schoolModel->getStudents($school['id']);
        }

        sendSuccess('School retrieved successfully', $school);
    }

    // Get by unique ID
    if (isset($_GET['unique_id'])) {
        $school = $schoolModel->getByUniqueId($_GET['unique_id']);

        if (!$school) {
            sendError('School not found', 404);
        }

        sendSuccess('School retrieved successfully', $school);
    }

    // Search schools
    $searchQuery = isset($_GET['search']) ? $_GET['search'] : null;
    $schools = $schoolModel->search($searchQuery);

    sendSuccess('Schools retrieved successfully', $schools);

} catch (Exception $e) {
    sendError('Failed to retrieve schools: ' . $e->getMessage(), 500);
}
