<?php
/**
 * Delete User API (Soft Delete)
 * DELETE /api/users/delete.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../utils/helpers.php';

enableCORS();

if (getRequestMethod() !== 'DELETE') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user
    $currentUser = authenticateUser();

    $input = getJsonInput();

    if (!isset($input['id'])) {
        sendError('User ID is required', 400);
    }

    $userId = $input['id'];

    $userModel = new User();
    $user = $userModel->getById($userId);

    if (!$user) {
        sendError('User not found', 404);
    }

    // Permission check: only admin or the user themselves can delete
    if ($currentUser['user_type'] !== 'admin' && $currentUser['user_id'] != $userId) {
        sendError('Insufficient permissions', 403);
    }

    $userModel->delete($userId);

    sendSuccess('User deleted successfully');

} catch (Exception $e) {
    sendError('Failed to delete user: ' . $e->getMessage(), 500);
}
