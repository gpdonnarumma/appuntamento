<?php
/**
 * Teacher Dashboard
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_TEACHER);

$pageTitle = 'Dashboard Docente';
$user = getCurrentUser();

// Get teacher's courses
$coursesResult = apiGetCourse(null, $user['id']);
$courses = $coursesResult['success'] ? $coursesResult['data'] : [];

// Get upcoming lessons
$lessonsResult = apiGetLessons(['teacher_id' => $user['id'], 'date_from' => date('Y-m-d')]);
$upcomingLessons = $lessonsResult['success'] ? $lessonsResult['data'] : [];

// Get pending enrollment requests
$requestsResult = apiGetEnrollmentRequests($user['id'], true);
$pendingRequests = $requestsResult['success'] ? $requestsResult['data'] : [];

// Count total students
$totalStudents = 0;
foreach ($courses as $course) {
    $totalStudents += $course['enrolled_students'] ?? 0;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Dashboard Docente</h1>
    <p class="page-subtitle">Benvenuto, Prof. <?php echo htmlspecialchars($user['last_name']); ?>!</p>
</div>

<!-- Statistics -->
<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-icon">📚</div>
        <div class="stat-value"><?php echo count($courses); ?></div>
        <div class="stat-label">Corsi Attivi</div>
    </div>

    <div class="stat-card secondary">
        <div class="stat-icon">🎓</div>
        <div class="stat-value"><?php echo $totalStudents; ?></div>
        <div class="stat-label">Studenti Totali</div>
    </div>

    <div class="stat-card accent">
        <div class="stat-icon">📅</div>
        <div class="stat-value"><?php echo count($upcomingLessons); ?></div>
        <div class="stat-label">Lezioni Future</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">⏳</div>
        <div class="stat-value"><?php echo count($pendingRequests); ?></div>
        <div class="stat-label">Richieste Pendenti</div>
    </div>
</div>

<!-- Your Unique ID -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">🆔 Il Tuo ID Univoco</h3>
    </div>
    <div class="card-body">
        <p>Condividi questo ID con gli studenti per permettere loro di iscriversi ai tuoi corsi:</p>
        <div class="d-flex align-items-center gap-2 mt-2">
            <code style="background: #f5f5f5; padding: 0.75rem 1rem; border-radius: 8px; font-size: 1.5rem; font-weight: bold;">
                <?php echo htmlspecialchars($user['unique_id']); ?>
            </code>
            <button class="btn btn-primary" onclick="copyToClipboard('<?php echo htmlspecialchars($user['unique_id']); ?>')">
                📋 Copia ID
            </button>
        </div>
    </div>
</div>

<!-- Pending Enrollment Requests -->
<?php if (!empty($pendingRequests)): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">⏳ Richieste Iscrizione Pendenti</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Studente</th>
                            <th>Email</th>
                            <th>Corso</th>
                            <th>Data Richiesta</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingRequests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['student_first_name'] . ' ' . $request['student_last_name']); ?></td>
                                <td><?php echo htmlspecialchars($request['student_email']); ?></td>
                                <td><?php echo htmlspecialchars($request['course_name']); ?></td>
                                <td><?php echo formatDateTime($request['created_at']); ?></td>
                                <td>
                                    <form method="POST" action="<?php echo baseUrl('teacher/requests.php'); ?>" style="display: inline;">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success">Approva</button>
                                    </form>
                                    <form method="POST" action="<?php echo baseUrl('teacher/requests.php'); ?>" style="display: inline;">
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

<!-- Your Courses -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📚 I Tuoi Corsi</h3>
        <a href="<?php echo baseUrl('teacher/courses.php'); ?>" class="btn btn-sm btn-primary">Gestisci Corsi</a>
    </div>
    <div class="card-body">
        <?php if (empty($courses)): ?>
            <p class="text-center" style="padding: 2rem; color: #666;">
                Non hai ancora creato corsi. <a href="<?php echo baseUrl('teacher/courses.php'); ?>">Crea il tuo primo corso</a>
            </p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($courses as $course): ?>
                    <div class="col-4">
                        <div class="card course-card">
                            <div class="card-body">
                                <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>
                                <p><?php echo htmlspecialchars($course['description'] ?? ''); ?></p>
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <span class="badge badge-teacher">
                                        <?php echo $course['enrolled_students'] ?? 0; ?> studenti
                                    </span>
                                    <a href="<?php echo baseUrl('teacher/courses.php?id=' . $course['id']); ?>" class="btn btn-sm btn-primary">
                                        Dettagli
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-4">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size: 3rem; margin-bottom: 1rem;">➕</div>
                <h4>Crea Lezione</h4>
                <p>Programma una nuova lezione</p>
                <a href="<?php echo baseUrl('teacher/lessons.php?action=create'); ?>" class="btn btn-primary">Crea Lezione</a>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📅</div>
                <h4>Calendario</h4>
                <p>Visualizza tutte le lezioni</p>
                <a href="<?php echo baseUrl('teacher/lessons.php'); ?>" class="btn btn-primary">Vai al Calendario</a>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🎓</div>
                <h4>Studenti</h4>
                <p>Visualizza i tuoi studenti</p>
                <a href="<?php echo baseUrl('teacher/students.php'); ?>" class="btn btn-primary">Vai agli Studenti</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
