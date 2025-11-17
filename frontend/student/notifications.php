<?php
/**
 * Student - Notifications
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_STUDENT);

$pageTitle = 'Notifiche';
$user = getCurrentUser();

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'mark_read' && isset($_POST['notification_id'])) {
            $notificationId = $_POST['notification_id'];
            $result = apiMarkNotificationAsRead($notificationId);

            if ($result['success']) {
                setSuccessMessage('Notifica segnata come letta.');
            } else {
                setErrorMessage($result['message']);
            }

            header('Location: ' . baseUrl('student/notifications.php'));
            exit;
        } elseif ($_POST['action'] === 'mark_all_read') {
            $result = apiMarkAllNotificationsAsRead();

            if ($result['success']) {
                setSuccessMessage('Tutte le notifiche sono state segnate come lette.');
            } else {
                setErrorMessage($result['message']);
            }

            header('Location: ' . baseUrl('student/notifications.php'));
            exit;
        } elseif ($_POST['action'] === 'delete' && isset($_POST['notification_id'])) {
            $notificationId = $_POST['notification_id'];
            $result = apiDeleteNotification($notificationId);

            if ($result['success']) {
                setSuccessMessage('Notifica eliminata.');
            } else {
                setErrorMessage($result['message']);
            }

            header('Location: ' . baseUrl('student/notifications.php'));
            exit;
        }
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all'; // all, unread, read

// Get notifications
$onlyUnread = ($filter === 'unread');
$notificationsResult = apiGetNotifications($onlyUnread);
$allNotifications = $notificationsResult['success'] ? $notificationsResult['data']['notifications'] : [];
$unreadCount = $notificationsResult['success'] ? ($notificationsResult['data']['unread_count'] ?? 0) : 0;

// Apply filter
if ($filter === 'read') {
    $notifications = array_filter($allNotifications, function($n) {
        return $n['is_read'] == 1;
    });
} else {
    $notifications = $allNotifications;
}

// Sort by date (newest first)
usort($notifications, function($a, $b) {
    return strcmp($b['created_at'], $a['created_at']);
});

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">🔔 Notifiche</h1>
    <p class="page-subtitle">
        <?php if ($unreadCount > 0): ?>
            Hai <strong><?php echo $unreadCount; ?></strong> notifiche non lette
        <?php else: ?>
            Nessuna notifica non letta
        <?php endif; ?>
    </p>
</div>

<!-- Notification Controls -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <!-- Filter Buttons -->
                <div class="btn-group" role="group">
                    <a href="<?php echo baseUrl('student/notifications.php?filter=all'); ?>"
                       class="btn btn-sm <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        Tutte
                    </a>
                    <a href="<?php echo baseUrl('student/notifications.php?filter=unread'); ?>"
                       class="btn btn-sm <?php echo $filter === 'unread' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        Non Lette <?php if ($unreadCount > 0): ?><span class="badge bg-danger"><?php echo $unreadCount; ?></span><?php endif; ?>
                    </a>
                    <a href="<?php echo baseUrl('student/notifications.php?filter=read'); ?>"
                       class="btn btn-sm <?php echo $filter === 'read' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                        Lette
                    </a>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <?php if ($unreadCount > 0): ?>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="action" value="mark_all_read">
                        <button type="submit" class="btn btn-sm btn-outline-primary">
                            ✓ Segna Tutte come Lette
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Notifications List -->
<div class="card">
    <div class="card-body">
        <?php if (empty($notifications)): ?>
            <div style="text-align: center; padding: 3rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">🔔</div>
                <h3>Nessuna notifica</h3>
                <p style="color: #666; margin: 1rem 0;">
                    <?php if ($filter === 'unread'): ?>
                        Non hai notifiche non lette.
                    <?php elseif ($filter === 'read'): ?>
                        Non hai notifiche lette.
                    <?php else: ?>
                        Non hai ancora ricevuto notifiche.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notifications as $notification): ?>
                    <div class="list-group-item <?php echo $notification['is_read'] == 0 ? 'bg-light' : ''; ?>">
                        <div class="d-flex w-100 justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-2">
                                    <?php
                                    // Determine notification icon based on type
                                    $icon = '📢';
                                    $type = $notification['type'] ?? '';
                                    if (strpos($type, 'lesson') !== false) {
                                        $icon = '📅';
                                    } elseif (strpos($type, 'enrollment') !== false || strpos($type, 'course') !== false) {
                                        $icon = '📚';
                                    } elseif (strpos($type, 'message') !== false) {
                                        $icon = '💬';
                                    } elseif (strpos($type, 'reminder') !== false) {
                                        $icon = '⏰';
                                    }
                                    ?>
                                    <span style="font-size: 1.5rem; margin-right: 0.5rem;"><?php echo $icon; ?></span>
                                    <h5 class="mb-0">
                                        <?php echo htmlspecialchars($notification['title']); ?>
                                        <?php if ($notification['is_read'] == 0): ?>
                                            <span class="badge bg-primary ms-2">Nuovo</span>
                                        <?php endif; ?>
                                    </h5>
                                </div>
                                <p class="mb-2" style="color: #666;">
                                    <?php echo nl2br(htmlspecialchars($notification['message'])); ?>
                                </p>
                                <small class="text-muted">
                                    🕐 <?php echo formatDateTime($notification['created_at']); ?>
                                </small>
                            </div>
                            <div class="ms-3" style="min-width: 120px; text-align: right;">
                                <?php if ($notification['is_read'] == 0): ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Segna come letta">
                                            ✓ Letta
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Vuoi eliminare questa notifica?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Elimina">
                                        🗑️
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Notification Statistics -->
<?php if (!empty($allNotifications)): ?>
    <div class="dashboard-stats mt-3">
        <div class="stat-card">
            <div class="stat-icon">📬</div>
            <div class="stat-value"><?php echo count($allNotifications); ?></div>
            <div class="stat-label">Totali</div>
        </div>

        <div class="stat-card secondary">
            <div class="stat-icon">🆕</div>
            <div class="stat-value"><?php echo $unreadCount; ?></div>
            <div class="stat-label">Non Lette</div>
        </div>

        <div class="stat-card accent">
            <div class="stat-icon">✅</div>
            <div class="stat-value"><?php echo count($allNotifications) - $unreadCount; ?></div>
            <div class="stat-label">Lette</div>
        </div>
    </div>
<?php endif; ?>

<!-- Info Card -->
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">ℹ️ Informazioni sulle Notifiche</h3>
    </div>
    <div class="card-body">
        <p>Riceverai notifiche per:</p>
        <ul>
            <li>📚 <strong>Nuove iscrizioni:</strong> Quando sei stato iscritto a un nuovo corso</li>
            <li>📅 <strong>Nuove lezioni:</strong> Quando viene programmata una nuova lezione</li>
            <li>✏️ <strong>Modifiche lezioni:</strong> Quando una lezione viene modificata o cancellata</li>
            <li>⏰ <strong>Promemoria:</strong> Promemoria automatici prima delle lezioni</li>
            <li>💬 <strong>Messaggi:</strong> Comunicazioni dai tuoi docenti</li>
        </ul>
        <p class="mb-0" style="color: #666;">
            💡 <strong>Suggerimento:</strong> Le notifiche non lette sono evidenziate con uno sfondo grigio chiaro.
        </p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
