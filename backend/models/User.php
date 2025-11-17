<?php
/**
 * User Model
 * Handles all user-related database operations
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';

class User {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Create new user
     */
    public function create($data) {
        try {
            // Generate unique ID
            $uniqueId = generateAvailableUniqueId('user');

            // Hash password
            $passwordHash = hashPassword($data['password']);

            $stmt = $this->db->prepare("
                INSERT INTO users (email, password_hash, user_type, first_name, last_name, birth_date, profile_photo, unique_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['email'],
                $passwordHash,
                $data['user_type'],
                $data['first_name'],
                $data['last_name'],
                $data['birth_date'],
                $data['profile_photo'] ?? null,
                $uniqueId
            ]);

            $userId = $this->db->lastInsertId();

            // If student, create default preferences
            if ($data['user_type'] === 'student') {
                $this->createStudentPreferences($userId);
            }

            return [
                'id' => $userId,
                'unique_id' => $uniqueId
            ];
        } catch (PDOException $e) {
            throw new Exception("Error creating user: " . $e->getMessage());
        }
    }

    /**
     * Get user by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, email, user_type, first_name, last_name, birth_date, profile_photo, unique_id, is_active, created_at, updated_at
                FROM users
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Error fetching user: " . $e->getMessage());
        }
    }

    /**
     * Get user by email
     */
    public function getByEmail($email) {
        try {
            $stmt = $this->db->prepare("
                SELECT *
                FROM users
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Error fetching user: " . $e->getMessage());
        }
    }

    /**
     * Get user by unique ID
     */
    public function getByUniqueId($uniqueId) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, email, user_type, first_name, last_name, birth_date, profile_photo, unique_id, is_active, created_at, updated_at
                FROM users
                WHERE unique_id = ?
            ");
            $stmt->execute([$uniqueId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Error fetching user: " . $e->getMessage());
        }
    }

    /**
     * Update user
     */
    public function update($id, $data) {
        try {
            $fields = [];
            $values = [];

            if (isset($data['first_name'])) {
                $fields[] = "first_name = ?";
                $values[] = $data['first_name'];
            }

            if (isset($data['last_name'])) {
                $fields[] = "last_name = ?";
                $values[] = $data['last_name'];
            }

            if (isset($data['birth_date'])) {
                $fields[] = "birth_date = ?";
                $values[] = $data['birth_date'];
            }

            if (isset($data['profile_photo'])) {
                $fields[] = "profile_photo = ?";
                $values[] = $data['profile_photo'];
            }

            if (isset($data['email'])) {
                $fields[] = "email = ?";
                $values[] = $data['email'];
            }

            if (empty($fields)) {
                throw new Exception("No fields to update");
            }

            $values[] = $id;

            $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);

            return $stmt->execute($values);
        } catch (PDOException $e) {
            throw new Exception("Error updating user: " . $e->getMessage());
        }
    }

    /**
     * Delete user (soft delete)
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            throw new Exception("Error deleting user: " . $e->getMessage());
        }
    }

    /**
     * Search users by type and query
     */
    public function search($userType = null, $query = null) {
        try {
            $sql = "
                SELECT id, email, user_type, first_name, last_name, birth_date, profile_photo, unique_id, created_at
                FROM users
                WHERE is_active = 1
            ";

            $params = [];

            if ($userType) {
                $sql .= " AND user_type = ?";
                $params[] = $userType;
            }

            if ($query) {
                $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR unique_id LIKE ?)";
                $searchTerm = "%$query%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            $sql .= " ORDER BY created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error searching users: " . $e->getMessage());
        }
    }

    /**
     * Authenticate user
     */
    public function authenticate($email, $password) {
        try {
            $user = $this->getByEmail($email);

            if (!$user) {
                return false;
            }

            if (!$user['is_active']) {
                throw new Exception("User account is inactive");
            }

            if (verifyPassword($password, $user['password_hash'])) {
                // Remove password hash from returned data
                unset($user['password_hash']);
                return $user;
            }

            return false;
        } catch (PDOException $e) {
            throw new Exception("Error authenticating user: " . $e->getMessage());
        }
    }

    /**
     * Change password
     */
    public function changePassword($id, $newPassword) {
        try {
            $passwordHash = hashPassword($newPassword);
            $stmt = $this->db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            return $stmt->execute([$passwordHash, $id]);
        } catch (PDOException $e) {
            throw new Exception("Error changing password: " . $e->getMessage());
        }
    }

    /**
     * Create default student preferences
     */
    private function createStudentPreferences($studentId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO student_preferences (student_id, notify_free_slots, notify_before_lesson)
                VALUES (?, 0, 1)
            ");
            return $stmt->execute([$studentId]);
        } catch (PDOException $e) {
            error_log("Error creating student preferences: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get student preferences
     */
    public function getStudentPreferences($studentId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM student_preferences WHERE student_id = ?");
            $stmt->execute([$studentId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Error fetching preferences: " . $e->getMessage());
        }
    }

    /**
     * Update student preferences
     */
    public function updateStudentPreferences($studentId, $data) {
        try {
            $stmt = $this->db->prepare("
                UPDATE student_preferences
                SET notify_free_slots = ?, notify_before_lesson = ?
                WHERE student_id = ?
            ");

            return $stmt->execute([
                $data['notify_free_slots'] ?? 0,
                $data['notify_before_lesson'] ?? 1,
                $studentId
            ]);
        } catch (PDOException $e) {
            throw new Exception("Error updating preferences: " . $e->getMessage());
        }
    }

    /**
     * Get all teachers
     */
    public function getAllTeachers() {
        return $this->search('teacher');
    }

    /**
     * Get all students
     */
    public function getAllStudents() {
        return $this->search('student');
    }

    /**
     * Get all admins
     */
    public function getAllAdmins() {
        return $this->search('admin');
    }
}
