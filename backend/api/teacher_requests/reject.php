<?php
/**
 * Reject Teacher-School Request API
 * POST /api/teacher_requests/reject.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Enrollment.php';
require_once __DIR__ . '/../../models/School.php';
require_once __DIR__ . '/../../utils/helpers.php';

enableCORS();

if (getRequestMethod() !== 'POST') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user - only admins
    $currentUser = requireRole('admin');

    $input = getJsonInput();

    if (!isset($input['request_id'])) {
        sendError('Request ID is required', 400);
    }

    $requestId = $input['request_id'];

    // Get request
    $enrollmentModel = new Enrollment();
    $request = $enrollmentModel->getTeacherSchoolRequestById($requestId);

    if (!$request) {
        sendError('Teacher-school request not found', 404);
    }

    // Permission check - only school admin
    if ($request['admin_id'] != $currentUser['user_id']) {
        sendError('Insufficient permissions', 403);
    }

    // Reject request
    $enrollmentModel->rejectTeacherSchoolRequest($requestId);

    sendSuccess('Teacher request rejected successfully');

} catch (Exception $e) {
    sendError('Failed to reject teacher request: ' . $e->getMessage(), 500);
}
