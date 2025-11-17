<?php
/**
 * Get Notifications API
 * GET /api/notifications/read.php
 * GET /api/notifications/read.php?unread=true
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/helpers.php';

enableCORS();

if (getRequestMethod() !== 'GET') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user
    $currentUser = authenticateUser();

    $db = getDB();

    // Build query
    $sql = "
        SELECT *
        FROM notifications
        WHERE user_id = ?
    ";

    $params = [$currentUser['user_id']];

    // Filter by unread if requested
    if (isset($_GET['unread']) && $_GET['unread'] === 'true') {
        $sql .= " AND is_read = 0";
    }

    $sql .= " ORDER BY created_at DESC";

    // Limit results
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $sql .= " LIMIT ?";
    $params[] = $limit;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $notifications = $stmt->fetchAll();

    // Get unread count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$currentUser['user_id']]);
    $result = $stmt->fetch();
    $unreadCount = $result['count'];

    sendSuccess('Notifications retrieved successfully', [
        'notifications' => $notifications,
        'unread_count' => $unreadCount
    ]);

} catch (Exception $e) {
    sendError('Failed to retrieve notifications: ' . $e->getMessage(), 500);
}
