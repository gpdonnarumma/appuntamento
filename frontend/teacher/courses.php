<?php
/**
 * Teacher Courses Management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_TEACHER);

$pageTitle = 'Gestisci Corsi';
$user = getCurrentUser();

// Handle course creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $courseName = $_POST['course_name'] ?? '';
            $description = $_POST['description'] ?? '';

            $result = apiCreateCourse($courseName, $description);

            if ($result['success']) {
                setSuccessMessage('Corso creato con successo!');
            } else {
                setErrorMessage($result['message']);
            }

            header('Location: ' . baseUrl('teacher/courses.php'));
            exit;
        } elseif ($_POST['action'] === 'update') {
            $courseId = $_POST['course_id'];
            $data = [];

            if (isset($_POST['course_name'])) {
                $data['course_name'] = $_POST['course_name'];
            }
            if (isset($_POST['description'])) {
                $data['description'] = $_POST['description'];
            }

            $result = apiUpdateCourse($courseId, $data);

            if ($result['success']) {
                setSuccessMessage('Corso aggiornato con successo!');
            } else {
                setErrorMessage($result['message']);
            }

            header('Location: ' . baseUrl('teacher/courses.php'));
            exit;
        } elseif ($_POST['action'] === 'delete') {
            $courseId = $_POST['course_id'];

            $result = apiDeleteCourse($courseId);

            if ($result['success']) {
                setSuccessMessage('Corso eliminato con successo!');
            } else {
                setErrorMessage($result['message']);
            }

            header('Location: ' . baseUrl('teacher/courses.php'));
            exit;
        }
    }
}

// Get view mode
$viewMode = $_GET['view'] ?? 'list';
$editCourseId = $_GET['edit'] ?? null;
$viewCourseId = $_GET['id'] ?? null;

// Get teacher's courses
$coursesResult = apiGetCourse(null, $user['id']);
$courses = $coursesResult['success'] ? $coursesResult['data'] : [];

// Get course details if viewing/editing
$editCourse = null;
$viewCourse = null;
$courseStudents = [];

if ($editCourseId) {
    foreach ($courses as $c) {
        if ($c['id'] == $editCourseId) {
            $editCourse = $c;
            break;
        }
    }
}

if ($viewCourseId) {
    foreach ($courses as $c) {
        if ($c['id'] == $viewCourseId) {
            $viewCourse = $c;
            // Get enrolled students
            $enrollmentsResult = apiGetEnrollments($viewCourseId);
            if ($enrollmentsResult['success']) {
                $courseStudents = $enrollmentsResult['data'];
            }
            break;
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">📚 Gestisci Corsi</h1>
    <p class="page-subtitle">Crea e gestisci i tuoi corsi</p>
</div>

<?php if ($viewCourse): ?>
    <!-- Course Details View -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo htmlspecialchars($viewCourse['course_name']); ?></h3>
            <div>
                <a href="<?php echo baseUrl('teacher/courses.php?edit=' . $viewCourse['id']); ?>" class="btn btn-sm btn-primary">✏️ Modifica</a>
                <a href="<?php echo baseUrl('teacher/courses.php'); ?>" class="btn btn-sm btn-outline">← Torna ai Corsi</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-6">
                    <p><strong>Descrizione:</strong></p>
                    <p><?php echo nl2br(htmlspecialchars($viewCourse['description'] ?? 'Nessuna descrizione')); ?></p>
                </div>
                <div class="col-6">
                    <p><strong>Studenti Iscritti:</strong> <?php echo count($courseStudents); ?></p>
                    <p><strong>Creato il:</strong> <?php echo formatDateTime($viewCourse['created_at']); ?></p>
                    <p><strong>Ultima Modifica:</strong> <?php echo formatDateTime($viewCourse['updated_at']); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrolled Students -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">👥 Studenti Iscritti (<?php echo count($courseStudents); ?>)</h3>
        </div>
        <div class="card-body">
            <?php if (empty($courseStudents)): ?>
                <p class="text-center" style="padding: 2rem; color: #666;">
                    Nessuno studente iscritto a questo corso.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID Studente</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Data Iscrizione</th>
                                <th>Stato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courseStudents as $enrollment): ?>
                                <tr>
                                    <td><code><?php echo htmlspecialchars($enrollment['student_unique_id']); ?></code></td>
                                    <td><?php echo htmlspecialchars($enrollment['student_first_name'] . ' ' . $enrollment['student_last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($enrollment['student_email']); ?></td>
                                    <td><?php echo formatDateTime($enrollment['enrolled_at']); ?></td>
                                    <td>
                                        <span class="badge badge-teacher">Attivo</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($viewMode === 'create' || $editCourse): ?>
    <!-- Create/Edit Course Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><?php echo $editCourse ? '✏️ Modifica Corso' : '➕ Crea Nuovo Corso'; ?></h3>
            <a href="<?php echo baseUrl('teacher/courses.php'); ?>" class="btn btn-sm btn-outline">← Annulla</a>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo $editCourse ? 'update' : 'create'; ?>">
                <?php if ($editCourse): ?>
                    <input type="hidden" name="course_id" value="<?php echo $editCourse['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label" for="course_name">Nome Corso *</label>
                    <input
                        type="text"
                        id="course_name"
                        name="course_name"
                        class="form-control"
                        value="<?php echo htmlspecialchars($editCourse['course_name'] ?? ''); ?>"
                        placeholder="Es: Corso di Pianoforte - Livello Base"
                        required
                    >
                    <small class="form-text">Specifica lo strumento e il livello nel nome del corso.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Descrizione</label>
                    <textarea
                        id="description"
                        name="description"
                        class="form-control"
                        rows="6"
                        placeholder="Descrivi il corso: strumento, livello richiesto, obiettivi, contenuti delle lezioni, prerequisiti..."
                    ><?php echo htmlspecialchars($editCourse['description'] ?? ''); ?></textarea>
                    <small class="form-text">Una descrizione dettagliata aiuterà gli studenti a capire se il corso fa per loro.</small>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editCourse ? '💾 Salva Modifiche' : '➕ Crea Corso'; ?>
                    </button>
                    <a href="<?php echo baseUrl('teacher/courses.php'); ?>" class="btn btn-outline">Annulla</a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($editCourse): ?>
        <!-- Delete Course Section -->
        <div class="card" style="border-color: #dc3545;">
            <div class="card-header" style="background: #fff5f5; border-bottom-color: #dc3545;">
                <h3 class="card-title" style="color: #dc3545;">⚠️ Zona Pericolosa</h3>
            </div>
            <div class="card-body">
                <p><strong>Elimina questo corso</strong></p>
                <p style="color: #666;">
                    Una volta eliminato, il corso non potrà essere recuperato. Tutti gli studenti iscritti verranno rimossi.
                </p>
                <form method="POST" action="" onsubmit="return confirm('Sei sicuro di voler eliminare questo corso? Questa azione è irreversibile.');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="course_id" value="<?php echo $editCourse['id']; ?>">
                    <button type="submit" class="btn btn-danger">🗑️ Elimina Corso</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- Courses List -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">I Tuoi Corsi (<?php echo count($courses); ?>)</h3>
            <a href="<?php echo baseUrl('teacher/courses.php?view=create'); ?>" class="btn btn-sm btn-primary">➕ Nuovo Corso</a>
        </div>
        <div class="card-body">
            <?php if (empty($courses)): ?>
                <div style="text-align: center; padding: 3rem;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📚</div>
                    <h3>Non hai ancora creato corsi</h3>
                    <p style="color: #666; margin: 1rem 0;">Crea il tuo primo corso per iniziare ad accettare studenti.</p>
                    <a href="<?php echo baseUrl('teacher/courses.php?view=create'); ?>" class="btn btn-primary">➕ Crea il Primo Corso</a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($courses as $course): ?>
                        <div class="col-4">
                            <div class="card course-card" style="margin-bottom: 1.5rem;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h4 style="margin: 0; flex: 1;"><?php echo htmlspecialchars($course['course_name']); ?></h4>
                                    </div>

                                    <?php if (!empty($course['description'])): ?>
                                        <p style="color: #666; font-size: 0.9rem; margin: 0.5rem 0;">
                                            <?php
                                            $desc = $course['description'];
                                            echo htmlspecialchars(strlen($desc) > 80 ? substr($desc, 0, 80) . '...' : $desc);
                                            ?>
                                        </p>
                                    <?php endif; ?>

                                    <div style="margin: 1rem 0;">
                                        <span class="badge badge-teacher">
                                            👥 <?php echo $course['enrolled_students'] ?? 0; ?> studenti iscritti
                                        </span>
                                    </div>

                                    <div class="d-flex gap-2" style="margin-top: 1rem;">
                                        <a href="<?php echo baseUrl('teacher/courses.php?id=' . $course['id']); ?>" class="btn btn-sm btn-primary" style="flex: 1;">
                                            👁️ Dettagli
                                        </a>
                                        <a href="<?php echo baseUrl('teacher/courses.php?edit=' . $course['id']); ?>" class="btn btn-sm btn-outline">
                                            ✏️ Modifica
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

    <!-- Quick Stats -->
    <?php if (!empty($courses)): ?>
        <div class="row">
            <div class="col-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div style="font-size: 3rem; margin-bottom: 0.5rem;">📚</div>
                        <h3 style="margin: 0;"><?php echo count($courses); ?></h3>
                        <p style="color: #666; margin: 0.5rem 0 0 0;">Corsi Totali</p>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div style="font-size: 3rem; margin-bottom: 0.5rem;">👥</div>
                        <h3 style="margin: 0;">
                            <?php
                            $totalStudents = 0;
                            foreach ($courses as $c) {
                                $totalStudents += $c['enrolled_students'] ?? 0;
                            }
                            echo $totalStudents;
                            ?>
                        </h3>
                        <p style="color: #666; margin: 0.5rem 0 0 0;">Studenti Totali</p>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div style="font-size: 3rem; margin-bottom: 0.5rem;">📈</div>
                        <h3 style="margin: 0;">
                            <?php
                            $avg = count($courses) > 0 ? round($totalStudents / count($courses), 1) : 0;
                            echo $avg;
                            ?>
                        </h3>
                        <p style="color: #666; margin: 0.5rem 0 0 0;">Media Studenti/Corso</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
