<?php
/**
 * Delete School API (Soft Delete)
 * DELETE /api/schools/delete.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/School.php';
require_once __DIR__ . '/../../utils/helpers.php';

enableCORS();

if (getRequestMethod() !== 'DELETE') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user - only admins
    $currentUser = requireRole('admin');

    $input = getJsonInput();

    if (!isset($input['id'])) {
        sendError('School ID is required', 400);
    }

    $schoolId = $input['id'];

    $schoolModel = new School();
    $school = $schoolModel->getById($schoolId);

    if (!$school) {
        sendError('School not found', 404);
    }

    // Only the school admin can delete
    if ($school['admin_id'] != $currentUser['user_id']) {
        sendError('Insufficient permissions', 403);
    }

    $schoolModel->delete($schoolId);

    sendSuccess('School deleted successfully');

} catch (Exception $e) {
    sendError('Failed to delete school: ' . $e->getMessage(), 500);
}
