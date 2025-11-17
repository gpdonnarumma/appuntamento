<?php
/**
 * Teacher Schools Management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_TEACHER);

$pageTitle = 'Le Mie Scuole';
$user = getCurrentUser();

// Handle join school request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['school_id'])) {
    $schoolId = $_POST['school_id'];
    $result = apiRequestTeacherSchool($schoolId);

    if ($result['success']) {
        setSuccessMessage('Richiesta inviata con successo! Attendi l\'approvazione dell\'amministratore.');
    } else {
        setErrorMessage($result['message']);
    }

    header('Location: ' . baseUrl('teacher/schools.php'));
    exit;
}

// Get teacher's schools
$mySchoolsResult = apiGetTeacherSchools($user['id']);
$mySchools = $mySchoolsResult['success'] ? $mySchoolsResult['data'] : [];

// Get pending requests
$pendingRequestsResult = apiGetTeacherPendingRequests($user['id']);
$pendingRequests = $pendingRequestsResult['success'] ? $pendingRequestsResult['data'] : [];

// Search schools
$searchQuery = $_GET['search'] ?? '';
$searchResults = [];

if (!empty($searchQuery)) {
    $searchResult = apiSearchSchools($searchQuery);
    if ($searchResult['success']) {
        $searchResults = $searchResult['data'];

        // Filter out schools already joined or with pending requests
        $mySchoolIds = array_column($mySchools, 'school_id');
        $pendingSchoolIds = array_column($pendingRequests, 'school_id');
        $excludeIds = array_merge($mySchoolIds, $pendingSchoolIds);

        $searchResults = array_filter($searchResults, function($school) use ($excludeIds) {
            return !in_array($school['id'], $excludeIds);
        });
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">🏫 Le Mie Scuole</h1>
    <p class="page-subtitle">Gestisci le tue affiliazioni alle scuole</p>
</div>

<!-- My Schools -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">🏫 Scuole di Appartenenza (<?php echo count($mySchools); ?>)</h3>
    </div>
    <div class="card-body">
        <?php if (empty($mySchools)): ?>
            <div style="text-align: center; padding: 2rem;">
                <div style="font-size: 3rem; margin-bottom: 1rem;">🏫</div>
                <p style="color: #666;">
                    Non sei ancora affiliato a nessuna scuola.<br>
                    Cerca una scuola qui sotto e richiedi di unirti.
                </p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($mySchools as $school): ?>
                    <div class="col-4">
                        <div class="card" style="margin-bottom: 1rem;">
                            <div class="card-body">
                                <h4 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($school['school_name']); ?></h4>
                                <p style="margin: 0; color: #666; font-size: 0.9rem;">
                                    📍 <?php echo htmlspecialchars($school['city']); ?>
                                </p>
                                <p style="margin: 0.5rem 0; color: #666; font-size: 0.9rem;">
                                    🆔 <code><?php echo htmlspecialchars($school['unique_id']); ?></code>
                                </p>
                                <p style="margin: 0.5rem 0 0 0; color: #999; font-size: 0.85rem;">
                                    Affiliato dal <?php echo formatDate($school['joined_at']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Pending Requests -->
<?php if (!empty($pendingRequests)): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">⏳ Richieste in Attesa (<?php echo count($pendingRequests); ?>)</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Scuola</th>
                            <th>Città</th>
                            <th>ID Scuola</th>
                            <th>Data Richiesta</th>
                            <th>Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingRequests as $request): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($request['school_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($request['city']); ?></td>
                                <td><code><?php echo htmlspecialchars($request['school_unique_id']); ?></code></td>
                                <td><?php echo formatDateTime($request['created_at']); ?></td>
                                <td>
                                    <span class="badge" style="background: #ffc107; color: #000;">⏳ In Attesa</span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p style="margin: 1rem 0 0 0; color: #666; font-size: 0.9rem;">
                ℹ️ Le richieste devono essere approvate dall'amministratore della scuola.
            </p>
        </div>
    </div>
<?php endif; ?>

<!-- Search Schools -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">🔍 Cerca Scuola</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="form-group">
                <label class="form-label" for="search">Cerca per nome, città o ID univoco</label>
                <div class="d-flex gap-2">
                    <input
                        type="text"
                        id="search"
                        name="search"
                        class="form-control"
                        placeholder="Es: Conservatorio Milano, Roma, SC1234ABCD..."
                        value="<?php echo htmlspecialchars($searchQuery); ?>"
                    >
                    <button type="submit" class="btn btn-primary">🔍 Cerca</button>
                </div>
                <small class="form-text">
                    Puoi cercare per nome della scuola, città o ID univoco. L'ID univoco è un codice che inizia con "SC" fornito dalla scuola.
                </small>
            </div>
        </form>

        <?php if (!empty($searchQuery)): ?>
            <hr>
            <h4>Risultati Ricerca</h4>

            <?php if (empty($searchResults)): ?>
                <div style="text-align: center; padding: 2rem;">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
                    <p style="color: #666;">
                        Nessuna scuola trovata per "<strong><?php echo htmlspecialchars($searchQuery); ?></strong>"
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive" style="margin-top: 1rem;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Scuola</th>
                                <th>Città</th>
                                <th>ID Univoco</th>
                                <th>Amministratore</th>
                                <th>Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($searchResults as $school): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($school['school_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($school['city']); ?></td>
                                    <td><code><?php echo htmlspecialchars($school['unique_id']); ?></code></td>
                                    <td>
                                        <?php
                                        if (isset($school['admin_first_name'])) {
                                            echo htmlspecialchars($school['admin_first_name'] . ' ' . $school['admin_last_name']);
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="school_id" value="<?php echo $school['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-primary"
                                                    onclick="return confirm('Vuoi richiedere di unirti a questa scuola?');">
                                                ➕ Richiedi Affiliazione
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Info Box -->
<div class="card" style="border-color: #17a2b8; background: #e7f7ff;">
    <div class="card-body">
        <h4 style="margin: 0 0 1rem 0; color: #17a2b8;">ℹ️ Come Funziona</h4>
        <ul style="margin: 0; padding-left: 1.5rem; color: #0c5460;">
            <li>Cerca la scuola a cui vuoi affiliarti usando il nome, la città o l'ID univoco</li>
            <li>Richiedi di unirti alla scuola cliccando su "Richiedi Affiliazione"</li>
            <li>L'amministratore della scuola riceverà la tua richiesta e potrà approvarla o rifiutarla</li>
            <li>Potrai appartenere a più scuole contemporaneamente</li>
            <li>Chiedi all'amministratore della scuola l'ID univoco se non lo trovi con la ricerca</li>
        </ul>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
