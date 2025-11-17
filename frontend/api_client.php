<?php
/**
 * API Client
 * Functions to call backend API
 */

require_once __DIR__ . '/config.php';

/**
 * Make API request
 */
function apiRequest($endpoint, $method = 'GET', $data = null, $requireAuth = true) {
    $url = API_BASE_URL . $endpoint;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = ['Content-Type: application/json'];

    // Add authorization header if required
    if ($requireAuth && isLoggedIn()) {
        $token = getToken();
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // Add data for POST/PUT/DELETE
    if (in_array($method, ['POST', 'PUT', 'DELETE']) && $data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode >= 400) {
        return [
            'success' => false,
            'message' => $result['message'] ?? 'Request failed',
            'data' => null
        ];
    }

    return $result;
}

// ============================================
// AUTHENTICATION
// ============================================

function apiRegister($email, $password, $userType, $firstName, $lastName, $birthDate, $profilePhoto = null) {
    $data = [
        'email' => $email,
        'password' => $password,
        'user_type' => $userType,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'birth_date' => $birthDate
    ];

    if ($profilePhoto) {
        $data['profile_photo'] = $profilePhoto;
    }

    return apiRequest('/auth/register.php', 'POST', $data, false);
}

function apiLogin($email, $password) {
    $data = [
        'email' => $email,
        'password' => $password
    ];

    return apiRequest('/auth/login.php', 'POST', $data, false);
}

// ============================================
// USERS
// ============================================

function apiGetUser($id = null, $uniqueId = null) {
    if ($id) {
        return apiRequest('/users/read.php?id=' . $id);
    } elseif ($uniqueId) {
        return apiRequest('/users/read.php?unique_id=' . $uniqueId);
    }
    return apiRequest('/users/read.php');
}

function apiSearchUsers($type = null, $search = null) {
    $params = [];
    if ($type) $params[] = 'type=' . urlencode($type);
    if ($search) $params[] = 'search=' . urlencode($search);

    $query = !empty($params) ? '?' . implode('&', $params) : '';
    return apiRequest('/users/read.php' . $query);
}

function apiUpdateUser($id, $data) {
    $data['id'] = $id;
    return apiRequest('/users/update.php', 'PUT', $data);
}

function apiDeleteUser($id) {
    return apiRequest('/users/delete.php', 'DELETE', ['id' => $id]);
}

// ============================================
// SCHOOLS
// ============================================

function apiCreateSchool($schoolName, $city) {
    return apiRequest('/schools/create.php', 'POST', [
        'school_name' => $schoolName,
        'city' => $city
    ]);
}

function apiGetSchool($id = null, $uniqueId = null) {
    if ($id) {
        return apiRequest('/schools/read.php?id=' . $id);
    } elseif ($uniqueId) {
        return apiRequest('/schools/read.php?unique_id=' . $uniqueId);
    }
    return apiRequest('/schools/read.php');
}

function apiSearchSchools($search = null) {
    $query = $search ? '?search=' . urlencode($search) : '';
    return apiRequest('/schools/read.php' . $query);
}

function apiUpdateSchool($id, $data) {
    $data['id'] = $id;
    return apiRequest('/schools/update.php', 'PUT', $data);
}

function apiDeleteSchool($id) {
    return apiRequest('/schools/delete.php', 'DELETE', ['id' => $id]);
}

// ============================================
// COURSES
// ============================================

function apiCreateCourse($courseName, $description = null) {
    return apiRequest('/courses/create.php', 'POST', [
        'course_name' => $courseName,
        'description' => $description
    ]);
}

function apiGetCourse($id = null, $teacherId = null, $studentId = null, $teacherUniqueId = null) {
    $params = [];
    if ($id) $params[] = 'id=' . $id;
    if ($teacherId) $params[] = 'teacher_id=' . $teacherId;
    if ($studentId) $params[] = 'student_id=' . $studentId;
    if ($teacherUniqueId) $params[] = 'teacher_unique_id=' . urlencode($teacherUniqueId);

    $query = !empty($params) ? '?' . implode('&', $params) : '';
    return apiRequest('/courses/read.php' . $query);
}

function apiGetInstruments() {
    return apiRequest('/courses/read.php?instruments=true');
}

function apiUpdateCourse($id, $data) {
    $data['id'] = $id;
    return apiRequest('/courses/update.php', 'PUT', $data);
}

function apiDeleteCourse($id) {
    return apiRequest('/courses/delete.php', 'DELETE', ['id' => $id]);
}

// ============================================
// LESSONS
// ============================================

function apiCreateLesson($courseId, $studentId, $lessonDate, $startTime, $endTime, $additionalData = []) {
    $data = array_merge([
        'course_id' => $courseId,
        'student_id' => $studentId,
        'lesson_date' => $lessonDate,
        'start_time' => $startTime,
        'end_time' => $endTime
    ], $additionalData);

    return apiRequest('/lessons/create.php', 'POST', $data);
}

function apiGetLessons($filters = []) {
    $params = [];
    foreach ($filters as $key => $value) {
        $params[] = $key . '=' . urlencode($value);
    }

    $query = !empty($params) ? '?' . implode('&', $params) : '';
    return apiRequest('/lessons/read.php' . $query);
}

function apiGetLesson($id) {
    return apiRequest('/lessons/read.php?id=' . $id);
}

function apiGetNextLesson($studentId) {
    return apiRequest('/lessons/read.php?student_id=' . $studentId . '&next=true');
}

function apiGetLessonHistory($studentId, $limit = 50) {
    return apiRequest('/lessons/read.php?student_id=' . $studentId . '&history=true&limit=' . $limit);
}

function apiUpdateLesson($id, $data) {
    $data['id'] = $id;
    return apiRequest('/lessons/update.php', 'PUT', $data);
}

function apiDeleteLesson($id, $deleteRecurring = false, $skipNotification = false, $noAssign = false) {
    return apiRequest('/lessons/delete.php', 'DELETE', [
        'id' => $id,
        'delete_recurring' => $deleteRecurring,
        'skip_notification' => $skipNotification,
        'no_assign' => $noAssign
    ]);
}

// ============================================
// ENROLLMENTS
// ============================================

function apiRequestEnrollment($courseId) {
    return apiRequest('/enrollments/request.php', 'POST', [
        'course_id' => $courseId
    ]);
}

function apiGetEnrollmentRequests($teacherId, $pendingOnly = false) {
    $query = '?teacher_id=' . $teacherId;
    if ($pendingOnly) {
        $query .= '&pending=true';
    }
    return apiRequest('/enrollments/read.php' . $query);
}

function apiApproveEnrollment($requestId) {
    return apiRequest('/enrollments/approve.php', 'POST', [
        'request_id' => $requestId
    ]);
}

function apiRejectEnrollment($requestId) {
    return apiRequest('/enrollments/reject.php', 'POST', [
        'request_id' => $requestId
    ]);
}

// ============================================
// TEACHER REQUESTS
// ============================================

function apiRequestTeacherSchool($schoolId) {
    return apiRequest('/teacher_requests/request.php', 'POST', [
        'school_id' => $schoolId
    ]);
}

function apiGetTeacherRequests($schoolId = null, $teacherId = null, $pendingOnly = false) {
    $params = [];
    if ($schoolId) $params[] = 'school_id=' . $schoolId;
    if ($teacherId) $params[] = 'teacher_id=' . $teacherId;
    if ($pendingOnly) $params[] = 'pending=true';

    $query = !empty($params) ? '?' . implode('&', $params) : '';
    return apiRequest('/teacher_requests/read.php' . $query);
}

function apiApproveTeacherRequest($requestId) {
    return apiRequest('/teacher_requests/approve.php', 'POST', [
        'request_id' => $requestId
    ]);
}

function apiRejectTeacherRequest($requestId) {
    return apiRequest('/teacher_requests/reject.php', 'POST', [
        'request_id' => $requestId
    ]);
}

// ============================================
// NOTIFICATIONS
// ============================================

function apiGetNotifications($unreadOnly = false, $limit = 50) {
    $params = [];
    if ($unreadOnly) $params[] = 'unread=true';
    $params[] = 'limit=' . $limit;

    $query = '?' . implode('&', $params);
    return apiRequest('/notifications/read.php' . $query);
}

function apiMarkNotificationRead($notificationId = null, $markAll = false) {
    $data = [];
    if ($notificationId) {
        $data['notification_id'] = $notificationId;
    }
    if ($markAll) {
        $data['mark_all'] = true;
    }

    return apiRequest('/notifications/mark_read.php', 'POST', $data);
}
