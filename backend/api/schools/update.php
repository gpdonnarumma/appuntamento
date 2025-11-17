<?php
/**
 * Update School API
 * PUT /api/schools/update.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/School.php';
require_once __DIR__ . '/../../utils/helpers.php';

enableCORS();

if (getRequestMethod() !== 'PUT') {
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

    // Only the school admin can update
    if ($school['admin_id'] != $currentUser['user_id']) {
        sendError('Insufficient permissions', 403);
    }

    // Sanitize and update
    $updateData = [];

    if (isset($input['school_name'])) {
        $updateData['school_name'] = sanitizeInput($input['school_name']);
    }

    if (isset($input['city'])) {
        $updateData['city'] = sanitizeInput($input['city']);
    }

    if (empty($updateData)) {
        sendError('No fields to update', 400);
    }

    $schoolModel->update($schoolId, $updateData);

    // Get updated school
    $updatedSchool = $schoolModel->getById($schoolId);

    sendSuccess('School updated successfully', $updatedSchool);

} catch (Exception $e) {
    sendError('Failed to update school: ' . $e->getMessage(), 500);
}
