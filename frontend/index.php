<?php
/**
 * Landing Page & Login
 * Music School Scheduler
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api_client.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    $role = getUserRole();
    switch ($role) {
        case ROLE_ADMIN:
            header('Location: /frontend/admin/index.php');
            break;
        case ROLE_TEACHER:
            header('Location: /frontend/teacher/index.php');
            break;
        case ROLE_STUDENT:
            header('Location: /frontend/student/index.php');
            break;
    }
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = apiLogin($email, $password);

    if ($result['success']) {
        $_SESSION['user'] = $result['data']['user'];
        $_SESSION['token'] = $result['data']['token'];

        $role = $result['data']['user']['user_type'];
        switch ($role) {
            case ROLE_ADMIN:
                header('Location: /frontend/admin/index.php');
                break;
            case ROLE_TEACHER:
                header('Location: /frontend/teacher/index.php');
                break;
            case ROLE_STUDENT:
                header('Location: /frontend/student/index.php');
                break;
        }
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Login</title>
    <link rel="stylesheet" href="/frontend/assets/css/common.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            padding: 3rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .login-title {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: #666;
            font-size: 1.1rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
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

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
        }

        .register-link a {
            color: #667eea;
            font-weight: 600;
        }

        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-logo">🎵</div>
            <h1 class="login-title"><?php echo APP_NAME; ?></h1>
            <p class="login-subtitle">Gestione orari scuola di musica</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="login">

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    placeholder="tua@email.com"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    placeholder="••••••••"
                    required
                >
            </div>

            <button type="submit" class="btn-login">
                Accedi
            </button>
        </form>

        <div class="register-link">
            Non hai un account? <a href="/frontend/register.php">Registrati ora</a>
        </div>
    </div>

    <script src="/frontend/assets/js/common.js"></script>
</body>
</html>
