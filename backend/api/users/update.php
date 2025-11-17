<?php
/**
 * Update User API
 * PUT /api/users/update.php
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/School.php';
require_once __DIR__ . '/../../utils/helpers.php';
require_once __DIR__ . '/../../utils/email.php';

enableCORS();

if (getRequestMethod() !== 'PUT') {
    sendError('Method not allowed', 405);
}

try {
    // Authenticate user
    $currentUser = authenticateUser();

    $input = getJsonInput();

    if (!isset($input['id'])) {
        sendError('User ID is required', 400);
    }

    $userId = $input['id'];

    $userModel = new User();
    $user = $userModel->getById($userId);

    if (!$user) {
        sendError('User not found', 404);
    }

    // Permission checks
    $canModify = false;

    // User can modify their own profile
    if ($currentUser['user_id'] == $userId) {
        $canModify = true;
    }

    // Teacher can modify student data (will require admin approval if admin exists)
    if ($currentUser['user_type'] === 'teacher' && $user['user_type'] === 'student') {
        // Check if teacher teaches this student
        $db = getDB();
        $stmt = $db->prepare("
            SELECT COUNT(*) as count
            FROM course_enrollments ce
            INNER JOIN courses c ON ce.course_id = c.id
            WHERE c.teacher_id = ? AND ce.student_id = ?
        ");
        $stmt->execute([$currentUser['user_id'], $userId]);
        $result = $stmt->fetch();

        if ($result['count'] > 0) {
            // Check if there's an admin in any school the teacher belongs to
            $stmt = $db->prepare("
                SELECT COUNT(*) as count
                FROM teacher_schools ts
                INNER JOIN schools s ON ts.school_id = s.id
                WHERE ts.teacher_id = ?
            ");
            $stmt->execute([$currentUser['user_id']]);
            $schoolResult = $stmt->fetch();

            if ($schoolResult['count'] > 0) {
                // Send notification to admin(s) for approval
                $stmt = $db->prepare("
                    SELECT DISTINCT s.admin_id
                    FROM teacher_schools ts
                    INNER JOIN schools s ON ts.school_id = s.id
                    WHERE ts.teacher_id = ?
                ");
                $stmt->execute([$currentUser['user_id']]);
                $admins = $stmt->fetchAll();

                foreach ($admins as $admin) {
                    EmailService::createNotification(
                        $admin['admin_id'],
                        'student_data_change',
                        'Richiesta modifica dati studente',
                        "Il docente richiede di modificare i dati di {$user['first_name']} {$user['last_name']}",
                        $userId,
                        'user'
                    );
                }

                sendSuccess('Modification request sent to administrator for approval');
            } else {
                $canModify = true;
            }
        }
    }

    // Admin can modify any user
    if ($currentUser['user_type'] === 'admin') {
        $canModify = true;
    }

    if (!$canModify) {
        sendError('Insufficient permissions to modify this user', 403);
    }

    // Sanitize and update
    $updateData = [];

    if (isset($input['first_name'])) {
        $updateData['first_name'] = sanitizeInput($input['first_name']);
    }

    if (isset($input['last_name'])) {
        $updateData['last_name'] = sanitizeInput($input['last_name']);
    }

    if (isset($input['birth_date'])) {
        if (!validateDate($input['birth_date'])) {
            sendError('Invalid birth date format. Use YYYY-MM-DD', 400);
        }
        $updateData['birth_date'] = $input['birth_date'];
    }

    if (isset($input['profile_photo'])) {
        $updateData['profile_photo'] = sanitizeInput($input['profile_photo']);
    }

    if (isset($input['email']) && $currentUser['user_id'] == $userId) {
        if (!validateEmail($input['email'])) {
            sendError('Invalid email format', 400);
        }
        $updateData['email'] = sanitizeInput($input['email']);
    }

    if (empty($updateData)) {
        sendError('No fields to update', 400);
    }

    $userModel->update($userId, $updateData);

    // Update student preferences if provided
    if ($user['user_type'] === 'student' && isset($input['preferences'])) {
        $userModel->updateStudentPreferences($userId, $input['preferences']);
    }

    // Get updated user
    $updatedUser = $userModel->getById($userId);

    sendSuccess('User updated successfully', $updatedUser);

} catch (Exception $e) {
    sendError('Failed to update user: ' . $e->getMessage(), 500);
}
