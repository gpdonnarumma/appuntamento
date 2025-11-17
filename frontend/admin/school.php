<?php
/**
 * Admin - School Management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_ADMIN);

$pageTitle = 'Gestione Scuola';
$user = getCurrentUser();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $schoolName = $_POST['school_name'] ?? '';
            $city = $_POST['city'] ?? '';

            if (!empty($schoolName) && !empty($city)) {
                $result = apiCreateSchool($schoolName, $city);

                if ($result['success']) {
                    setSuccessMessage('Scuola creata con successo!');
                } else {
                    setErrorMessage($result['message'] ?? 'Errore durante la creazione della scuola.');
                }
            } else {
                setErrorMessage('Nome scuola e città sono obbligatori.');
            }

            header('Location: ' . baseUrl('admin/school.php'));
            exit;

        } elseif ($_POST['action'] === 'update') {
            $schoolId = $_POST['school_id'] ?? '';
            $data = [];

            if (isset($_POST['school_name'])) {
                $data['school_name'] = $_POST['school_name'];
            }
            if (isset($_POST['city'])) {
                $data['city'] = $_POST['city'];
            }

            if (!empty($schoolId) && !empty($data)) {
                $result = apiUpdateSchool($schoolId, $data);

                if ($result['success']) {
                    setSuccessMessage('Scuola aggiornata con successo!');
                } else {
                    setErrorMessage($result['message'] ?? 'Errore durante l\'aggiornamento.');
                }
            }

            header('Location: ' . baseUrl('admin/school.php'));
            exit;

        } elseif ($_POST['action'] === 'delete') {
            $schoolId = $_POST['school_id'] ?? '';

            if (!empty($schoolId)) {
                $result = apiDeleteSchool($schoolId);

                if ($result['success']) {
                    setSuccessMessage('Scuola eliminata con successo!');
                } else {
                    setErrorMessage($result['message'] ?? 'Errore durante l\'eliminazione.');
                }
            }

            header('Location: ' . baseUrl('admin/school.php'));
            exit;
        }
    }
}

// Get admin's school
$schoolsResult = apiSearchSchools();
$schools = $schoolsResult['success'] ? $schoolsResult['data'] : [];

$school = null;
$schoolDetails = null;

if (!empty($schools)) {
    foreach ($schools as $s) {
        if ($s['admin_id'] == $user['id']) {
            $school = $s;
            break;
        }
    }
}

// Get school details
if ($school) {
    $schoolDetailsResult = apiGetSchool($school['id']);
    if ($schoolDetailsResult['success']) {
        $schoolDetails = $schoolDetailsResult['data'];
    }
}

$viewMode = $_GET['view'] ?? 'view';

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">🏫 Gestione Scuola</h1>
    <p class="page-subtitle">Gestisci le informazioni della tua scuola di musica</p>
</div>

<?php if (!$school && $viewMode !== 'create'): ?>
    <!-- No School - Create First -->
    <div class="card">
        <div class="card-body" style="text-align: center; padding: 3rem;">
            <div style="font-size: 4rem; margin-bottom: 1rem;">🏫</div>
            <h3>Nessuna Scuola Creata</h3>
            <p style="color: #adb5bd; margin: 1rem 0;">
                Per iniziare, crea la tua scuola di musica. Potrai poi aggiungere docenti, studenti e gestire le lezioni.
            </p>
            <a href="<?php echo baseUrl('admin/school.php?view=create'); ?>" class="btn btn-primary">
                ➕ Crea la Tua Scuola
            </a>
        </div>
    </div>

<?php elseif ($viewMode === 'create' || $viewMode === 'edit'): ?>
    <!-- Create/Edit School Form -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <?php echo $viewMode === 'create' ? '➕ Crea Nuova Scuola' : '✏️ Modifica Scuola'; ?>
            </h3>
            <?php if ($school): ?>
                <a href="<?php echo baseUrl('admin/school.php'); ?>" class="btn btn-sm btn-outline-secondary">← Annulla</a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="<?php echo $viewMode === 'create' ? 'create' : 'update'; ?>">
                <?php if ($school): ?>
                    <input type="hidden" name="school_id" value="<?php echo $school['id']; ?>">
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label" for="school_name">Nome Scuola *</label>
                    <input
                        type="text"
                        id="school_name"
                        name="school_name"
                        class="form-control"
                        value="<?php echo htmlspecialchars($school['school_name'] ?? ''); ?>"
                        placeholder="Es: Accademia Musicale Mozart"
                        required
                    >
                    <small class="form-text">Il nome completo della tua scuola di musica.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="city">Città *</label>
                    <input
                        type="text"
                        id="city"
                        name="city"
                        class="form-control"
                        value="<?php echo htmlspecialchars($school['city'] ?? ''); ?>"
                        placeholder="Es: Milano"
                        required
                    >
                    <small class="form-text">La città in cui ha sede la scuola.</small>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $viewMode === 'create' ? '➕ Crea Scuola' : '💾 Salva Modifiche'; ?>
                    </button>
                    <?php if ($school): ?>
                        <a href="<?php echo baseUrl('admin/school.php'); ?>" class="btn btn-outline-secondary">Annulla</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php if ($school && $viewMode === 'edit'): ?>
        <!-- Delete School Section -->
        <div class="card" style="border-color: #dc3545;">
            <div class="card-header" style="background-color: rgba(220, 53, 69, 0.2); border-bottom-color: #dc3545;">
                <h3 class="card-title" style="color: #dc3545;">⚠️ Zona Pericolosa</h3>
            </div>
            <div class="card-body">
                <p><strong>Elimina questa scuola</strong></p>
                <p style="color: #adb5bd;">
                    Una volta eliminata, la scuola e tutti i dati associati non potranno essere recuperati. Tutti i docenti e studenti verranno rimossi.
                </p>
                <form method="POST" action="" onsubmit="return confirm('Sei ASSOLUTAMENTE SICURO di voler eliminare questa scuola? Questa azione è IRREVERSIBILE e cancellerà TUTTI i dati!');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="school_id" value="<?php echo $school['id']; ?>">
                    <button type="submit" class="btn btn-danger">🗑️ Elimina Scuola</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- View School Details -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🏫 Informazioni Scuola</h3>
            <a href="<?php echo baseUrl('admin/school.php?view=edit'); ?>" class="btn btn-sm btn-primary">✏️ Modifica</a>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <h5 style="color: #adb5bd; font-size: 0.9rem; margin-bottom: 0.5rem;">NOME SCUOLA</h5>
                        <p style="font-size: 1.5rem; font-weight: 600; margin: 0;">
                            <?php echo htmlspecialchars($school['school_name']); ?>
                        </p>
                    </div>

                    <div class="mb-4">
                        <h5 style="color: #adb5bd; font-size: 0.9rem; margin-bottom: 0.5rem;">CITTÀ</h5>
                        <p style="font-size: 1.2rem; margin: 0;">
                            📍 <?php echo htmlspecialchars($school['city']); ?>
                        </p>
                    </div>

                    <div class="mb-4">
                        <h5 style="color: #adb5bd; font-size: 0.9rem; margin-bottom: 0.5rem;">CREATA IL</h5>
                        <p style="margin: 0;">
                            <?php echo formatDateTime($school['created_at']); ?>
                        </p>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card" style="background: rgba(13, 110, 253, 0.1); border: 1px solid var(--admin-primary);">
                        <div class="card-body text-center">
                            <h5 style="color: #adb5bd; font-size: 0.9rem; margin-bottom: 1rem;">ID UNIVOCO SCUOLA</h5>
                            <code style="background: #343a40; padding: 0.75rem 1rem; border-radius: 8px; font-size: 1.8rem; font-weight: bold; display: block; margin-bottom: 1rem; color: var(--admin-accent);">
                                <?php echo htmlspecialchars($school['unique_id']); ?>
                            </code>
                            <button class="btn btn-outline-primary" onclick="copyToClipboard('<?php echo htmlspecialchars($school['unique_id']); ?>')">
                                📋 Copia ID
                            </button>
                            <p style="margin: 1rem 0 0 0; font-size: 0.85rem; color: #adb5bd;">
                                I docenti possono usare questo ID per richiedere l'accesso alla scuola.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- School Statistics -->
    <?php if ($schoolDetails): ?>
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon">👨‍🏫</div>
                <div class="stat-value"><?php echo count($schoolDetails['teachers'] ?? []); ?></div>
                <div class="stat-label">Docenti Attivi</div>
            </div>

            <div class="stat-card secondary">
                <div class="stat-icon">🎓</div>
                <div class="stat-value"><?php echo count($schoolDetails['students'] ?? []); ?></div>
                <div class="stat-label">Studenti Iscritti</div>
            </div>

            <div class="stat-card accent">
                <div class="stat-icon">📚</div>
                <div class="stat-value"><?php echo count($schoolDetails['courses'] ?? []); ?></div>
                <div class="stat-label">Corsi Disponibili</div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Quick Links -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">🚀 Azioni Rapide</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <a href="<?php echo baseUrl('admin/teachers.php'); ?>" class="btn btn-outline-primary btn-block mb-2" style="width: 100%; padding: 1rem;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">👨‍🏫</div>
                        <strong>Gestisci Docenti</strong>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?php echo baseUrl('admin/students.php'); ?>" class="btn btn-outline-primary btn-block mb-2" style="width: 100%; padding: 1rem;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">🎓</div>
                        <strong>Visualizza Studenti</strong>
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="<?php echo baseUrl('admin/lessons.php'); ?>" class="btn btn-outline-primary btn-block mb-2" style="width: 100%; padding: 1rem;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">📅</div>
                        <strong>Calendario Lezioni</strong>
                    </a>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
