<?php
/**
 * Student - Calendar
 * Google Calendar-style view of all lessons
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_STUDENT);

$pageTitle = 'Calendario Lezioni';
$user = getCurrentUser();

// Get all lessons for the student
$lessonsResult = apiGetLessons(['student_id' => $user['id']]);
$lessons = $lessonsResult['success'] ? $lessonsResult['data'] : [];

// Get student's courses for filtering
$coursesResult = apiGetCourse(null, null, $user['id']);
$courses = $coursesResult['success'] ? $coursesResult['data'] : [];

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">📅 Calendario Lezioni</h1>
    <p class="page-subtitle">Visualizza tutte le tue lezioni programmate</p>
</div>

<!-- Calendar Controls -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row align-items-center">
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

        <?php if (!empty($courses)): ?>
            <div class="mt-3">
                <label class="form-label">Filtra per Corso:</label>
                <select id="courseFilter" class="form-select" style="max-width: 300px;">
                    <option value="">Tutti i Corsi</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>">
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Calendar View -->
<div class="card">
    <div class="card-body">
        <div id="calendar"></div>
    </div>
</div>

<!-- Upcoming Lessons List -->
<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">🔜 Prossime Lezioni</h3>
    </div>
    <div class="card-body">
        <?php
        $today = date('Y-m-d');
        $upcomingLessons = array_filter($lessons, function($lesson) use ($today) {
            return $lesson['lesson_date'] >= $today;
        });
        usort($upcomingLessons, function($a, $b) {
            return strcmp($a['lesson_date'] . $a['start_time'], $b['lesson_date'] . $b['start_time']);
        });
        $upcomingLessons = array_slice($upcomingLessons, 0, 10);
        ?>

        <?php if (empty($upcomingLessons)): ?>
            <p class="text-center" style="padding: 2rem; color: #666;">
                Non ci sono lezioni future programmate.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Corso</th>
                            <th>Docente</th>
                            <th>Data</th>
                            <th>Orario</th>
                            <th>Aula</th>
                            <th>Obiettivi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcomingLessons as $lesson): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($lesson['course_name']); ?></strong></td>
                                <td>Prof. <?php echo htmlspecialchars($lesson['teacher_last_name']); ?></td>
                                <td><?php echo formatDate($lesson['lesson_date']); ?></td>
                                <td><?php echo formatTime($lesson['start_time']); ?> - <?php echo formatTime($lesson['end_time']); ?></td>
                                <td><?php echo htmlspecialchars($lesson['classroom'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($lesson['objectives'] ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

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
    const lessonsData = <?php echo json_encode($lessons); ?>;
    const coursesData = <?php echo json_encode($courses); ?>;

    // Create color map for courses
    const colors = ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#17a2b8', '#fd7e14', '#20c997'];
    const courseColors = {};
    coursesData.forEach((course, index) => {
        courseColors[course.id] = colors[index % colors.length];
    });

    // Convert lessons to FullCalendar events
    let events = lessonsData.map(lesson => {
        return {
            id: lesson.id,
            title: lesson.course_name,
            start: lesson.lesson_date + 'T' + lesson.start_time,
            end: lesson.lesson_date + 'T' + lesson.end_time,
            backgroundColor: courseColors[lesson.course_id] || '#007bff',
            borderColor: courseColors[lesson.course_id] || '#007bff',
            extendedProps: {
                courseId: lesson.course_id,
                courseName: lesson.course_name,
                teacher: 'Prof. ' + lesson.teacher_last_name,
                classroom: lesson.classroom || '-',
                objectives: lesson.objectives || '-'
            }
        };
    });

    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'it',
        initialView: 'dayGridMonth',
        headerToolbar: false, // We'll use custom controls
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

    // Course filter
    const courseFilter = document.getElementById('courseFilter');
    if (courseFilter) {
        courseFilter.addEventListener('change', function() {
            const selectedCourse = this.value;

            if (selectedCourse === '') {
                calendar.removeAllEventSources();
                calendar.addEventSource(events);
            } else {
                const filteredEvents = events.filter(event =>
                    event.extendedProps.courseId == selectedCourse
                );
                calendar.removeAllEventSources();
                calendar.addEventSource(filteredEvents);
            }
        });
    }

    // Show lesson details in modal
    function showLessonDetails(event) {
        const props = event.extendedProps;
        const modalBody = document.getElementById('lessonModalBody');

        const startDate = new Date(event.start);
        const endDate = new Date(event.end);

        modalBody.innerHTML = `
            <div class="mb-3">
                <h5>${event.title}</h5>
            </div>
            <div class="mb-2">
                <strong>👨‍🏫 Docente:</strong> ${props.teacher}
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
});
</script>

<style>
#calendar {
    background: white;
    border-radius: 8px;
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
}

.fc-col-header-cell {
    background-color: #f8f9fa;
    font-weight: 600;
}

.fc-daygrid-day.fc-day-today {
    background-color: rgba(0, 123, 255, 0.1) !important;
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
