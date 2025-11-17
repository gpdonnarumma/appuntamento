<?php
/**
 * Admin - Teachers Management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_ADMIN);

$pageTitle = 'Gestione Docenti';
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

// Get teachers and their details
$teachers = [];
$teacherCourses = [];
$teacherStudents = [];

if ($school) {
    $schoolDetailsResult = apiGetSchool($school['id']);
    if ($schoolDetailsResult['success']) {
        $teachers = $schoolDetailsResult['data']['teachers'] ?? [];

        // Get courses and students for each teacher
        foreach ($teachers as $teacher) {
            $coursesResult = apiGetCourse(null, $teacher['id']);
            if ($coursesResult['success']) {
                $teacherCourses[$teacher['id']] = $coursesResult['data'];

                // Count unique students
                $uniqueStudents = [];
                foreach ($coursesResult['data'] as $course) {
                    if (isset($course['enrolled_students'])) {
                        $enrollmentsResult = apiGetEnrollments($course['id']);
                        if ($enrollmentsResult['success']) {
                            foreach ($enrollmentsResult['data'] as $enrollment) {
                                $uniqueStudents[$enrollment['student_id']] = true;
                            }
                        }
                    }
                }
                $teacherStudents[$teacher['id']] = count($uniqueStudents);
            }
        }
    }
}

// Get view mode and selected teacher
$viewMode = $_GET['view'] ?? 'list';
$teacherId = $_GET['teacher_id'] ?? null;

$selectedTeacher = null;
$selectedTeacherCourses = [];

if ($teacherId) {
    // Find teacher details
    foreach ($teachers as $t) {
        if ($t['id'] == $teacherId) {
            $selectedTeacher = $t;
            $selectedTeacherCourses = $teacherCourses[$teacherId] ?? [];
            break;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">👨‍🏫 Gestione Docenti</h1>
    <p class="page-subtitle">Visualizza e gestisci i docenti della tua scuola</p>
</div>

<?php if (!$school): ?>
    <div class="alert alert-warning">
        Non hai ancora creato una scuola. <a href="<?php echo baseUrl('admin/school.php'); ?>" style="text-decoration: underline;">Crea la tua scuola prima</a>
    </div>

<?php elseif ($selectedTeacher): ?>
    <!-- Teacher Detail View -->
    <div class="mb-3">
        <a href="<?php echo baseUrl('admin/teachers.php'); ?>" class="btn btn-outline-secondary">
            ← Torna alla Lista Docenti
        </a>
    </div>

    <!-- Teacher Info Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informazioni Docente</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center">
                    <?php if (!empty($selectedTeacher['profile_photo'])): ?>
                        <img
                            src="<?php echo htmlspecialchars($selectedTeacher['profile_photo']); ?>"
                            alt="Foto Profilo"
                            style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid var(--admin-primary);"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        >
                        <div style="display: none; width: 150px; height: 150px; border-radius: 50%; background: var(--admin-primary); color: white; font-size: 3rem; align-items: center; justify-content: center; margin: 0 auto;">
                            <?php echo strtoupper(substr($selectedTeacher['first_name'], 0, 1)); ?>
                        </div>
                    <?php else: ?>
                        <div style="width: 150px; height: 150px; border-radius: 50%; background: var(--admin-primary); color: white; font-size: 3rem; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                            <?php echo strtoupper(substr($selectedTeacher['first_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h3><?php echo htmlspecialchars($selectedTeacher['first_name'] . ' ' . $selectedTeacher['last_name']); ?></h3>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($selectedTeacher['email']); ?></p>
                            <p><strong>Data di Nascita:</strong> <?php echo formatDate($selectedTeacher['birth_date']); ?></p>
                            <p><strong>ID Univoco:</strong> <code style="background: #343a40; padding: 0.25rem 0.5rem; border-radius: 4px;"><?php echo htmlspecialchars($selectedTeacher['unique_id']); ?></code></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Registrato il:</strong> <?php echo formatDateTime($selectedTeacher['created_at']); ?></p>
                            <p><strong>Corsi Attivi:</strong> <?php echo count($selectedTeacherCourses); ?></p>
                            <p><strong>Studenti Totali:</strong> <?php echo $teacherStudents[$teacherId] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Teacher Courses -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📚 Corsi del Docente</h3>
        </div>
        <div class="card-body">
            <?php if (empty($selectedTeacherCourses)): ?>
                <p class="text-center" style="padding: 2rem; color: #adb5bd;">
                    Questo docente non ha ancora creato corsi.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome Corso</th>
                                <th>Descrizione</th>
                                <th>Studenti Iscritti</th>
                                <th>Creato il</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($selectedTeacherCourses as $course): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($course['course_name']); ?></strong></td>
                                    <td>
                                        <?php
                                        $desc = $course['description'] ?? '-';
                                        echo htmlspecialchars(strlen($desc) > 60 ? substr($desc, 0, 60) . '...' : $desc);
                                        ?>
                                    </td>
                                    <td><?php echo $course['enrolled_students'] ?? 0; ?></td>
                                    <td><?php echo formatDate($course['created_at']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <!-- Teachers List -->
    <?php if (empty($teachers)): ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 3rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">👨‍🏫</div>
                <h3>Nessun Docente Registrato</h3>
                <p style="color: #adb5bd; margin: 1rem 0;">
                    I docenti possono richiedere l'accesso alla scuola usando il tuo ID univoco:
                </p>
                <code style="background: #343a40; padding: 0.75rem 1rem; border-radius: 8px; font-size: 1.5rem; font-weight: bold; color: var(--admin-accent);">
                    <?php echo htmlspecialchars($school['unique_id']); ?>
                </code>
                <p style="color: #adb5bd; margin-top: 1rem;">
                    Le richieste di accesso appariranno nella <a href="<?php echo baseUrl('admin/requests.php'); ?>">pagina Richieste</a>.
                </p>
            </div>
        </div>
    <?php else: ?>
        <!-- Statistics -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">👨‍🏫</div>
                <div class="stat-value"><?php echo count($teachers); ?></div>
                <div class="stat-label">Docenti Totali</div>
            </div>

            <div class="stat-card secondary">
                <div class="stat-icon">📚</div>
                <div class="stat-value">
                    <?php
                    $totalCourses = 0;
                    foreach ($teacherCourses as $courses) {
                        $totalCourses += count($courses);
                    }
                    echo $totalCourses;
                    ?>
                </div>
                <div class="stat-label">Corsi Totali</div>
            </div>

            <div class="stat-card accent">
                <div class="stat-icon">🎓</div>
                <div class="stat-value">
                    <?php
                    $totalStudents = array_sum($teacherStudents);
                    echo $totalStudents;
                    ?>
                </div>
                <div class="stat-label">Studenti Totali</div>
            </div>
        </div>

        <!-- Teachers Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Lista Docenti (<?php echo count($teachers); ?>)</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>ID Univoco</th>
                                <th>Corsi</th>
                                <th>Studenti</th>
                                <th>Registrato il</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teachers as $teacher): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                                    <td>
                                        <code style="background: #343a40; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($teacher['unique_id']); ?>
                                        </code>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">
                                            <?php echo count($teacherCourses[$teacher['id']] ?? []); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            <?php echo $teacherStudents[$teacher['id']] ?? 0; ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($teacher['created_at']); ?></td>
                                    <td>
                                        <a href="<?php echo baseUrl('admin/teachers.php?teacher_id=' . $teacher['id']); ?>" class="btn btn-sm btn-outline-primary">
                                            👁️ Dettagli
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">ℹ️ Come Aggiungere Docenti</h3>
            </div>
            <div class="card-body">
                <p>I docenti possono richiedere l'accesso alla tua scuola seguendo questi passaggi:</p>
                <ol style="color: #adb5bd;">
                    <li>Il docente si registra sulla piattaforma come "Docente"</li>
                    <li>Nella sua dashboard, il docente cerca la scuola usando il tuo ID univoco: <code style="background: #343a40; padding: 0.25rem 0.5rem; border-radius: 4px; color: var(--admin-accent);"><?php echo htmlspecialchars($school['unique_id']); ?></code></li>
                    <li>Il docente invia una richiesta di accesso</li>
                    <li>Tu ricevi la richiesta nella <a href="<?php echo baseUrl('admin/requests.php'); ?>">pagina Richieste</a></li>
                    <li>Approvi o rifiuti la richiesta</li>
                </ol>
                <p style="margin-top: 1rem;">
                    <strong>Suggerimento:</strong> Condividi l'ID univoco della scuola con i docenti che vuoi aggiungere.
                </p>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
