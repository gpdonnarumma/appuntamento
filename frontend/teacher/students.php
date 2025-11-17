<?php
/**
 * Teacher Students Management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_TEACHER);

$pageTitle = 'I Miei Studenti';
$user = getCurrentUser();

// Get teacher's courses
$coursesResult = apiGetCourse(null, $user['id']);
$courses = $coursesResult['success'] ? $coursesResult['data'] : [];

// Get filter
$filterCourseId = $_GET['course'] ?? null;

// Get all students enrolled in teacher's courses
$allStudents = [];
$studentsByCourse = [];

foreach ($courses as $course) {
    $enrollmentsResult = apiGetEnrollments($course['id']);
    if ($enrollmentsResult['success']) {
        $enrollments = $enrollmentsResult['data'];
        $studentsByCourse[$course['id']] = $enrollments;

        foreach ($enrollments as $enrollment) {
            $studentKey = $enrollment['student_id'];
            if (!isset($allStudents[$studentKey])) {
                $allStudents[$studentKey] = [
                    'id' => $enrollment['student_id'],
                    'unique_id' => $enrollment['student_unique_id'],
                    'first_name' => $enrollment['student_first_name'],
                    'last_name' => $enrollment['student_last_name'],
                    'email' => $enrollment['student_email'],
                    'courses' => []
                ];
            }
            $allStudents[$studentKey]['courses'][] = [
                'course_id' => $course['id'],
                'course_name' => $course['course_name'],
                'enrolled_at' => $enrollment['enrolled_at']
            ];
        }
    }
}

// Filter students by course if selected
$displayStudents = $allStudents;
if ($filterCourseId) {
    $displayStudents = array_filter($allStudents, function($student) use ($filterCourseId) {
        foreach ($student['courses'] as $course) {
            if ($course['course_id'] == $filterCourseId) {
                return true;
            }
        }
        return false;
    });
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">👥 I Miei Studenti</h1>
    <p class="page-subtitle">Visualizza e gestisci i tuoi studenti</p>
</div>

<!-- Filter by Course -->
<div class="card">
    <div class="card-body" style="padding: 1rem;">
        <div class="d-flex gap-2 align-items-center">
            <label style="margin: 0; font-weight: 600;">Filtra per corso:</label>
            <a href="<?php echo baseUrl('teacher/students.php'); ?>"
               class="btn btn-sm <?php echo !$filterCourseId ? 'btn-primary' : 'btn-outline'; ?>">
                📚 Tutti i Corsi (<?php echo count($allStudents); ?>)
            </a>
            <?php foreach ($courses as $course): ?>
                <a href="<?php echo baseUrl('teacher/students.php?course=' . $course['id']); ?>"
                   class="btn btn-sm <?php echo $filterCourseId == $course['id'] ? 'btn-primary' : 'btn-outline'; ?>">
                    <?php echo htmlspecialchars($course['course_name']); ?>
                    (<?php echo count($studentsByCourse[$course['id']] ?? []); ?>)
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Students List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Studenti Iscritti (<?php echo count($displayStudents); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($displayStudents)): ?>
            <div style="text-align: center; padding: 3rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">👥</div>
                <h3>Nessuno studente</h3>
                <p style="color: #666;">
                    <?php if ($filterCourseId): ?>
                        Nessuno studente iscritto a questo corso.
                    <?php else: ?>
                        Non hai ancora studenti iscritti ai tuoi corsi.<br>
                        Le richieste di iscrizione appariranno nella sezione <a href="<?php echo baseUrl('teacher/requests.php'); ?>">Richieste</a>.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($displayStudents as $student): ?>
                    <div class="col-6" style="margin-bottom: 1.5rem;">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h4 style="margin: 0 0 0.5rem 0;">
                                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                        </h4>
                                        <p style="margin: 0; color: #666; font-size: 0.9rem;">
                                            📧 <?php echo htmlspecialchars($student['email']); ?>
                                        </p>
                                        <p style="margin: 0.25rem 0 0 0; color: #666; font-size: 0.9rem;">
                                            🆔 <code><?php echo htmlspecialchars($student['unique_id']); ?></code>
                                        </p>
                                    </div>
                                </div>

                                <div style="margin: 1rem 0;">
                                    <strong style="font-size: 0.9rem;">Corsi iscritti:</strong>
                                    <?php foreach ($student['courses'] as $course): ?>
                                        <div style="margin: 0.5rem 0;">
                                            <span class="badge badge-teacher">
                                                📚 <?php echo htmlspecialchars($course['course_name']); ?>
                                            </span>
                                            <span style="font-size: 0.85rem; color: #666; margin-left: 0.5rem;">
                                                dal <?php echo formatDate($course['enrolled_at']); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="d-flex gap-2" style="margin-top: 1rem;">
                                    <a href="<?php echo baseUrl('teacher/lessons.php?student=' . $student['id']); ?>"
                                       class="btn btn-sm btn-primary" style="flex: 1;">
                                        📅 Visualizza Lezioni
                                    </a>
                                    <button class="btn btn-sm btn-outline" onclick="copyToClipboard('<?php echo htmlspecialchars($student['email']); ?>')">
                                        📋 Copia Email
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Summary Stats -->
<div class="row">
    <div class="col-3">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">👥</div>
                <h3 style="margin: 0;"><?php echo count($allStudents); ?></h3>
                <p style="color: #666; margin: 0.5rem 0 0 0;">Studenti Totali</p>
            </div>
        </div>
    </div>
    <div class="col-3">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">📚</div>
                <h3 style="margin: 0;"><?php echo count($courses); ?></h3>
                <p style="color: #666; margin: 0.5rem 0 0 0;">Corsi Attivi</p>
            </div>
        </div>
    </div>
    <div class="col-3">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">📈</div>
                <h3 style="margin: 0;">
                    <?php echo count($courses) > 0 ? round(count($allStudents) / count($courses), 1) : 0; ?>
                </h3>
                <p style="color: #666; margin: 0.5rem 0 0 0;">Media Studenti/Corso</p>
            </div>
        </div>
    </div>
    <div class="col-3">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size: 3rem; margin-bottom: 0.5rem;">🎯</div>
                <h3 style="margin: 0;">
                    <?php
                    $totalEnrollments = 0;
                    foreach ($allStudents as $s) {
                        $totalEnrollments += count($s['courses']);
                    }
                    echo $totalEnrollments;
                    ?>
                </h3>
                <p style="color: #666; margin: 0.5rem 0 0 0;">Iscrizioni Totali</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
