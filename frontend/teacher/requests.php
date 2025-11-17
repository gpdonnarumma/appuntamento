<?php
/**
 * Teacher Enrollment Requests Management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_TEACHER);

$pageTitle = 'Richieste Iscrizione';
$user = getCurrentUser();

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['request_id'])) {
        $requestId = $_POST['request_id'];

        if ($_POST['action'] === 'approve') {
            $result = apiApproveEnrollment($requestId);

            if ($result['success']) {
                setSuccessMessage('Richiesta approvata con successo!');
            } else {
                setErrorMessage($result['message']);
            }
        } elseif ($_POST['action'] === 'reject') {
            $result = apiRejectEnrollment($requestId);

            if ($result['success']) {
                setSuccessMessage('Richiesta rifiutata.');
            } else {
                setErrorMessage($result['message']);
            }
        }

        header('Location: ' . baseUrl('teacher/requests.php'));
        exit;
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'pending';

// Get enrollment requests
if ($filter === 'all') {
    $requestsResult = apiGetEnrollmentRequests($user['id'], false);
} else {
    $requestsResult = apiGetEnrollmentRequests($user['id'], true);
}

$requests = $requestsResult['success'] ? $requestsResult['data'] : [];

// Filter by status if needed
if ($filter !== 'all' && $filter !== 'pending') {
    $requests = array_filter($requests, function($req) use ($filter) {
        return $req['status'] === $filter;
    });
}

// Get teacher's courses for reference
$coursesResult = apiGetCourse(null, $user['id']);
$courses = $coursesResult['success'] ? $coursesResult['data'] : [];

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">📬 Richieste Iscrizione</h1>
    <p class="page-subtitle">Gestisci le richieste di iscrizione ai tuoi corsi</p>
</div>

<!-- Filter Tabs -->
<div class="card">
    <div class="card-body" style="padding: 1rem;">
        <div class="d-flex gap-2">
            <a href="<?php echo baseUrl('teacher/requests.php?filter=pending'); ?>"
               class="btn <?php echo $filter === 'pending' ? 'btn-teacher' : 'btn-outline-secondary'; ?>">
                ⏳ Pendenti
                <?php
                $pendingCount = count(array_filter($requests, function($r) { return $r['status'] === 'pending'; }));
                if ($pendingCount > 0) echo "($pendingCount)";
                ?>
            </a>
            <a href="<?php echo baseUrl('teacher/requests.php?filter=approved'); ?>"
               class="btn <?php echo $filter === 'approved' ? 'btn-teacher' : 'btn-outline-secondary'; ?>">
                ✅ Approvate
            </a>
            <a href="<?php echo baseUrl('teacher/requests.php?filter=rejected'); ?>"
               class="btn <?php echo $filter === 'rejected' ? 'btn-teacher' : 'btn-outline-secondary'; ?>">
                ❌ Rifiutate
            </a>
            <a href="<?php echo baseUrl('teacher/requests.php?filter=all'); ?>"
               class="btn <?php echo $filter === 'all' ? 'btn-teacher' : 'btn-outline-secondary'; ?>">
                📋 Tutte
            </a>
        </div>
    </div>
</div>

<!-- Requests List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <?php
            if ($filter === 'pending') echo '⏳ Richieste Pendenti';
            elseif ($filter === 'approved') echo '✅ Richieste Approvate';
            elseif ($filter === 'rejected') echo '❌ Richieste Rifiutate';
            else echo '📋 Tutte le Richieste';
            ?>
            (<?php echo count($requests); ?>)
        </h3>
    </div>
    <div class="card-body">
        <?php if (empty($requests)): ?>
            <div style="text-align: center; padding: 3rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📬</div>
                <h3>Nessuna richiesta</h3>
                <p style="color: #666;">
                    <?php if ($filter === 'pending'): ?>
                        Non ci sono richieste di iscrizione in attesa di approvazione.
                    <?php else: ?>
                        Nessuna richiesta trovata per questo filtro.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Studente</th>
                            <th>ID Studente</th>
                            <th>Email</th>
                            <th>Corso</th>
                            <th>Data Richiesta</th>
                            <th>Stato</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $request): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($request['student_first_name'] . ' ' . $request['student_last_name']); ?></strong>
                                </td>
                                <td>
                                    <code><?php echo htmlspecialchars($request['student_unique_id'] ?? ''); ?></code>
                                </td>
                                <td><?php echo htmlspecialchars($request['student_email']); ?></td>
                                <td><?php echo htmlspecialchars($request['course_name']); ?></td>
                                <td><?php echo formatDateTime($request['created_at']); ?></td>
                                <td>
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <span class="badge bg-warning text-dark">⏳ Pendente</span>
                                    <?php elseif ($request['status'] === 'approved'): ?>
                                        <span class="badge bg-success text-white">✅ Approvata</span>
                                    <?php elseif ($request['status'] === 'rejected'): ?>
                                        <span class="badge bg-danger text-white">❌ Rifiutata</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success" title="Approva">
                                                ✅ Approva
                                            </button>
                                        </form>
                                        <form method="POST" action="" style="display: inline; margin-left: 0.5rem;">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Rifiuta"
                                                    onclick="return confirm('Sei sicuro di voler rifiutare questa richiesta?');">
                                                ❌ Rifiuta
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Summary Stats -->
<?php if (!empty($courses)): ?>
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-value">
                <?php echo count(array_filter($requests, function($r) { return $r['status'] === 'pending'; })); ?>
            </div>
            <div class="stat-label">Richieste Pendenti</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">✅</div>
            <div class="stat-value">
                <?php
                $allRequests = apiGetEnrollmentRequests($user['id'], false);
                $allReqs = $allRequests['success'] ? $allRequests['data'] : [];
                echo count(array_filter($allReqs, function($r) { return $r['status'] === 'approved'; }));
                ?>
            </div>
            <div class="stat-label">Totale Approvate</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-value"><?php echo count($courses); ?></div>
            <div class="stat-label">Corsi Attivi</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">👥</div>
            <div class="stat-value">
                <?php
                $totalStudents = 0;
                foreach ($courses as $c) {
                    $totalStudents += $c['enrolled_students'] ?? 0;
                }
                echo $totalStudents;
                ?>
            </div>
            <div class="stat-label">Studenti Totali</div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
