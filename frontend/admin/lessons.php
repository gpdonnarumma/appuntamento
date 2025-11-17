<?php
/**
 * Admin - Lessons Management
 * Calendar view of all lessons in the school
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_ADMIN);

$pageTitle = 'Calendario Lezioni';
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

// Get all lessons for the school
$lessons = [];
$teachers = [];
$students = [];

if ($school) {
    // Get school details to get teachers and students
    $schoolDetailsResult = apiGetSchool($school['id']);
    if ($schoolDetailsResult['success']) {
        $teachers = $schoolDetailsResult['data']['teachers'] ?? [];
        $students = $schoolDetailsResult['data']['students'] ?? [];
    }

    // Get all lessons (we'll filter by school teachers)
    $lessonsResult = apiGetLessons([]);
    if ($lessonsResult['success']) {
        $allLessons = $lessonsResult['data'];

        // Filter lessons that belong to our school's teachers
        $teacherIds = array_column($teachers, 'id');
        $lessons = array_filter($allLessons, function($lesson) use ($teacherIds) {
            return in_array($lesson['teacher_id'], $teacherIds);
        });
    }
}

// Get filter parameters
$filterTeacher = $_GET['teacher'] ?? '';
$filterStudent = $_GET['student'] ?? '';
$filterDate = $_GET['date'] ?? '';

// Apply filters
$filteredLessons = $lessons;

if (!empty($filterTeacher)) {
    $filteredLessons = array_filter($filteredLessons, function($lesson) use ($filterTeacher) {
        return $lesson['teacher_id'] == $filterTeacher;
    });
}

if (!empty($filterStudent)) {
    $filteredLessons = array_filter($filteredLessons, function($lesson) use ($filterStudent) {
        return $lesson['student_id'] == $filterStudent;
    });
}

if (!empty($filterDate)) {
    $filteredLessons = array_filter($filteredLessons, function($lesson) use ($filterDate) {
        return $lesson['lesson_date'] == $filterDate;
    });
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">📅 Calendario Lezioni</h1>
    <p class="page-subtitle">Visualizza tutte le lezioni programmate nella scuola</p>
</div>

<?php if (!$school): ?>
    <div class="alert alert-warning">
        Non hai ancora creato una scuola. <a href="<?php echo baseUrl('admin/school.php'); ?>" style="text-decoration: underline;">Crea la tua scuola prima</a>
    </div>

<?php else: ?>

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">🔍 Filtri</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label" for="teacher">Docente</label>
                        <select name="teacher" id="teacher" class="form-select">
                            <option value="">Tutti i Docenti</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>" <?php echo $filterTeacher == $teacher['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="student">Studente</label>
                        <select name="student" id="student" class="form-select">
                            <option value="">Tutti gli Studenti</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>" <?php echo $filterStudent == $student['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="date">Data</label>
                        <input type="date" name="date" id="date" class="form-control" value="<?php echo htmlspecialchars($filterDate); ?>">
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">Applica Filtri</button>
                        <?php if (!empty($filterTeacher) || !empty($filterStudent) || !empty($filterDate)): ?>
                            <a href="<?php echo baseUrl('admin/lessons.php'); ?>" class="btn btn-outline-secondary">Cancella</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($lessons)): ?>
        <div class="card">
            <div class="card-body" style="text-align: center; padding: 3rem;">
                <div style="font-size: 4rem; margin-bottom: 1rem;">📅</div>
                <h3>Nessuna Lezione Programmata</h3>
                <p style="color: #adb5bd; margin: 1rem 0;">
                    Le lezioni programmate dai docenti appariranno qui.
                </p>
                <p style="color: #adb5bd;">
                    I docenti possono creare lezioni dalla loro dashboard.
                </p>
            </div>
        </div>

    <?php else: ?>
        <!-- Statistics -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">📅</div>
                <div class="stat-value"><?php echo count($filteredLessons); ?></div>
                <div class="stat-label"><?php echo (!empty($filterTeacher) || !empty($filterStudent) || !empty($filterDate)) ? 'Lezioni Filtrate' : 'Lezioni Totali'; ?></div>
            </div>

            <div class="stat-card secondary">
                <div class="stat-icon">📆</div>
                <div class="stat-value">
                    <?php
                    $today = date('Y-m-d');
                    $todayLessons = array_filter($filteredLessons, function($lesson) use ($today) {
                        return $lesson['lesson_date'] == $today;
                    });
                    echo count($todayLessons);
                    ?>
                </div>
                <div class="stat-label">Lezioni Oggi</div>
            </div>

            <div class="stat-card accent">
                <div class="stat-icon">🔜</div>
                <div class="stat-value">
                    <?php
                    $futureLessons = array_filter($filteredLessons, function($lesson) use ($today) {
                        return $lesson['lesson_date'] > $today;
                    });
                    echo count($futureLessons);
                    ?>
                </div>
                <div class="stat-label">Lezioni Future</div>
            </div>
        </div>

        <!-- Calendar View -->
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">📅 Calendario</h3>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-primary" id="prevMonth">
                                ← Precedente
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="today">
                                Oggi
                            </button>
                            <button type="button" class="btn btn-outline-primary" id="nextMonth">
                                Successivo →
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <h4 id="currentMonth" class="mb-0"></h4>
                    </div>
                </div>

                <div id="calendar"></div>
            </div>
        </div>

        <!-- Lessons List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📋 Lista Lezioni</h3>
            </div>
            <div class="card-body">
                <?php if (empty($filteredLessons)): ?>
                    <p class="text-center" style="padding: 2rem; color: #adb5bd;">
                        Nessuna lezione trovata con questi filtri.
                    </p>
                <?php else: ?>
                    <?php
                    // Sort lessons by date and time
                    $sortedLessons = $filteredLessons;
                    usort($sortedLessons, function($a, $b) {
                        $dateCompare = strcmp($a['lesson_date'], $b['lesson_date']);
                        if ($dateCompare === 0) {
                            return strcmp($a['start_time'], $b['start_time']);
                        }
                        return $dateCompare;
                    });
                    ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Orario</th>
                                    <th>Corso</th>
                                    <th>Docente</th>
                                    <th>Studente</th>
                                    <th>Aula</th>
                                    <th>Stato</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $today = date('Y-m-d');
                                foreach ($sortedLessons as $lesson):
                                    $isPast = $lesson['lesson_date'] < $today;
                                    $isToday = $lesson['lesson_date'] == $today;
                                ?>
                                    <tr style="<?php echo $isToday ? 'background: rgba(13, 110, 253, 0.1);' : ''; ?>">
                                        <td>
                                            <?php echo formatDate($lesson['lesson_date']); ?>
                                            <?php if ($isToday): ?>
                                                <span class="badge bg-info ms-2">Oggi</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo formatTime($lesson['start_time']); ?> - <?php echo formatTime($lesson['end_time']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($lesson['course_name']); ?></strong></td>
                                        <td>Prof. <?php echo htmlspecialchars($lesson['teacher_first_name'] . ' ' . $lesson['teacher_last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lesson['student_first_name'] . ' ' . $lesson['student_last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lesson['classroom'] ?? '-'); ?></td>
                                        <td>
                                            <?php if ($isPast): ?>
                                                <span class="badge bg-secondary">Completata</span>
                                            <?php elseif ($isToday): ?>
                                                <span class="badge bg-warning">In Corso</span>
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

    <?php endif; ?>

<?php endif; ?>

<!-- Lesson Detail Modal -->
<div class="modal fade" id="lessonModal" tabindex="-1" aria-labelledby="lessonModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="lessonModalLabel">Dettagli Lezione</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="lessonModalBody">
                <!-- Content will be populated by JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>

<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css' rel='stylesheet' />

<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/it.global.min.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');

    <?php if (!empty($lessons)): ?>
    const lessonsData = <?php echo json_encode(array_values($filteredLessons)); ?>;

    // Convert lessons to FullCalendar events
    const events = lessonsData.map(lesson => {
        return {
            id: lesson.id,
            title: lesson.course_name + ' - ' + lesson.student_first_name + ' ' + lesson.student_last_name,
            start: lesson.lesson_date + 'T' + lesson.start_time,
            end: lesson.lesson_date + 'T' + lesson.end_time,
            backgroundColor: '#0d6efd',
            borderColor: '#0d6efd',
            extendedProps: {
                courseName: lesson.course_name,
                teacherName: 'Prof. ' + lesson.teacher_first_name + ' ' + lesson.teacher_last_name,
                studentName: lesson.student_first_name + ' ' + lesson.student_last_name,
                classroom: lesson.classroom || '-',
                objectives: lesson.objectives || '-'
            }
        };
    });

    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'it',
        initialView: 'dayGridMonth',
        headerToolbar: false,
        height: 'auto',
        events: events,
        eventClick: function(info) {
            showLessonDetails(info.event);
        },
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        }
    });

    calendar.render();

    // Update month display
    function updateMonthDisplay() {
        const date = calendar.getDate();
        const options = { year: 'numeric', month: 'long' };
        document.getElementById('currentMonth').textContent =
            date.toLocaleDateString('it-IT', options);
    }
    updateMonthDisplay();

    // Navigation buttons
    document.getElementById('prevMonth').addEventListener('click', () => {
        calendar.prev();
        updateMonthDisplay();
    });

    document.getElementById('nextMonth').addEventListener('click', () => {
        calendar.next();
        updateMonthDisplay();
    });

    document.getElementById('today').addEventListener('click', () => {
        calendar.today();
        updateMonthDisplay();
    });

    // Show lesson details in modal
    function showLessonDetails(event) {
        const props = event.extendedProps;
        const modalBody = document.getElementById('lessonModalBody');

        const startDate = new Date(event.start);
        const endDate = new Date(event.end);

        modalBody.innerHTML = `
            <div class="mb-3">
                <h5>${props.courseName}</h5>
            </div>
            <div class="mb-2">
                <strong>👨‍🏫 Docente:</strong> ${props.teacherName}
            </div>
            <div class="mb-2">
                <strong>🎓 Studente:</strong> ${props.studentName}
            </div>
            <div class="mb-2">
                <strong>📅 Data:</strong> ${startDate.toLocaleDateString('it-IT', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                })}
            </div>
            <div class="mb-2">
                <strong>🕐 Orario:</strong> ${startDate.toLocaleTimeString('it-IT', {
                    hour: '2-digit',
                    minute: '2-digit'
                })} - ${endDate.toLocaleTimeString('it-IT', {
                    hour: '2-digit',
                    minute: '2-digit'
                })}
            </div>
            <div class="mb-2">
                <strong>📍 Aula:</strong> ${props.classroom}
            </div>
            ${props.objectives !== '-' ? `
                <div class="mb-2">
                    <strong>🎯 Obiettivi:</strong> ${props.objectives}
                </div>
            ` : ''}
        `;

        const modal = new bootstrap.Modal(document.getElementById('lessonModal'));
        modal.show();
    }
    <?php endif; ?>
});
</script>

<style>
#calendar {
    background: var(--admin-card-bg);
    border-radius: 8px;
    padding: 1rem;
}

.fc {
    font-family: inherit;
}

.fc-event {
    cursor: pointer;
    border-radius: 4px;
    padding: 2px 4px;
}

.fc-event:hover {
    opacity: 0.8;
}

.fc-daygrid-day-number {
    font-size: 0.9rem;
    padding: 4px;
    color: #e9ecef;
}

.fc-col-header-cell {
    background-color: var(--admin-dark);
    font-weight: 600;
    color: #e9ecef;
}

.fc-daygrid-day.fc-day-today {
    background-color: rgba(13, 110, 253, 0.2) !important;
}

.fc-theme-standard td, .fc-theme-standard th {
    border-color: var(--admin-border);
}

.fc-theme-standard .fc-scrollgrid {
    border-color: var(--admin-border);
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
