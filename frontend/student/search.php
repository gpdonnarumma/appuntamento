<?php
/**
 * Student - Search Teacher
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_STUDENT);

$pageTitle = 'Cerca Docente';
$user = getCurrentUser();

$searchResults = [];
$searchPerformed = false;

// Handle search
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['teacher_id'])) {
    $teacherId = $_GET['teacher_id'];
    $coursesResult = apiGetCourse(null, null, null, $teacherId);

    if ($coursesResult['success']) {
        $searchResults = $coursesResult['data'];
        $searchPerformed = true;
    }
}

// Handle enrollment request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
    $courseId = $_POST['course_id'];
    $result = apiRequestEnrollment($courseId);

    if ($result['success']) {
        setSuccessMessage('Richiesta di iscrizione inviata con successo!');
    } else {
        setErrorMessage($result['message']);
    }

    header('Location: ' . baseUrl('student/search.php'));
    exit;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">🔍 Cerca Docente</h1>
    <p class="page-subtitle">Inserisci l'ID univoco del docente per trovare i suoi corsi</p>
</div>

<!-- Search Form -->
<div class="card">
    <div class="card-body">
        <form method="GET" action="">
            <div class="row">
                <div class="col-8">
                    <input
                        type="text"
                        name="teacher_id"
                        class="form-control"
                        placeholder="Inserisci ID Docente (es. ABC12345)"
                        value="<?php echo htmlspecialchars($_GET['teacher_id'] ?? ''); ?>"
                        required
                    >
                </div>
                <div class="col-4">
                    <button type="submit" class="btn btn-primary btn-block">
                        🔍 Cerca Corsi
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Search Results -->
<?php if ($searchPerformed): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Risultati della Ricerca</h3>
        </div>
        <div class="card-body">
            <?php if (empty($searchResults)): ?>
                <p class="text-center" style="padding: 2rem; color: #666;">
                    ❌ Nessun docente trovato con questo ID. Verifica l'ID e riprova.
                </p>
            <?php else: ?>
                <?php
                // Group by teacher
                $teacher = null;
                if (!empty($searchResults)) {
                    $firstCourse = $searchResults[0];
                    $teacher = [
                        'name' => $firstCourse['teacher_first_name'] . ' ' . $firstCourse['teacher_last_name'],
                        'unique_id' => $firstCourse['teacher_unique_id']
                    ];
                }
                ?>

                <?php if ($teacher): ?>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 2rem;">
                        <h4>👨‍🏫 <?php echo htmlspecialchars($teacher['name']); ?></h4>
                        <p style="color: #666; margin-bottom: 0;">
                            ID: <code><?php echo htmlspecialchars($teacher['unique_id']); ?></code>
                        </p>
                    </div>
                <?php endif; ?>

                <h4>Corsi Disponibili:</h4>
                <div class="row">
                    <?php foreach ($searchResults as $course): ?>
                        <div class="col-6">
                            <div class="card course-card">
                                <div class="card-body">
                                    <h4><?php echo htmlspecialchars($course['course_name']); ?></h4>

                                    <?php if (!empty($course['description'])): ?>
                                        <p style="color: #666;">
                                            <?php echo htmlspecialchars($course['description']); ?>
                                        </p>
                                    <?php endif; ?>

                                    <form method="POST" action="">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" class="btn btn-primary btn-block mt-3">
                                            📝 Invia Richiesta di Iscrizione
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Help Card -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">ℹ️ Come Funziona</h3>
    </div>
    <div class="card-body">
        <ol>
            <li><strong>Richiedi l'ID</strong> - Chiedi al docente il suo ID univoco</li>
            <li><strong>Cerca</strong> - Inserisci l'ID nella barra di ricerca sopra</li>
            <li><strong>Scegli un corso</strong> - Visualizza tutti i corsi del docente</li>
            <li><strong>Invia richiesta</strong> - Clicca su "Invia Richiesta di Iscrizione"</li>
            <li><strong>Attendi</strong> - Il docente riceverà una notifica e dovrà approvarti</li>
        </ol>
        <p class="mt-3" style="color: #666;">
            💡 <strong>Suggerimento:</strong> Puoi anche fornire il tuo ID univoco al docente, così può aggiungerti direttamente!
        </p>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
