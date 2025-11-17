<?php
/**
 * Teacher Lessons Management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_TEACHER);

$pageTitle = 'Gestisci Lezioni';
$user = getCurrentUser();

// Handle lesson creation/update/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $courseId = $_POST['course_id'] ?? '';
            $studentId = $_POST['student_id'] ?? '';
            $lessonDate = $_POST['lesson_date'] ?? '';
            $startTime = $_POST['start_time'] ?? '';
            $endTime = $_POST['end_time'] ?? '';

            $additionalData = [];
            if (!empty($_POST['classroom'])) $additionalData['classroom'] = $_POST['classroom'];
            if (!empty($_POST['objectives'])) $additionalData['objectives'] = $_POST['objectives'];
            if (!empty($_POST['private_notes'])) $additionalData['private_notes'] = $_POST['private_notes'];

            // Handle recurring lessons
            if (isset($_POST['is_recurring']) && $_POST['is_recurring'] === '1') {
                $additionalData['is_recurring'] = 1;
                $additionalData['recurrence_pattern'] = $_POST['recurrence_pattern'] ?? 'weekly';
            }

            $result = apiCreateLesson($courseId, $studentId, $lessonDate, $startTime, $endTime, $additionalData);

            if ($result['success']) {
                if (isset($additionalData['is_recurring']) && $additionalData['is_recurring'] === 1) {
                    setSuccessMessage('Lezioni ricorrenti create con successo! (53 occorrenze totali)');
                } else {
                    setSuccessMessage('Lezione creata con successo!');
                }
            } else {
                setErrorMessage($result['message']);
            }

            header('Location: ' . baseUrl('teacher/lessons.php'));
            exit;

        } elseif ($_POST['action'] === 'update') {
            $lessonId = $_POST['lesson_id'];
            $data = [];

            if (!empty($_POST['lesson_date'])) $data['lesson_date'] = $_POST['lesson_date'];
            if (!empty($_POST['start_time'])) $data['start_time'] = $_POST['start_time'];
            if (!empty($_POST['end_time'])) $data['end_time'] = $_POST['end_time'];
            if (isset($_POST['classroom'])) $data['classroom'] = $_POST['classroom'];
            if (isset($_POST['objectives'])) $data['objectives'] = $_POST['objectives'];
            if (isset($_POST['private_notes'])) $data['private_notes'] = $_POST['private_notes'];
            if (isset($_POST['status'])) $data['status'] = $_POST['status'];

            $result = apiUpdateLesson($lessonId, $data);

            if ($result['success']) {
                setSuccessMessage('Lezione aggiornata con successo!');
            } else {
                setErrorMessage($result['message']);
            }

            header('Location: ' . baseUrl('teacher/lessons.php'));
            exit;

        } elseif ($_POST['action'] === 'delete') {
            $lessonId = $_POST['lesson_id'];
            $deleteRecurring = isset($_POST['delete_recurring']) ? true : false;

            $result = apiDeleteLesson($lessonId, $deleteRecurring);

            if ($result['success']) {
                setSuccessMessage($deleteRecurring ? 'Serie di lezioni eliminate con successo!' : 'Lezione eliminata con successo!');
            } else {
                setErrorMessage($result['message']);
            }

            header('Location: ' . baseUrl('teacher/lessons.php'));
            exit;
        }
    }
}

// Get view mode
$viewMode = $_GET['view'] ?? 'list';
$lessonId = $_GET['id'] ?? null;
$editLessonId = $_GET['edit'] ?? null;

// Get teacher's courses
$coursesResult = apiGetCourse(null, $user['id']);
$courses = $coursesResult['success'] ? $coursesResult['data'] : [];

// Get students for selected course (for create form)
$selectedCourseId = $_GET['course'] ?? ($_POST['course_id'] ?? null);
$courseStudents = [];
if ($selectedCourseId) {
    $enrollmentsResult = apiGetEnrollments($selectedCourseId);
    if ($enrollmentsResult['success']) {
        $courseStudents = $enrollmentsResult['data'];
    }
}

// Get filters
$filterCourse = $_GET['filter_course'] ?? null;
$filterStudent = $_GET['filter_student'] ?? null;
$filterDate = $_GET['filter_date'] ?? null;
$filterStatus = $_GET['filter_status'] ?? 'future'; // future, past, all

// Build lessons filter
$lessonsFilter = ['teacher_id' => $user['id']];
if ($filterCourse) $lessonsFilter['course_id'] = $filterCourse;
if ($filterStudent) $lessonsFilter['student_id'] = $filterStudent;
if ($filterDate) $lessonsFilter['date'] = $filterDate;

if ($filterStatus === 'future') {
    $lessonsFilter['date_from'] = date('Y-m-d');
} elseif ($filterStatus === 'past') {
    $lessonsFilter['date_to'] = date('Y-m-d', strtotime('-1 day'));
}

// Get lessons
$lessonsResult = apiGetLessons($lessonsFilter);
$lessons = $lessonsResult['success'] ? $lessonsResult['data'] : [];

// Get specific lesson if viewing/editing
$viewLesson = null;
$editLesson = null;

if ($lessonId) {
    $result = apiGetLesson($lessonId);
    if ($result['success']) {
        $viewLesson = $result['data'];
    }
}

if ($editLessonId) {
    $result = apiGetLesson($editLessonId);
    if ($result['success']) {
        $editLesson = $result['data'];
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">📅 Gestisci Lezioni</h1>
    <p class="page-subtitle">Crea e gestisci le tue lezioni</p>
</div>

<?php if ($viewMode === 'create' || $editLesson): ?>
    <!-- Create/Edit Lesson Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo $editLesson ? '✏️ Modifica Lezione' : '➕ Crea Nuova Lezione'; ?></h3>
            <a href="<?php echo baseUrl('teacher/lessons.php'); ?>" class="btn btn-sm btn-outline">← Annulla</a>
        </div>
        <div class="card-body">
            <form method="POST" action="" id="lessonForm">
                <input type="hidden" name="action" value="<?php echo $editLesson ? 'update' : 'create'; ?>">
                <?php if ($editLesson): ?>
                    <input type="hidden" name="lesson_id" value="<?php echo $editLesson['id']; ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label" for="course_id">Corso *</label>
                            <select
                                id="course_id"
                                name="course_id"
                                class="form-control"
                                required
                                <?php echo $editLesson ? 'disabled' : 'onchange="this.form.submit()"'; ?>
                            >
                                <option value="">Seleziona corso...</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>"
                                            <?php echo ($selectedCourseId == $course['id'] || ($editLesson && $editLesson['course_id'] == $course['id'])) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label" for="student_id">Studente *</label>
                            <select
                                id="student_id"
                                name="student_id"
                                class="form-control"
                                required
                                <?php echo $editLesson ? 'disabled' : ''; ?>
                            >
                                <option value="">Seleziona studente...</option>
                                <?php foreach ($courseStudents as $enrollment): ?>
                                    <option value="<?php echo $enrollment['student_id']; ?>"
                                            <?php echo ($editLesson && $editLesson['student_id'] == $enrollment['student_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($enrollment['student_first_name'] . ' ' . $enrollment['student_last_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($courseStudents) && !$editLesson): ?>
                                <small class="form-text" style="color: #dc3545;">Seleziona prima un corso con studenti iscritti.</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label" for="lesson_date">Data Lezione *</label>
                            <input
                                type="date"
                                id="lesson_date"
                                name="lesson_date"
                                class="form-control"
                                value="<?php echo $editLesson ? htmlspecialchars($editLesson['lesson_date']) : ''; ?>"
                                min="<?php echo date('Y-m-d'); ?>"
                                required
                            >
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label" for="start_time">Ora Inizio *</label>
                            <input
                                type="time"
                                id="start_time"
                                name="start_time"
                                class="form-control"
                                value="<?php echo $editLesson ? htmlspecialchars(substr($editLesson['start_time'], 0, 5)) : ''; ?>"
                                required
                            >
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label" for="end_time">Ora Fine *</label>
                            <input
                                type="time"
                                id="end_time"
                                name="end_time"
                                class="form-control"
                                value="<?php echo $editLesson ? htmlspecialchars(substr($editLesson['end_time'], 0, 5)) : ''; ?>"
                                required
                            >
                        </div>
                    </div>
                </div>

                <?php if (!$editLesson): ?>
                    <div class="form-group" style="background: #f8f9ff; padding: 1rem; border-radius: 8px; border: 2px solid var(--teacher-primary);">
                        <label class="form-label">
                            <input type="checkbox" id="is_recurring" name="is_recurring" value="1" onchange="toggleRecurrence()">
                            🔄 Lezione Ricorrente
                        </label>
                        <div id="recurrenceOptions" style="display: none; margin-top: 1rem;">
                            <label class="form-label" for="recurrence_pattern">Frequenza:</label>
                            <select id="recurrence_pattern" name="recurrence_pattern" class="form-control">
                                <option value="weekly">Settimanale (52 occorrenze)</option>
                                <option value="monthly">Mensile (52 occorrenze)</option>
                            </select>
                            <small class="form-text">
                                ℹ️ Verrà creata automaticamente questa lezione più altre 52 occorrenze future con la stessa ora e durata.
                            </small>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label" for="classroom">Aula/Luogo</label>
                    <input
                        type="text"
                        id="classroom"
                        name="classroom"
                        class="form-control"
                        value="<?php echo $editLesson ? htmlspecialchars($editLesson['classroom'] ?? '') : ''; ?>"
                        placeholder="Es: Aula 101, Sala Prove, Online"
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="objectives">Obiettivi della Lezione</label>
                    <textarea
                        id="objectives"
                        name="objectives"
                        class="form-control"
                        rows="3"
                        placeholder="Cosa si imparerà in questa lezione..."
                    ><?php echo $editLesson ? htmlspecialchars($editLesson['objectives'] ?? '') : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label" for="private_notes">Note Private</label>
                    <textarea
                        id="private_notes"
                        name="private_notes"
                        class="form-control"
                        rows="2"
                        placeholder="Note visibili solo a te..."
                    ><?php echo $editLesson ? htmlspecialchars($editLesson['private_notes'] ?? '') : ''; ?></textarea>
                    <small class="form-text">Queste note sono visibili solo a te, non allo studente.</small>
                </div>

                <?php if ($editLesson): ?>
                    <div class="form-group">
                        <label class="form-label" for="status">Stato</label>
                        <select id="status" name="status" class="form-control">
                            <option value="scheduled" <?php echo $editLesson['status'] === 'scheduled' ? 'selected' : ''; ?>>📅 Programmata</option>
                            <option value="completed" <?php echo $editLesson['status'] === 'completed' ? 'selected' : ''; ?>>✅ Completata</option>
                            <option value="cancelled" <?php echo $editLesson['status'] === 'cancelled' ? 'selected' : ''; ?>>❌ Cancellata</option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editLesson ? '💾 Salva Modifiche' : '➕ Crea Lezione'; ?>
                    </button>
                    <a href="<?php echo baseUrl('teacher/lessons.php'); ?>" class="btn btn-outline">Annulla</a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($editLesson && $editLesson['parent_lesson_id']): ?>
        <!-- Warning for recurring lessons -->
        <div class="card" style="border-color: #ffc107; background: #fff9e6;">
            <div class="card-body">
                <p style="margin: 0; color: #856404;">
                    ⚠️ <strong>Attenzione:</strong> Questa lezione fa parte di una serie ricorrente. Le modifiche si applicano solo a questa singola lezione.
                </p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($editLesson): ?>
        <!-- Delete Lesson -->
        <div class="card" style="border-color: #dc3545;">
            <div class="card-header" style="background: #fff5f5; border-bottom-color: #dc3545;">
                <h3 class="card-title" style="color: #dc3545;">⚠️ Elimina Lezione</h3>
            </div>
            <div class="card-body">
                <?php if ($editLesson['parent_lesson_id'] === null && $editLesson['is_recurring']): ?>
                    <!-- This is a parent recurring lesson -->
                    <p><strong>Questa è una lezione ricorrente padre.</strong></p>
                    <form method="POST" action="" style="display: inline; margin-right: 1rem;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="lesson_id" value="<?php echo $editLesson['id']; ?>">
                        <button type="submit" class="btn btn-danger"
                                onclick="return confirm('Eliminare solo questa lezione?');">
                            🗑️ Elimina Solo Questa
                        </button>
                    </form>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="lesson_id" value="<?php echo $editLesson['id']; ?>">
                        <input type="hidden" name="delete_recurring" value="1">
                        <button type="submit" class="btn btn-danger"
                                onclick="return confirm('ATTENZIONE: Eliminare TUTTA la serie di lezioni ricorrenti? Questa azione è irreversibile.');">
                            🗑️ Elimina Tutta la Serie
                        </button>
                    </form>
                <?php else: ?>
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="lesson_id" value="<?php echo $editLesson['id']; ?>">
                        <button type="submit" class="btn btn-danger"
                                onclick="return confirm('Sei sicuro di voler eliminare questa lezione?');">
                            🗑️ Elimina Lezione
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- Lessons List -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Filtri</h3>
            <a href="<?php echo baseUrl('teacher/lessons.php?view=create'); ?>" class="btn btn-sm btn-primary">➕ Nuova Lezione</a>
        </div>
        <div class="card-body" style="padding: 1rem;">
            <form method="GET" action="" class="d-flex gap-2 align-items-end">
                <div class="form-group" style="margin: 0; min-width: 200px;">
                    <label class="form-label" for="filter_course" style="font-size: 0.9rem;">Corso:</label>
                    <select id="filter_course" name="filter_course" class="form-control form-control-sm">
                        <option value="">Tutti i corsi</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo $filterCourse == $course['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin: 0; min-width: 150px;">
                    <label class="form-label" for="filter_status" style="font-size: 0.9rem;">Periodo:</label>
                    <select id="filter_status" name="filter_status" class="form-control form-control-sm">
                        <option value="future" <?php echo $filterStatus === 'future' ? 'selected' : ''; ?>>Future</option>
                        <option value="past" <?php echo $filterStatus === 'past' ? 'selected' : ''; ?>>Passate</option>
                        <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>Tutte</option>
                    </select>
                </div>

                <div class="form-group" style="margin: 0;">
                    <label class="form-label" for="filter_date" style="font-size: 0.9rem;">Data specifica:</label>
                    <input type="date" id="filter_date" name="filter_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filterDate ?? ''); ?>">
                </div>

                <button type="submit" class="btn btn-sm btn-primary">🔍 Filtra</button>
                <a href="<?php echo baseUrl('teacher/lessons.php'); ?>" class="btn btn-sm btn-outline">✕ Reset</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Lezioni (<?php echo count($lessons); ?>)</h3>
        </div>
        <div class="card-body">
            <?php if (empty($lessons)): ?>
                <div style="text-align: center; padding: 3rem;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📅</div>
                    <h3>Nessuna lezione trovata</h3>
                    <p style="color: #666;">
                        <?php if ($filterStatus === 'future'): ?>
                            Non hai lezioni programmate. <a href="<?php echo baseUrl('teacher/lessons.php?view=create'); ?>">Crea la prima lezione</a>
                        <?php else: ?>
                            Nessuna lezione trovata con i filtri selezionati.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Orario</th>
                                <th>Corso</th>
                                <th>Studente</th>
                                <th>Aula</th>
                                <th>Stato</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lessons as $lesson): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo formatDate($lesson['lesson_date']); ?></strong>
                                        <?php if ($lesson['is_recurring'] && $lesson['parent_lesson_id'] === null): ?>
                                            <br><span class="badge" style="background: #17a2b8; color: #fff; font-size: 0.75rem;">🔄 Ricorrente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo formatTime($lesson['start_time']); ?> -
                                        <?php echo formatTime($lesson['end_time']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($lesson['course_name']); ?></td>
                                    <td><?php echo htmlspecialchars($lesson['student_first_name'] . ' ' . $lesson['student_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($lesson['classroom'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($lesson['status'] === 'scheduled'): ?>
                                            <span class="badge" style="background: #17a2b8; color: #fff;">📅 Programmata</span>
                                        <?php elseif ($lesson['status'] === 'completed'): ?>
                                            <span class="badge" style="background: #28a745; color: #fff;">✅ Completata</span>
                                        <?php elseif ($lesson['status'] === 'cancelled'): ?>
                                            <span class="badge" style="background: #dc3545; color: #fff;">❌ Cancellata</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo baseUrl('teacher/lessons.php?edit=' . $lesson['id']); ?>" class="btn btn-sm btn-outline" title="Modifica">
                                            ✏️
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

<script>
function toggleRecurrence() {
    const checkbox = document.getElementById('is_recurring');
    const options = document.getElementById('recurrenceOptions');
    options.style.display = checkbox.checked ? 'block' : 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
