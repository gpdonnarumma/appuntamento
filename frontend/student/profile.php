<?php
/**
 * Student Profile Management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../api_client.php';

requireRole(ROLE_STUDENT);

$pageTitle = 'Il Mio Profilo';
$user = getCurrentUser();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_profile') {
            $data = [];

            if (!empty($_POST['first_name'])) {
                $data['first_name'] = $_POST['first_name'];
            }
            if (!empty($_POST['last_name'])) {
                $data['last_name'] = $_POST['last_name'];
            }
            if (!empty($_POST['email'])) {
                $data['email'] = $_POST['email'];
            }
            if (!empty($_POST['birth_date'])) {
                $data['birth_date'] = $_POST['birth_date'];
            }
            if (!empty($_POST['profile_photo'])) {
                $data['profile_photo'] = $_POST['profile_photo'];
            }

            if (!empty($data)) {
                $result = apiUpdateUser($user['id'], $data);

                if ($result['success']) {
                    // Update session
                    $_SESSION['user'] = array_merge($_SESSION['user'], $data);
                    setSuccessMessage('Profilo aggiornato con successo!');
                } else {
                    setErrorMessage($result['message']);
                }
            }

            header('Location: ' . baseUrl('student/profile.php'));
            exit;

        } elseif ($_POST['action'] === 'change_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $errors = [];

            if (empty($currentPassword)) {
                $errors[] = 'Password attuale richiesta';
            }
            if (strlen($newPassword) < 6) {
                $errors[] = 'La nuova password deve essere di almeno 6 caratteri';
            }
            if ($newPassword !== $confirmPassword) {
                $errors[] = 'Le nuove password non coincidono';
            }

            if (empty($errors)) {
                $result = apiUpdateUser($user['id'], [
                    'current_password' => $currentPassword,
                    'new_password' => $newPassword
                ]);

                if ($result['success']) {
                    setSuccessMessage('Password cambiata con successo!');
                } else {
                    setErrorMessage($result['message']);
                }
            } else {
                setErrorMessage(implode(', ', $errors));
            }

            header('Location: ' . baseUrl('student/profile.php'));
            exit;
        }
    }
}

// Refresh user data
$userResult = apiGetUser($user['id']);
if ($userResult['success']) {
    $user = $userResult['data'];
    $_SESSION['user'] = $user;
}

// Get student statistics
$coursesResult = apiGetCourse(null, null, $user['id']);
$courses = $coursesResult['success'] ? $coursesResult['data'] : [];

$lessonsResult = apiGetLessons(['student_id' => $user['id']]);
$lessons = $lessonsResult['success'] ? $lessonsResult['data'] : [];

$completedLessons = 0;
$upcomingLessons = 0;
$today = date('Y-m-d');

foreach ($lessons as $lesson) {
    if ($lesson['lesson_date'] < $today) {
        $completedLessons++;
    } else {
        $upcomingLessons++;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">👤 Il Mio Profilo</h1>
    <p class="page-subtitle">Gestisci le tue informazioni personali</p>
</div>

<!-- Profile Information -->
<div class="row">
    <div class="col-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📝 Informazioni Profilo</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="first_name">Nome *</label>
                                <input
                                    type="text"
                                    id="first_name"
                                    name="first_name"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($user['first_name']); ?>"
                                    required
                                >
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="last_name">Cognome *</label>
                                <input
                                    type="text"
                                    id="last_name"
                                    name="last_name"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($user['last_name']); ?>"
                                    required
                                >
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email *</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            value="<?php echo htmlspecialchars($user['email']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="birth_date">Data di Nascita *</label>
                        <input
                            type="date"
                            id="birth_date"
                            name="birth_date"
                            class="form-control"
                            value="<?php echo htmlspecialchars($user['birth_date']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="profile_photo">URL Foto Profilo</label>
                        <input
                            type="url"
                            id="profile_photo"
                            name="profile_photo"
                            class="form-control"
                            value="<?php echo htmlspecialchars($user['profile_photo'] ?? ''); ?>"
                            placeholder="https://esempio.com/foto.jpg"
                        >
                        <small class="form-text">Inserisci l'URL di un'immagine online oppure lascia vuoto per usare l'avatar predefinito.</small>
                    </div>

                    <button type="submit" class="btn btn-primary">💾 Salva Modifiche</button>
                </form>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🔒 Cambia Password</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group">
                        <label class="form-label" for="current_password">Password Attuale *</label>
                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            class="form-control"
                            required
                        >
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="new_password">Nuova Password *</label>
                                <input
                                    type="password"
                                    id="new_password"
                                    name="new_password"
                                    class="form-control"
                                    placeholder="Min. 6 caratteri"
                                    required
                                >
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="form-label" for="confirm_password">Conferma Nuova Password *</label>
                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    class="form-control"
                                    required
                                >
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">🔒 Cambia Password</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-4">
        <!-- Unique ID Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">🆔 ID Univoco</h3>
            </div>
            <div class="card-body text-center">
                <p style="margin: 0 0 1rem 0; color: #666;">Il tuo ID univoco da condividere con i docenti:</p>
                <code style="background: #f5f5f5; padding: 0.75rem 1rem; border-radius: 8px; font-size: 1.5rem; font-weight: bold; display: block; margin-bottom: 1rem;">
                    <?php echo htmlspecialchars($user['unique_id']); ?>
                </code>
                <button class="btn btn-outline" onclick="copyToClipboard('<?php echo htmlspecialchars($user['unique_id']); ?>')">
                    📋 Copia ID
                </button>
                <p style="margin: 1rem 0 0 0; font-size: 0.85rem; color: #999;">
                    I docenti possono usare questo ID per aggiungerti ai loro corsi.
                </p>
            </div>
        </div>

        <!-- Account Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">ℹ️ Informazioni Account</h3>
            </div>
            <div class="card-body">
                <p style="margin: 0 0 0.5rem 0;">
                    <strong>Tipo Account:</strong><br>
                    <span class="badge badge-student">Studente</span>
                </p>
                <p style="margin: 1rem 0 0.5rem 0;">
                    <strong>Registrato il:</strong><br>
                    <?php echo formatDateTime($user['created_at']); ?>
                </p>
                <p style="margin: 1rem 0 0 0;">
                    <strong>Stato:</strong><br>
                    <span class="badge" style="background: #28a745; color: #fff;">✅ Attivo</span>
                </p>
            </div>
        </div>

        <!-- Student Statistics -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📊 Le Tue Statistiche</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>📚 Corsi Iscritti:</span>
                        <strong class="badge badge-student"><?php echo count($courses); ?></strong>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>📅 Lezioni Totali:</span>
                        <strong><?php echo count($lessons); ?></strong>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>✅ Completate:</span>
                        <strong style="color: #28a745;"><?php echo $completedLessons; ?></strong>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>🔜 Future:</span>
                        <strong style="color: #007bff;"><?php echo $upcomingLessons; ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile Photo Preview -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📸 Anteprima Foto</h3>
            </div>
            <div class="card-body text-center">
                <?php if (!empty($user['profile_photo'])): ?>
                    <img
                        src="<?php echo htmlspecialchars($user['profile_photo']); ?>"
                        alt="Foto Profilo"
                        style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid var(--student-primary);"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                    >
                    <div style="display: none; width: 150px; height: 150px; border-radius: 50%; background: var(--student-primary); color: white; font-size: 3rem; align-items: center; justify-content: center; margin: 0 auto;">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                    </div>
                <?php else: ?>
                    <div style="width: 150px; height: 150px; border-radius: 50%; background: var(--student-primary); color: white; font-size: 3rem; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <p style="margin: 1rem 0 0 0; font-size: 0.85rem; color: #999;">
                    Questa è come apparirà la tua foto nel sistema.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
