<?php
/**
 * Student - My Courses
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_STUDENT);

$pageTitle = 'I Miei Corsi';
$user = getCurrentUser();

// Get view mode
$viewCourseId = $_GET['id'] ?? null;

// Get student's courses
$coursesResult = apiGetCourse(null, null, $user['id']);
$courses = $coursesResult['success'] ? $coursesResult['data'] : [];

// Get course details if viewing
$viewCourse = null;
$courseLessons = [];
$courseStats = [];

if ($viewCourseId) {
    foreach ($courses as $c) {
        if ($c['id'] == $viewCourseId) {
            $viewCourse = $c;

            // Get lessons for this course
            $lessonsResult = apiGetLessons([
                'course_id' => $viewCourseId,
                'student_id' => $user['id']
            ]);
            if ($lessonsResult['success']) {
                $courseLessons = $lessonsResult['data'];
            }

            // Calculate stats
            $totalLessons = count($courseLessons);
            $completedLessons = 0;
            $upcomingLessons = 0;
            $today = date('Y-m-d');

            foreach ($courseLessons as $lesson) {
                if ($lesson['lesson_date'] < $today) {
                    $completedLessons++;
                } else {
                    $upcomingLessons++;
                }
            }

            $courseStats = [
                'total' => $totalLessons,
                'completed' => $completedLessons,
                'upcoming' => $upcomingLessons
            ];

            break;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<?php if ($viewCourse): ?>
    <!-- Course Detail View -->
    <div class="page-header">
        <h1 class="page-title"><?php echo htmlspecialchars($viewCourse['course_name']); ?></h1>
        <a href="<?php echo baseUrl('student/courses.php'); ?>" class="btn btn-outline-secondary">← Torna ai Corsi</a>
    </div>

    <!-- Course Info -->
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h4>👨‍🏫 Docente</h4>
                    <p>Prof. <?php echo htmlspecialchars($viewCourse['teacher_first_name'] . ' ' . $viewCourse['teacher_last_name']); ?></p>

                    <?php if (!empty($viewCourse['description'])): ?>
                        <h4 class="mt-4">📋 Descrizione del Corso</h4>
                        <p><?php echo nl2br(htmlspecialchars($viewCourse['description'])); ?></p>
                    <?php endif; ?>

                    <h4 class="mt-4">📅 Informazioni Iscrizione</h4>
                    <p>Iscritto dal: <strong><?php echo formatDate($viewCourse['enrolled_at']); ?></strong></p>
                </div>
                <div class="col-md-4">
                    <!-- Course Stats -->
                    <div class="card" style="background: #f8f9fa;">
                        <div class="card-body">
                            <h5 class="mb-3">📊 Statistiche</h5>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Lezioni Totali:</span>
                                    <strong class="badge badge-student"><?php echo $courseStats['total']; ?></strong>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Completate:</span>
                                    <strong style="color: #28a745;"><?php echo $courseStats['completed']; ?></strong>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Future:</span>
                                    <strong style="color: #007bff;"><?php echo $courseStats['upcoming']; ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Lessons -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📅 Lezioni del Corso</h3>
        </div>
        <div class="card-body">
            <?php if (empty($courseLessons)): ?>
                <p class="text-center" style="padding: 2rem; color: #666;">
                    Non ci sono ancora lezioni programmate per questo corso.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Orario</th>
                                <th>Aula</th>
                                <th>Obiettivi</th>
                                <th>Stato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $today = date('Y-m-d');
                            foreach ($courseLessons as $lesson):
                                $isPast = $lesson['lesson_date'] < $today;
                            ?>
                                <tr style="<?php echo $isPast ? 'opacity: 0.6;' : ''; ?>">
                                    <td><?php echo formatDate($lesson['lesson_date']); ?></td>
                                    <td><?php echo formatTime($lesson['start_time']); ?> - <?php echo formatTime($lesson['end_time']); ?></td>
                                    <td><?php echo htmlspecialchars($lesson['classroom'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($lesson['objectives'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($isPast): ?>
                                            <span class="badge badge-secondary">Completata</span>
                                        <?php else: ?>
                                            <span class="badge badge-student">Programmata</span>
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
    <!-- Courses List -->
    <div class="page-header">
        <h1 class="page-title">📚 I Miei Corsi</h1>
        <p class="page-subtitle">Tutti i corsi a cui sei iscritto</p>
    </div>

    <?php if (empty($courses)): ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 3rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📚</div>
                <h3>Non sei ancora iscritto a nessun corso</h3>
                <p style="color: #666; margin: 1rem 0;">
                    Cerca un docente per iniziare il tuo percorso musicale!
                </p>
                <a href="<?php echo baseUrl('student/search.php'); ?>" class="btn btn-primary">
                    🔍 Cerca Docente
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Course Statistics -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-value"><?php echo count($courses); ?></div>
                <div class="stat-label">Corsi Totali</div>
            </div>

            <div class="stat-card secondary">
                <div class="stat-icon">👨‍🏫</div>
                <div class="stat-value">
                    <?php
                    $uniqueTeachers = [];
                    foreach ($courses as $c) {
                        $uniqueTeachers[$c['teacher_id']] = true;
                    }
                    echo count($uniqueTeachers);
                    ?>
                </div>
                <div class="stat-label">Docenti</div>
            </div>

            <div class="stat-card accent">
                <div class="stat-icon">🎵</div>
                <div class="stat-value">
                    <?php
                    $activeCount = 0;
                    foreach ($courses as $c) {
                        if (!empty($c['enrolled_at'])) {
                            $activeCount++;
                        }
                    }
                    echo $activeCount;
                    ?>
                </div>
                <div class="stat-label">Corsi Attivi</div>
            </div>
        </div>

        <!-- Courses Grid -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">I Tuoi Corsi</h3>
                <a href="<?php echo baseUrl('student/search.php'); ?>" class="btn btn-sm btn-primary">🔍 Cerca Nuovo Docente</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($courses as $course): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card course-card" style="margin-bottom: 1.5rem;">
                                <div class="card-body">
                                    <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>

                                    <p style="color: #666; margin: 0.5rem 0;">
                                        👨‍🏫 Prof. <?php echo htmlspecialchars($course['teacher_first_name'] . ' ' . $course['teacher_last_name']); ?>
                                    </p>

                                    <?php if (!empty($course['description'])): ?>
                                        <p style="color: #666; font-size: 0.9rem; margin: 0.5rem 0;">
                                            <?php
                                            $desc = $course['description'];
                                            echo htmlspecialchars(strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc);
                                            ?>
                                        </p>
                                    <?php endif; ?>

                                    <div style="margin: 1rem 0;">
                                        <span class="badge badge-student">
                                            Iscritto dal <?php echo formatDate($course['enrolled_at']); ?>
                                        </span>
                                    </div>

                                    <a href="<?php echo baseUrl('student/courses.php?id=' . $course['id']); ?>" class="btn btn-primary btn-block">
                                        👁️ Visualizza Dettagli
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
