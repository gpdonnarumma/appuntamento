<?php
/**
 * Mark Notification as Read API
 * POST /api/notifications/mark_read.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/helpers.php';

enableCORS();

if (getRequestMethod() !== 'POST') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user
    $currentUser = authenticateUser();

    $input = getJsonInput();

    $db = getDB();

    // Mark single notification as read
    if (isset($input['notification_id'])) {
        $notificationId = $input['notification_id'];

        // Verify ownership
        $stmt = $db->prepare("SELECT user_id FROM notifications WHERE id = ?");
        $stmt->execute([$notificationId]);
        $notification = $stmt->fetch();

        if (!$notification) {
            sendError('Notification not found', 404);
        }

        if ($notification['user_id'] != $currentUser['user_id']) {
            sendError('Insufficient permissions', 403);
        }

        // Mark as read
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
        $stmt->execute([$notificationId]);

        sendSuccess('Notification marked as read');
    }

    // Mark all notifications as read
    if (isset($input['mark_all']) && $input['mark_all'] === true) {
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$currentUser['user_id']]);

        sendSuccess('All notifications marked as read');
    }

    sendError('Please provide notification_id or mark_all parameter', 400);

} catch (Exception $e) {
    sendError('Failed to mark notification as read: ' . $e->getMessage(), 500);
}
