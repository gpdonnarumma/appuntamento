<?php
/**
 * Registration Page
 * Music School Scheduler
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_client.php';

// If already logged in, redirect
if (isLoggedIn()) {
    $role = getUserRole();
    switch ($role) {
        case ROLE_ADMIN:
            header('Location: ' . baseUrl('admin/index.php'));
            break;
        case ROLE_TEACHER:
            header('Location: ' . baseUrl('teacher/index.php'));
            break;
        case ROLE_STUDENT:
            header('Location: ' . baseUrl('student/index.php'));
            break;
    }
    exit;
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $userType = $_POST['user_type'] ?? '';
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $birthDate = $_POST['birth_date'] ?? '';

    // Validation
    $errors = [];

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email non valida';
    }

    if (strlen($password) < 6) {
        $errors[] = 'La password deve essere di almeno 6 caratteri';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Le password non coincidono';
    }

    if (!in_array($userType, [ROLE_ADMIN, ROLE_TEACHER, ROLE_STUDENT])) {
        $errors[] = 'Tipo utente non valido';
    }

    if (empty($firstName) || empty($lastName)) {
        $errors[] = 'Nome e cognome sono obbligatori';
    }

    if (empty($birthDate)) {
        $errors[] = 'Data di nascita obbligatoria';
    }

    // For admin, school info is required
    if ($userType === ROLE_ADMIN) {
        $schoolName = $_POST['school_name'] ?? '';
        $city = $_POST['city'] ?? '';

        if (empty($schoolName) || empty($city)) {
            $errors[] = 'Nome scuola e città sono obbligatori per gli amministratori';
        }
    }

    if (empty($errors)) {
        $result = apiRegister($email, $password, $userType, $firstName, $lastName, $birthDate);

        if ($result['success']) {
            $_SESSION['user'] = $result['data']['user'];
            $_SESSION['token'] = $result['data']['token'];

            // If admin, create school
            if ($userType === ROLE_ADMIN) {
                $schoolResult = apiCreateSchool($schoolName, $city);
                if (!$schoolResult['success']) {
                    $error = 'Registrazione completata ma errore nella creazione della scuola: ' . $schoolResult['message'];
                }
            }

            // Redirect to appropriate dashboard
            switch ($userType) {
                case ROLE_ADMIN:
                    header('Location: ' . baseUrl('admin/index.php'));
                    break;
                case ROLE_TEACHER:
                    header('Location: ' . baseUrl('teacher/index.php'));
                    break;
                case ROLE_STUDENT:
                    header('Location: ' . baseUrl('student/index.php'));
                    break;
            }
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Registrazione</title>
    <link rel="stylesheet" href="<?php echo assetUrl('css/common.css'); ?>">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
        }

        .register-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 600px;
            padding: 3rem;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-logo {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }

        .register-title {
            font-size: 1.75rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .register-subtitle {
            color: #666;
        }

        .role-selection {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .role-option {
            padding: 1.5rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .role-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .role-option input[type="radio"] {
            display: none;
        }

        .role-option input[type="radio"]:checked + label {
            font-weight: bold;
            color: #667eea;
        }

        .role-option.selected {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .role-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .role-name {
            font-weight: 600;
        }

        .school-fields {
            display: none;
            padding: 1rem;
            background: #f8f9ff;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .school-fields.active {
            display: block;
        }

        .btn-register {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
        }

        .login-link a {
            color: #667eea;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <div class="register-logo">🎵</div>
            <h1 class="register-title">Registrazione</h1>
            <p class="register-subtitle">Crea il tuo account</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin: 0; padding-left: 1.5rem;">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="registerForm">
            <div class="role-selection">
                <div class="role-option" onclick="selectRole('student')">
                    <input type="radio" name="user_type" value="student" id="role_student" required>
                    <label for="role_student">
                        <div class="role-icon">🎓</div>
                        <div class="role-name">Studente</div>
                    </label>
                </div>

                <div class="role-option" onclick="selectRole('teacher')">
                    <input type="radio" name="user_type" value="teacher" id="role_teacher" required>
                    <label for="role_teacher">
                        <div class="role-icon">👨‍🏫</div>
                        <div class="role-name">Docente</div>
                    </label>
                </div>

                <div class="role-option" onclick="selectRole('admin')">
                    <input type="radio" name="user_type" value="admin" id="role_admin" required>
                    <label for="role_admin">
                        <div class="role-icon">🏫</div>
                        <div class="role-name">Scuola</div>
                    </label>
                </div>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label" for="first_name">Nome *</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" required>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label" for="last_name">Cognome *</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="birth_date">Data di Nascita *</label>
                <input type="date" id="birth_date" name="birth_date" class="form-control" required>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="tua@email.com" required>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label" for="password">Password *</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Min. 6 caratteri" required>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Conferma Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="school-fields" id="schoolFields">
                <h4>Dati Scuola</h4>
                <div class="form-group">
                    <label class="form-label" for="school_name">Nome Scuola *</label>
                    <input type="text" id="school_name" name="school_name" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label" for="city">Città *</label>
                    <input type="text" id="city" name="city" class="form-control">
                </div>
            </div>

            <button type="submit" class="btn-register">
                Registrati
            </button>
        </form>

        <div class="login-link">
            Hai già un account? <a href="<?php echo baseUrl('index.php'); ?>">Accedi</a>
        </div>
    </div>

    <script src="<?php echo assetUrl('js/common.js'); ?>"></script>
    <script>
        function selectRole(role) {
            // Remove all selected classes
            document.querySelectorAll('.role-option').forEach(el => {
                el.classList.remove('selected');
            });

            // Add selected class to clicked option
            const selectedOption = document.querySelector(`#role_${role}`).parentElement.parentElement;
            selectedOption.classList.add('selected');

            // Check the radio button
            document.getElementById(`role_${role}`).checked = true;

            // Show/hide school fields
            const schoolFields = document.getElementById('schoolFields');
            if (role === 'admin') {
                schoolFields.classList.add('active');
                document.getElementById('school_name').required = true;
                document.getElementById('city').required = true;
            } else {
                schoolFields.classList.remove('active');
                document.getElementById('school_name').required = false;
                document.getElementById('city').required = false;
            }
        }
    </script>
</body>
</html>
