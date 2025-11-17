<?php
/**
 * Get User(s) API
 * GET /api/users/read.php
 * GET /api/users/read.php?id=1
 * GET /api/users/read.php?unique_id=ABC123
 * GET /api/users/read.php?type=teacher&search=john
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../utils/helpers.php';

enableCORS();

if (getRequestMethod() !== 'GET') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user
    $currentUser = authenticateUser();

    $userModel = new User();

    // Get by ID
    if (isset($_GET['id'])) {
        $user = $userModel->getById($_GET['id']);

        if (!$user) {
            sendError('User not found', 404);
        }

        // If getting own profile, include preferences for students
        if ($currentUser['user_id'] == $user['id'] && $user['user_type'] === 'student') {
            $user['preferences'] = $userModel->getStudentPreferences($user['id']);
        }

        sendSuccess('User retrieved successfully', $user);
    }

    // Get by unique ID
    if (isset($_GET['unique_id'])) {
        $user = $userModel->getByUniqueId($_GET['unique_id']);

        if (!$user) {
            sendError('User not found', 404);
        }

        sendSuccess('User retrieved successfully', $user);
    }

    // Search users
    $userType = isset($_GET['type']) ? $_GET['type'] : null;
    $searchQuery = isset($_GET['search']) ? $_GET['search'] : null;

    $users = $userModel->search($userType, $searchQuery);

    sendSuccess('Users retrieved successfully', $users);

} catch (Exception $e) {
    sendError('Failed to retrieve users: ' . $e->getMessage(), 500);
}
