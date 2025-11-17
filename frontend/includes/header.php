<?php
/**
 * Common Header
 * Included in all authenticated pages
 */

if (!isLoggedIn()) {
    header('Location: /frontend/index.php');
    exit;
}

$user = getCurrentUser();
$role = getUserRole();
$roleColor = getRoleColor();
$roleName = getRoleName();

// Get CSS file based on role
$roleCss = '';
switch ($role) {
    case ROLE_ADMIN:
        $roleCss = 'admin.css';
        break;
    case ROLE_TEACHER:
        $roleCss = 'teacher.css';
        break;
    case ROLE_STUDENT:
        $roleCss = 'student.css';
        break;
}

// Get success/error messages
$successMessage = getSuccessMessage();
$errorMessage = getErrorMessage();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? APP_NAME; ?></title>
    <link rel="stylesheet" href="/frontend/assets/css/common.css">
    <link rel="stylesheet" href="/frontend/assets/css/<?php echo $roleCss; ?>">
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="logo">
                <span class="logo-icon">🎵</span>
                <?php echo APP_NAME; ?>
            </div>

            <ul class="nav-menu">
                <?php if ($role === ROLE_ADMIN): ?>
                    <li><a href="/frontend/admin/index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">Dashboard</a></li>
                    <li><a href="/frontend/admin/school.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'school.php' ? 'active' : ''; ?>">Scuola</a></li>
                    <li><a href="/frontend/admin/teachers.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'teachers.php' ? 'active' : ''; ?>">Docenti</a></li>
                    <li><a href="/frontend/admin/students.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'students.php' ? 'active' : ''; ?>">Studenti</a></li>
                    <li><a href="/frontend/admin/lessons.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'lessons.php' ? 'active' : ''; ?>">Lezioni</a></li>
                    <li><a href="/frontend/admin/requests.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'requests.php' ? 'active' : ''; ?>">Richieste</a></li>
                <?php elseif ($role === ROLE_TEACHER): ?>
                    <li><a href="/frontend/teacher/index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">Dashboard</a></li>
                    <li><a href="/frontend/teacher/courses.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'courses.php' ? 'active' : ''; ?>">Corsi</a></li>
                    <li><a href="/frontend/teacher/lessons.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'lessons.php' ? 'active' : ''; ?>">Lezioni</a></li>
                    <li><a href="/frontend/teacher/students.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'students.php' ? 'active' : ''; ?>">Studenti</a></li>
                    <li><a href="/frontend/teacher/requests.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'requests.php' ? 'active' : ''; ?>">Richieste</a></li>
                    <li><a href="/frontend/teacher/schools.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'schools.php' ? 'active' : ''; ?>">Scuole</a></li>
                    <li><a href="/frontend/teacher/profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">Profilo</a></li>
                <?php elseif ($role === ROLE_STUDENT): ?>
                    <li><a href="/frontend/student/index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">Dashboard</a></li>
                    <li><a href="/frontend/student/courses.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'courses.php' ? 'active' : ''; ?>">I Miei Corsi</a></li>
                    <li><a href="/frontend/student/search.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'search.php' ? 'active' : ''; ?>">Cerca Docente</a></li>
                    <li><a href="/frontend/student/calendar.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'calendar.php' ? 'active' : ''; ?>">Calendario</a></li>
                    <li><a href="/frontend/student/notifications.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : ''; ?>">Notifiche</a></li>
                    <li><a href="/frontend/student/profile.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'profile.php' ? 'active' : ''; ?>">Profilo</a></li>
                <?php endif; ?>

                <li>
                    <div class="user-info">
                        <?php if (!empty($user['profile_photo'])): ?>
                            <img src="<?php echo htmlspecialchars($user['profile_photo']); ?>" alt="Avatar" class="user-avatar">
                        <?php else: ?>
                            <div class="user-avatar-placeholder">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <div><strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong></div>
                            <div style="font-size: 0.875rem; opacity: 0.8;"><?php echo $roleName; ?></div>
                        </div>
                    </div>
                </li>

                <li><a href="/frontend/logout.php" class="nav-link">Esci</a></li>
            </ul>
        </nav>
    </header>

    <main class="main-content">
        <div class="container">
            <?php if ($successMessage): ?>
                <div class="alert alert-success alert-dismissible">
                    <?php echo htmlspecialchars($successMessage); ?>
                    <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="alert alert-error alert-dismissible">
                    <?php echo htmlspecialchars($errorMessage); ?>
                    <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
            <?php endif; ?>
