<?php
/**
 * Admin - Students Management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_ADMIN);

$pageTitle = 'Gestione Studenti';
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

// Get students and their details
$students = [];
$studentCourses = [];
$studentLessons = [];

if ($school) {
    $schoolDetailsResult = apiGetSchool($school['id']);
    if ($schoolDetailsResult['success']) {
        $students = $schoolDetailsResult['data']['students'] ?? [];

        // Get courses and lessons for each student
        foreach ($students as $student) {
            $coursesResult = apiGetCourse(null, null, $student['id']);
            if ($coursesResult['success']) {
                $studentCourses[$student['id']] = $coursesResult['data'];
            }

            $lessonsResult = apiGetLessons(['student_id' => $student['id']]);
            if ($lessonsResult['success']) {
                $studentLessons[$student['id']] = count($lessonsResult['data']);
            }
        }
    }
}

// Get view mode and selected student
$studentId = $_GET['student_id'] ?? null;

$selectedStudent = null;
$selectedStudentCourses = [];
$selectedStudentLessons = [];

if ($studentId) {
    // Find student details
    foreach ($students as $s) {
        if ($s['id'] == $studentId) {
            $selectedStudent = $s;
            $selectedStudentCourses = $studentCourses[$studentId] ?? [];

            // Get lessons
            $lessonsResult = apiGetLessons(['student_id' => $studentId]);
            if ($lessonsResult['success']) {
                $selectedStudentLessons = $lessonsResult['data'];
            }
            break;
        }
    }
}

// Search/Filter
$searchQuery = $_GET['search'] ?? '';
$filteredStudents = $students;

if (!empty($searchQuery)) {
    $filteredStudents = array_filter($students, function($student) use ($searchQuery) {
        $name = strtolower($student['first_name'] . ' ' . $student['last_name']);
        $email = strtolower($student['email']);
        $uniqueId = strtolower($student['unique_id']);
        $query = strtolower($searchQuery);

        return strpos($name, $query) !== false ||
               strpos($email, $query) !== false ||
               strpos($uniqueId, $query) !== false;
    });
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">🎓 Gestione Studenti</h1>
    <p class="page-subtitle">Visualizza gli studenti iscritti ai corsi della tua scuola</p>
</div>

<?php if (!$school): ?>
    <div class="alert alert-warning">
        Non hai ancora creato una scuola. <a href="<?php echo baseUrl('admin/school.php'); ?>" style="text-decoration: underline;">Crea la tua scuola prima</a>
    </div>

<?php elseif ($selectedStudent): ?>
    <!-- Student Detail View -->
    <div class="mb-3">
        <a href="<?php echo baseUrl('admin/students.php'); ?>" class="btn btn-outline-secondary">
            ← Torna alla Lista Studenti
        </a>
    </div>

    <!-- Student Info Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informazioni Studente</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center">
                    <?php if (!empty($selectedStudent['profile_photo'])): ?>
                        <img
                            src="<?php echo htmlspecialchars($selectedStudent['profile_photo']); ?>"
                            alt="Foto Profilo"
                            style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid var(--admin-primary);"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                        >
                        <div style="display: none; width: 150px; height: 150px; border-radius: 50%; background: var(--admin-primary); color: white; font-size: 3rem; align-items: center; justify-content: center; margin: 0 auto;">
                            <?php echo strtoupper(substr($selectedStudent['first_name'], 0, 1)); ?>
                        </div>
                    <?php else: ?>
                        <div style="width: 150px; height: 150px; border-radius: 50%; background: var(--admin-primary); color: white; font-size: 3rem; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                            <?php echo strtoupper(substr($selectedStudent['first_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-9">
                    <h3><?php echo htmlspecialchars($selectedStudent['first_name'] . ' ' . $selectedStudent['last_name']); ?></h3>

                    <div class="row mt-3">
                        <div class="col-md-6">
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($selectedStudent['email']); ?></p>
                            <p><strong>Data di Nascita:</strong> <?php echo formatDate($selectedStudent['birth_date']); ?></p>
                            <p><strong>ID Univoco:</strong> <code style="background: #343a40; padding: 0.25rem 0.5rem; border-radius: 4px;"><?php echo htmlspecialchars($selectedStudent['unique_id']); ?></code></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Registrato il:</strong> <?php echo formatDateTime($selectedStudent['created_at']); ?></p>
                            <p><strong>Corsi Iscritti:</strong> <?php echo count($selectedStudentCourses); ?></p>
                            <p><strong>Lezioni Totali:</strong> <?php echo count($selectedStudentLessons); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Statistics -->
    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-icon">📚</div>
            <div class="stat-value"><?php echo count($selectedStudentCourses); ?></div>
            <div class="stat-label">Corsi Iscritti</div>
        </div>

        <div class="stat-card secondary">
            <div class="stat-icon">📅</div>
            <div class="stat-value"><?php echo count($selectedStudentLessons); ?></div>
            <div class="stat-label">Lezioni Totali</div>
        </div>

        <div class="stat-card accent">
            <div class="stat-icon">✅</div>
            <div class="stat-value">
                <?php
                $completedCount = 0;
                $today = date('Y-m-d');
                foreach ($selectedStudentLessons as $lesson) {
                    if ($lesson['lesson_date'] < $today) {
                        $completedCount++;
                    }
                }
                echo $completedCount;
                ?>
            </div>
            <div class="stat-label">Lezioni Completate</div>
        </div>
    </div>

    <!-- Student Courses -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📚 Corsi Iscritti</h3>
        </div>
        <div class="card-body">
            <?php if (empty($selectedStudentCourses)): ?>
                <p class="text-center" style="padding: 2rem; color: #adb5bd;">
                    Questo studente non è iscritto a nessun corso.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nome Corso</th>
                                <th>Docente</th>
                                <th>Iscritto il</th>
                                <th>Stato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($selectedStudentCourses as $course): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($course['course_name']); ?></strong></td>
                                    <td>Prof. <?php echo htmlspecialchars($course['teacher_first_name'] . ' ' . $course['teacher_last_name']); ?></td>
                                    <td><?php echo formatDateTime($course['enrolled_at']); ?></td>
                                    <td><span class="badge bg-success">Attivo</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Student Lessons -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📅 Ultime Lezioni</h3>
        </div>
        <div class="card-body">
            <?php if (empty($selectedStudentLessons)): ?>
                <p class="text-center" style="padding: 2rem; color: #adb5bd;">
                    Nessuna lezione programmata per questo studente.
                </p>
            <?php else: ?>
                <?php
                // Sort by date descending
                usort($selectedStudentLessons, function($a, $b) {
                    return strcmp($b['lesson_date'], $a['lesson_date']);
                });
                $recentLessons = array_slice($selectedStudentLessons, 0, 10);
                ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Orario</th>
                                <th>Corso</th>
                                <th>Docente</th>
                                <th>Aula</th>
                                <th>Stato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $today = date('Y-m-d');
                            foreach ($recentLessons as $lesson):
                                $isPast = $lesson['lesson_date'] < $today;
                            ?>
                                <tr>
                                    <td><?php echo formatDate($lesson['lesson_date']); ?></td>
                                    <td><?php echo formatTime($lesson['start_time']); ?> - <?php echo formatTime($lesson['end_time']); ?></td>
                                    <td><?php echo htmlspecialchars($lesson['course_name']); ?></td>
                                    <td>Prof. <?php echo htmlspecialchars($lesson['teacher_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($lesson['classroom'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($isPast): ?>
                                            <span class="badge bg-secondary">Completata</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Programmata</span>
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

<?php else: ?>
    <!-- Students List -->
    <?php if (empty($students)): ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 3rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">🎓</div>
                <h3>Nessuno Studente Registrato</h3>
                <p style="color: #adb5bd; margin: 1rem 0;">
                    Gli studenti appariranno qui quando si iscriveranno ai corsi dei tuoi docenti.
                </p>
                <p style="color: #adb5bd;">
                    Gli studenti possono cercare i docenti usando il loro ID univoco e richiedere l'iscrizione ai corsi.
                </p>
            </div>
        </div>
    <?php else: ?>
        <!-- Statistics -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">🎓</div>
                <div class="stat-value"><?php echo count($students); ?></div>
                <div class="stat-label">Studenti Totali</div>
            </div>

            <div class="stat-card secondary">
                <div class="stat-icon">📚</div>
                <div class="stat-value">
                    <?php
                    $totalEnrollments = 0;
                    foreach ($studentCourses as $courses) {
                        $totalEnrollments += count($courses);
                    }
                    echo $totalEnrollments;
                    ?>
                </div>
                <div class="stat-label">Iscrizioni Totali</div>
            </div>

            <div class="stat-card accent">
                <div class="stat-icon">📅</div>
                <div class="stat-value">
                    <?php
                    $totalLessons = array_sum($studentLessons);
                    echo $totalLessons;
                    ?>
                </div>
                <div class="stat-label">Lezioni Totali</div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-10">
                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Cerca studente per nome, email o ID univoco..."
                                value="<?php echo htmlspecialchars($searchQuery); ?>"
                            >
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">🔍 Cerca</button>
                        </div>
                    </div>
                </form>
                <?php if (!empty($searchQuery)): ?>
                    <div class="mt-2">
                        <a href="<?php echo baseUrl('admin/students.php'); ?>" class="btn btn-sm btn-outline-secondary">
                            ✕ Cancella Ricerca
                        </a>
                        <span class="ms-2" style="color: #adb5bd;">
                            Trovati <?php echo count($filteredStudents); ?> risultati per "<?php echo htmlspecialchars($searchQuery); ?>"
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Students Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    Lista Studenti (<?php echo count($filteredStudents); ?>)
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($filteredStudents)): ?>
                    <p class="text-center" style="padding: 2rem; color: #adb5bd;">
                        Nessun studente trovato con questi criteri di ricerca.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Email</th>
                                    <th>ID Univoco</th>
                                    <th>Corsi</th>
                                    <th>Lezioni</th>
                                    <th>Registrato il</th>
                                    <th>Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filteredStudents as $student): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td>
                                            <code style="background: #343a40; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.9rem;">
                                                <?php echo htmlspecialchars($student['unique_id']); ?>
                                            </code>
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">
                                                <?php echo count($studentCourses[$student['id']] ?? []); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary">
                                                <?php echo $studentLessons[$student['id']] ?? 0; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($student['created_at']); ?></td>
                                        <td>
                                            <a href="<?php echo baseUrl('admin/students.php?student_id=' . $student['id']); ?>" class="btn btn-sm btn-outline-primary">
                                                👁️ Dettagli
                                            </a>
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

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
