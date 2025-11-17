<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_ADMIN);

$pageTitle = 'Dashboard Amministratore';
$user = getCurrentUser();

// Get schools for this admin
$schoolsResult = apiSearchSchools();
$schools = $schoolsResult['success'] ? $schoolsResult['data'] : [];

// Get first school (admin should have one)
$school = null;
if (!empty($schools)) {
    foreach ($schools as $s) {
        if ($s['admin_id'] == $user['id']) {
            $school = $s;
            break;
        }
    }
}

// Get school details if we have one
$teachers = [];
$students = [];
$lessons = [];

if ($school) {
    $schoolDetailsResult = apiGetSchool($school['id']);
    if ($schoolDetailsResult['success']) {
        $schoolDetails = $schoolDetailsResult['data'];
        $teachers = $schoolDetails['teachers'] ?? [];
        $students = $schoolDetails['students'] ?? [];
    }

    // Get lessons
    $lessonsResult = apiGetLessons(['date_from' => date('Y-m-d')]);
    $lessons = $lessonsResult['success'] ? $lessonsResult['data'] : [];
}

// Get pending teacher requests
$teacherRequests = [];
if ($school) {
    $requestsResult = apiGetTeacherRequests($school['id'], null, true);
    $teacherRequests = $requestsResult['success'] ? $requestsResult['data'] : [];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Dashboard Amministratore</h1>
    <p class="page-subtitle">Benvenuto, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
</div>

<?php if (!$school): ?>
    <div class="alert alert-warning">
        Non hai ancora creato una scuola. <a href="/frontend/admin/school.php" style="color: #856404; font-weight: bold;">Crea la tua scuola ora</a>
    </div>
<?php else: ?>

    <!-- Statistics -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">👨‍🏫</div>
            <div class="stat-value"><?php echo count($teachers); ?></div>
            <div class="stat-label">Docenti</div>
        </div>

        <div class="stat-card secondary">
            <div class="stat-icon">🎓</div>
            <div class="stat-value"><?php echo count($students); ?></div>
            <div class="stat-label">Studenti</div>
        </div>

        <div class="stat-card accent">
            <div class="stat-icon">📅</div>
            <div class="stat-value"><?php echo count($lessons); ?></div>
            <div class="stat-label">Lezioni Oggi</div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⏳</div>
            <div class="stat-value"><?php echo count($teacherRequests); ?></div>
            <div class="stat-label">Richieste Pendenti</div>
        </div>
    </div>

    <!-- School Info Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🏫 Informazioni Scuola</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <p><strong>Nome:</strong> <?php echo htmlspecialchars($school['school_name']); ?></p>
                    <p><strong>Città:</strong> <?php echo htmlspecialchars($school['city']); ?></p>
                </div>
                <div class="col-6">
                    <p><strong>ID Univoco:</strong>
                        <code style="background: #f5f5f5; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 1.1rem;">
                            <?php echo htmlspecialchars($school['unique_id']); ?>
                        </code>
                        <button class="btn btn-sm btn-outline ml-1" onclick="copyToClipboard('<?php echo htmlspecialchars($school['unique_id']); ?>')">
                            📋 Copia
                        </button>
                    </p>
                </div>
            </div>
            <a href="/frontend/admin/school.php" class="btn btn-primary mt-2">Gestisci Scuola</a>
        </div>
    </div>

    <!-- Pending Teacher Requests -->
    <?php if (!empty($teacherRequests)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">⏳ Richieste Docenti Pendenti</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Docente</th>
                                <th>Email</th>
                                <th>Data Richiesta</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teacherRequests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['teacher_first_name'] . ' ' . $request['teacher_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['teacher_email']); ?></td>
                                    <td><?php echo formatDateTime($request['created_at']); ?></td>
                                    <td>
                                        <form method="POST" action="/frontend/admin/requests.php" style="display: inline;">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success">Approva</button>
                                        </form>
                                        <form method="POST" action="/frontend/admin/requests.php" style="display: inline;">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Rifiuta</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Quick Links -->
    <div class="row">
        <div class="col-4">
            <div class="card">
                <div class="card-body text-center">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">👨‍🏫</div>
                    <h4>Gestisci Docenti</h4>
                    <p>Visualizza e gestisci i docenti della scuola</p>
                    <a href="/frontend/admin/teachers.php" class="btn btn-primary">Vai ai Docenti</a>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card">
                <div class="card-body text-center">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🎓</div>
                    <h4>Gestisci Studenti</h4>
                    <p>Visualizza la lista degli studenti</p>
                    <a href="/frontend/admin/students.php" class="btn btn-primary">Vai agli Studenti</a>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card">
                <div class="card-body text-center">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">📅</div>
                    <h4>Calendario Lezioni</h4>
                    <p>Visualizza tutte le lezioni programmate</p>
                    <a href="/frontend/admin/lessons.php" class="btn btn-primary">Vai al Calendario</a>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
