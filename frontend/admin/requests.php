<?php
/**
 * Admin - Teacher Requests Management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_ADMIN);

$pageTitle = 'Gestione Richieste';
$user = getCurrentUser();

// Get admin's school
$schoolsResult = apiSearchSchools();
$schools = $schoolsResult['success'] ? $schoolsResult['data'] : [];

$school = null;
if (!empty($schools)) {
    foreach ($schools as $s) {
        if ($s['admin_id'] == $user['id']) {
            $school = $s;
            break;
        }
    }
}

// Handle request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $school) {
    if (isset($_POST['action']) && isset($_POST['request_id'])) {
        $requestId = $_POST['request_id'];
        $action = $_POST['action'];

        if ($action === 'approve') {
            $result = apiApproveTeacherRequest($requestId);

            if ($result['success']) {
                setSuccessMessage('Richiesta approvata con successo!');
            } else {
                setErrorMessage($result['message'] ?? 'Errore durante l\'approvazione.');
            }
        } elseif ($action === 'reject') {
            $result = apiRejectTeacherRequest($requestId);

            if ($result['success']) {
                setSuccessMessage('Richiesta rifiutata.');
            } else {
                setErrorMessage($result['message'] ?? 'Errore durante il rifiuto.');
            }
        }

        header('Location: ' . baseUrl('admin/requests.php'));
        exit;
    }
}

// Get teacher requests
$pendingRequests = [];
$approvedRequests = [];
$rejectedRequests = [];

if ($school) {
    // Get all requests
    $allRequestsResult = apiGetTeacherRequests($school['id']);
    if ($allRequestsResult['success']) {
        $allRequests = $allRequestsResult['data'];

        // Separate by status
        foreach ($allRequests as $request) {
            switch ($request['status']) {
                case 'pending':
                    $pendingRequests[] = $request;
                    break;
                case 'approved':
                    $approvedRequests[] = $request;
                    break;
                case 'rejected':
                    $rejectedRequests[] = $request;
                    break;
            }
        }
    }
}

// Get view mode
$viewMode = $_GET['view'] ?? 'pending';

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">📝 Gestione Richieste</h1>
    <p class="page-subtitle">Gestisci le richieste di accesso dei docenti alla scuola</p>
</div>

<?php if (!$school): ?>
    <div class="alert alert-warning">
        Non hai ancora creato una scuola. <a href="<?php echo baseUrl('admin/school.php'); ?>" style="text-decoration: underline;">Crea la tua scuola prima</a>
    </div>

<?php else: ?>

    <!-- Statistics -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-value"><?php echo count($pendingRequests); ?></div>
            <div class="stat-label">Richieste Pendenti</div>
        </div>

        <div class="stat-card secondary">
            <div class="stat-icon">✅</div>
            <div class="stat-value"><?php echo count($approvedRequests); ?></div>
            <div class="stat-label">Richieste Approvate</div>
        </div>

        <div class="stat-card accent">
            <div class="stat-icon">❌</div>
            <div class="stat-value"><?php echo count($rejectedRequests); ?></div>
            <div class="stat-label">Richieste Rifiutate</div>
        </div>
    </div>

    <!-- View Tabs -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="<?php echo baseUrl('admin/requests.php?view=pending'); ?>"
                   class="btn <?php echo $viewMode === 'pending' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    ⏳ Pendenti (<?php echo count($pendingRequests); ?>)
                </a>
                <a href="<?php echo baseUrl('admin/requests.php?view=approved'); ?>"
                   class="btn <?php echo $viewMode === 'approved' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    ✅ Approvate (<?php echo count($approvedRequests); ?>)
                </a>
                <a href="<?php echo baseUrl('admin/requests.php?view=rejected'); ?>"
                   class="btn <?php echo $viewMode === 'rejected' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    ❌ Rifiutate (<?php echo count($rejectedRequests); ?>)
                </a>
            </div>
        </div>
    </div>

    <?php if ($viewMode === 'pending'): ?>
        <!-- Pending Requests -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">⏳ Richieste in Attesa di Approvazione</h3>
            </div>
            <div class="card-body">
                <?php if (empty($pendingRequests)): ?>
                    <div style="text-align: center; padding: 3rem;">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">⏳</div>
                        <h3>Nessuna Richiesta Pendente</h3>
                        <p style="color: #adb5bd; margin: 1rem 0;">
                            Le nuove richieste di accesso dei docenti appariranno qui.
                        </p>
                        <p style="color: #adb5bd;">
                            Condividi l'ID univoco della tua scuola con i docenti:
                        </p>
                        <code style="background: #343a40; padding: 0.75rem 1rem; border-radius: 8px; font-size: 1.5rem; font-weight: bold; color: var(--admin-accent);">
                            <?php echo htmlspecialchars($school['unique_id']); ?>
                        </code>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Docente</th>
                                    <th>Email</th>
                                    <th>ID Univoco</th>
                                    <th>Data Richiesta</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pendingRequests as $request): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($request['teacher_first_name'] . ' ' . $request['teacher_last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($request['teacher_email']); ?></td>
                                        <td>
                                            <code style="background: #343a40; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.9rem;">
                                                <?php echo htmlspecialchars($request['teacher_unique_id']); ?>
                                            </code>
                                        </td>
                                        <td><?php echo formatDateTime($request['created_at']); ?></td>
                                        <td>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    ✓ Approva
                                                </button>
                                            </form>
                                            <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Sei sicuro di voler rifiutare questa richiesta?');">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    ✕ Rifiuta
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($viewMode === 'approved'): ?>
        <!-- Approved Requests -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">✅ Richieste Approvate</h3>
            </div>
            <div class="card-body">
                <?php if (empty($approvedRequests)): ?>
                    <p class="text-center" style="padding: 2rem; color: #adb5bd;">
                        Nessuna richiesta approvata.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Docente</th>
                                    <th>Email</th>
                                    <th>ID Univoco</th>
                                    <th>Data Richiesta</th>
                                    <th>Data Approvazione</th>
                                    <th>Stato</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($approvedRequests as $request): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($request['teacher_first_name'] . ' ' . $request['teacher_last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($request['teacher_email']); ?></td>
                                        <td>
                                            <code style="background: #343a40; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.9rem;">
                                                <?php echo htmlspecialchars($request['teacher_unique_id']); ?>
                                            </code>
                                        </td>
                                        <td><?php echo formatDate($request['created_at']); ?></td>
                                        <td><?php echo formatDateTime($request['updated_at']); ?></td>
                                        <td>
                                            <span class="badge bg-success">Approvata</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($viewMode === 'rejected'): ?>
        <!-- Rejected Requests -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">❌ Richieste Rifiutate</h3>
            </div>
            <div class="card-body">
                <?php if (empty($rejectedRequests)): ?>
                    <p class="text-center" style="padding: 2rem; color: #adb5bd;">
                        Nessuna richiesta rifiutata.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Docente</th>
                                    <th>Email</th>
                                    <th>ID Univoco</th>
                                    <th>Data Richiesta</th>
                                    <th>Data Rifiuto</th>
                                    <th>Stato</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rejectedRequests as $request): ?>
                                    <tr style="opacity: 0.7;">
                                        <td>
                                            <strong><?php echo htmlspecialchars($request['teacher_first_name'] . ' ' . $request['teacher_last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($request['teacher_email']); ?></td>
                                        <td>
                                            <code style="background: #343a40; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.9rem;">
                                                <?php echo htmlspecialchars($request['teacher_unique_id']); ?>
                                            </code>
                                        </td>
                                        <td><?php echo formatDate($request['created_at']); ?></td>
                                        <td><?php echo formatDateTime($request['updated_at']); ?></td>
                                        <td>
                                            <span class="badge bg-danger">Rifiutata</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>

    <!-- Info Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">ℹ️ Come Funzionano le Richieste</h3>
        </div>
        <div class="card-body">
            <h5>Processo di Richiesta:</h5>
            <ol style="color: #adb5bd;">
                <li><strong>Richiesta:</strong> Un docente registrato cerca la tua scuola usando l'ID univoco e invia una richiesta di accesso.</li>
                <li><strong>Revisione:</strong> Tu ricevi la richiesta nella tab "Pendenti" e puoi vedere le informazioni del docente.</li>
                <li><strong>Decisione:</strong> Puoi approvare o rifiutare la richiesta.</li>
                <li><strong>Approvazione:</strong> Se approvi, il docente viene aggiunto alla scuola e può iniziare a creare corsi e gestire studenti.</li>
                <li><strong>Rifiuto:</strong> Se rifiuti, il docente riceve una notifica del rifiuto.</li>
            </ol>

            <div class="mt-3 p-3" style="background: rgba(13, 110, 253, 0.1); border-radius: 8px; border-left: 4px solid var(--admin-primary);">
                <h6>ID Univoco della Tua Scuola:</h6>
                <div class="d-flex align-items-center gap-2 mt-2">
                    <code style="background: #343a40; padding: 0.75rem 1rem; border-radius: 8px; font-size: 1.3rem; font-weight: bold; flex: 1; color: var(--admin-accent);">
                        <?php echo htmlspecialchars($school['unique_id']); ?>
                    </code>
                    <button class="btn btn-outline-primary" onclick="copyToClipboard('<?php echo htmlspecialchars($school['unique_id']); ?>')">
                        📋 Copia
                    </button>
                </div>
                <p style="margin: 1rem 0 0 0; font-size: 0.9rem; color: #adb5bd;">
                    Condividi questo ID con i docenti che vuoi aggiungere alla tua scuola.
                </p>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
