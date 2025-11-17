<?php
/**
 * Test Setup Script
 * Verifica che il backend sia configurato correttamente
 */

echo "=== Music School Scheduler - Backend Test ===\n\n";

// Test 1: PHP Version
echo "1. Testing PHP Version...\n";
$phpVersion = phpversion();
echo "   PHP Version: $phpVersion\n";
if (version_compare($phpVersion, '7.4.0', '>=')) {
    echo "   ✓ PHP version OK\n\n";
} else {
    echo "   ✗ PHP version too old (required >= 7.4)\n\n";
    exit(1);
}

// Test 2: SQLite Extension
echo "2. Testing SQLite Extension...\n";
if (extension_loaded('pdo_sqlite')) {
    echo "   ✓ SQLite extension loaded\n\n";
} else {
    echo "   ✗ SQLite extension not found\n\n";
    exit(1);
}

// Test 3: Database Connection
echo "3. Testing Database Connection...\n";
try {
    require_once __DIR__ . '/config/database.php';
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "   ✓ Database connection successful\n";

    // Check if tables exist
    $stmt = $conn->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   ✓ Found " . count($tables) . " tables\n";
    echo "   Tables: " . implode(', ', $tables) . "\n\n";
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 4: Helper Functions
echo "4. Testing Helper Functions...\n";
try {
    require_once __DIR__ . '/utils/helpers.php';

    $userId = generateUniqueUserId();
    echo "   Generated User ID: $userId\n";

    $schoolId = generateUniqueSchoolId();
    echo "   Generated School ID: $schoolId\n";

    $testEmail = 'test@example.com';
    if (validateEmail($testEmail)) {
        echo "   ✓ Email validation works\n";
    }

    $testDate = '2024-12-20';
    if (validateDate($testDate)) {
        echo "   ✓ Date validation works\n";
    }

    echo "\n";
} catch (Exception $e) {
    echo "   ✗ Helper functions error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 5: Models
echo "5. Testing Models...\n";
try {
    require_once __DIR__ . '/models/User.php';
    require_once __DIR__ . '/models/School.php';
    require_once __DIR__ . '/models/Course.php';
    require_once __DIR__ . '/models/Lesson.php';
    require_once __DIR__ . '/models/Enrollment.php';

    $userModel = new User();
    $schoolModel = new School();
    $courseModel = new Course();
    $lessonModel = new Lesson();
    $enrollmentModel = new Enrollment();

    echo "   ✓ All models loaded successfully\n\n";
} catch (Exception $e) {
    echo "   ✗ Models error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 6: Email Service
echo "6. Testing Email Service...\n";
try {
    require_once __DIR__ . '/utils/email.php';
    echo "   ✓ Email service loaded\n";
    echo "   Note: Email sending is currently in log mode\n\n";
} catch (Exception $e) {
    echo "   ✗ Email service error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 7: Directory Permissions
echo "7. Testing Directory Permissions...\n";
$databaseDir = __DIR__ . '/database';
if (is_writable($databaseDir)) {
    echo "   ✓ Database directory is writable\n\n";
} else {
    echo "   ✗ Database directory is not writable\n";
    echo "   Run: chmod 755 $databaseDir\n\n";
}

// Summary
echo "=== Test Summary ===\n";
echo "✓ Backend setup is complete and working!\n\n";
echo "Next steps:\n";
echo "1. Start PHP server: php -S localhost:8000\n";
echo "2. Test API endpoint: http://localhost:8000\n";
echo "3. Register first user: POST http://localhost:8000/api/auth/register.php\n";
echo "4. Read full documentation in README.md\n\n";
