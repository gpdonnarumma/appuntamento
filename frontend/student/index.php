<?php
/**
 * Student Dashboard
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_STUDENT);

$pageTitle = 'Dashboard Studente';
$user = getCurrentUser();

// Get student's courses
$coursesResult = apiGetCourse(null, null, $user['id']);
$courses = $coursesResult['success'] ? $coursesResult['data'] : [];

// Get next lesson
$nextLessonResult = apiGetNextLesson($user['id']);
$nextLesson = $nextLessonResult['success'] ? $nextLessonResult['data'] : null;

// Get upcoming lessons
$lessonsResult = apiGetLessons(['student_id' => $user['id'], 'date_from' => date('Y-m-d')]);
$upcomingLessons = $lessonsResult['success'] ? $lessonsResult['data'] : [];

// Get unread notifications
$notificationsResult = apiGetNotifications(true, 10);
$unreadNotifications = $notificationsResult['success'] ? ($notificationsResult['data']['unread_count'] ?? 0) : 0;

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Dashboard Studente</h1>
    <p class="page-subtitle">Benvenuto, <?php echo htmlspecialchars($user['first_name']); ?>!</p>
</div>

<!-- Next Lesson Banner -->
<?php if ($nextLesson): ?>
    <div class="next-lesson-banner">
        <div class="next-lesson-title">🎵 Prossima Lezione</div>
        <div class="next-lesson-info">
            <strong><?php echo htmlspecialchars($nextLesson['course_name']); ?></strong> con
            Prof. <?php echo htmlspecialchars($nextLesson['teacher_last_name']); ?>
        </div>
        <div class="next-lesson-time">
            📅 <?php echo formatDate($nextLesson['lesson_date']); ?> alle <?php echo formatTime($nextLesson['start_time']); ?>
        </div>
        <?php if (!empty($nextLesson['classroom'])): ?>
            <div>📍 Aula: <?php echo htmlspecialchars($nextLesson['classroom']); ?></div>
        <?php endif; ?>
        <?php if (!empty($nextLesson['objectives'])): ?>
            <div class="mt-2">
                <strong>Obiettivi:</strong> <?php echo htmlspecialchars($nextLesson['objectives']); ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Statistics -->
<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-icon">📚</div>
        <div class="stat-value"><?php echo count($courses); ?></div>
        <div class="stat-label">Corsi Iscritti</div>
    </div>

    <div class="stat-card secondary">
        <div class="stat-icon">📅</div>
        <div class="stat-value"><?php echo count($upcomingLessons); ?></div>
        <div class="stat-label">Lezioni Future</div>
    </div>

    <div class="stat-card accent">
        <div class="stat-icon">🔔</div>
        <div class="stat-value"><?php echo $unreadNotifications; ?></div>
        <div class="stat-label">Notifiche Non Lette</div>
    </div>
</div>

<!-- Your Unique ID -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">🆔 Il Tuo ID Univoco</h3>
    </div>
    <div class="card-body">
        <p>Questo è il tuo ID univoco. Puoi condividerlo con i docenti per velocizzare la tua iscrizione ai corsi:</p>
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

<!-- My Courses -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">📚 I Miei Corsi</h3>
        <a href="<?php echo baseUrl('student/search.php'); ?>" class="btn btn-sm btn-primary">Cerca Nuovo Docente</a>
    </div>
    <div class="card-body">
        <?php if (empty($courses)): ?>
            <p class="text-center" style="padding: 2rem; color: #666;">
                Non sei ancora iscritto a nessun corso. <a href="<?php echo baseUrl('student/search.php'); ?>">Cerca un docente</a> per iniziare!
            </p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($courses as $course): ?>
                    <div class="col-4">
                        <div class="card course-card">
                            <div class="card-body">
                                <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>
                                <p>
                                    👨‍🏫 Prof. <?php echo htmlspecialchars($course['teacher_first_name'] . ' ' . $course['teacher_last_name']); ?>
                                </p>
                                <?php if (!empty($course['description'])): ?>
                                    <p style="font-size: 0.9rem; color: #666;">
                                        <?php echo htmlspecialchars($course['description']); ?>
                                    </p>
                                <?php endif; ?>
                                <div class="mt-3">
                                    <span class="badge badge-student">
                                        Iscritto dal <?php echo formatDate($course['enrolled_at']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Upcoming Lessons -->
<?php if (!empty($upcomingLessons)): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📅 Prossime Lezioni</h3>
            <a href="<?php echo baseUrl('student/calendar.php'); ?>" class="btn btn-sm btn-primary">Visualizza Calendario</a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Corso</th>
                            <th>Docente</th>
                            <th>Data</th>
                            <th>Orario</th>
                            <th>Aula</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $displayedLessons = array_slice($upcomingLessons, 0, 5);
                        foreach ($displayedLessons as $lesson):
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($lesson['course_name']); ?></strong></td>
                                <td>Prof. <?php echo htmlspecialchars($lesson['teacher_last_name']); ?></td>
                                <td><?php echo formatDate($lesson['lesson_date']); ?></td>
                                <td><?php echo formatTime($lesson['start_time']); ?> - <?php echo formatTime($lesson['end_time']); ?></td>
                                <td><?php echo htmlspecialchars($lesson['classroom'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($upcomingLessons) > 5): ?>
                <div class="text-center mt-2">
                    <a href="<?php echo baseUrl('student/calendar.php'); ?>" class="btn btn-outline">Visualizza Tutte le Lezioni</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="row">
    <div class="col-4">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
                <h4>Cerca Docente</h4>
                <p>Trova nuovi corsi a cui iscriverti</p>
                <a href="<?php echo baseUrl('student/search.php'); ?>" class="btn btn-primary">Cerca</a>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size: 3rem; margin-bottom: 1rem;">📅</div>
                <h4>Calendario</h4>
                <p>Visualizza tutte le tue lezioni</p>
                <a href="<?php echo baseUrl('student/calendar.php'); ?>" class="btn btn-primary">Vai al Calendario</a>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card">
            <div class="card-body text-center">
                <div style="font-size: 3rem; margin-bottom: 1rem;">⚙️</div>
                <h4>Impostazioni</h4>
                <p>Gestisci il tuo profilo</p>
                <a href="<?php echo baseUrl('student/profile.php'); ?>" class="btn btn-primary">Vai al Profilo</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
