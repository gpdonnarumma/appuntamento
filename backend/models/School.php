<?php
/**
 * School Model
 * Handles all school-related database operations
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';

class School {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Create new school
     */
    public function create($data) {
        try {
            // Generate unique ID for school
            $uniqueId = generateAvailableUniqueId('school');

            $stmt = $this->db->prepare("
                INSERT INTO schools (admin_id, school_name, city, unique_id)
                VALUES (?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['admin_id'],
                $data['school_name'],
                $data['city'],
                $uniqueId
            ]);

            $schoolId = $this->db->lastInsertId();

            return [
                'id' => $schoolId,
                'unique_id' => $uniqueId
            ];
        } catch (PDOException $e) {
            throw new Exception("Error creating school: " . $e->getMessage());
        }
    }

    /**
     * Get school by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT s.*, u.first_name as admin_first_name, u.last_name as admin_last_name, u.email as admin_email
                FROM schools s
                INNER JOIN users u ON s.admin_id = u.id
                WHERE s.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Error fetching school: " . $e->getMessage());
        }
    }

    /**
     * Get school by unique ID
     */
    public function getByUniqueId($uniqueId) {
        try {
            $stmt = $this->db->prepare("
                SELECT s.*, u.first_name as admin_first_name, u.last_name as admin_last_name, u.email as admin_email
                FROM schools s
                INNER JOIN users u ON s.admin_id = u.id
                WHERE s.unique_id = ?
            ");
            $stmt->execute([$uniqueId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Error fetching school: " . $e->getMessage());
        }
    }

    /**
     * Update school
     */
    public function update($id, $data) {
        try {
            $fields = [];
            $values = [];

            if (isset($data['school_name'])) {
                $fields[] = "school_name = ?";
                $values[] = $data['school_name'];
            }

            if (isset($data['city'])) {
                $fields[] = "city = ?";
                $values[] = $data['city'];
            }

            if (empty($fields)) {
                throw new Exception("No fields to update");
            }

            $values[] = $id;

            $sql = "UPDATE schools SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);

            return $stmt->execute($values);
        } catch (PDOException $e) {
            throw new Exception("Error updating school: " . $e->getMessage());
        }
    }

    /**
     * Delete school (soft delete)
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("UPDATE schools SET is_active = 0 WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            throw new Exception("Error deleting school: " . $e->getMessage());
        }
    }

    /**
     * Search schools
     */
    public function search($query = null) {
        try {
            $sql = "
                SELECT s.*, u.first_name as admin_first_name, u.last_name as admin_last_name
                FROM schools s
                INNER JOIN users u ON s.admin_id = u.id
                WHERE s.is_active = 1
            ";

            $params = [];

            if ($query) {
                $sql .= " AND (s.school_name LIKE ? OR s.city LIKE ? OR s.unique_id LIKE ?)";
                $searchTerm = "%$query%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }

            $sql .= " ORDER BY s.school_name ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error searching schools: " . $e->getMessage());
        }
    }

    /**
     * Get all schools
     */
    public function getAll() {
        return $this->search();
    }

    /**
     * Get schools by admin
     */
    public function getByAdmin($adminId) {
        try {
            $stmt = $this->db->prepare("
                SELECT *
                FROM schools
                WHERE admin_id = ? AND is_active = 1
                ORDER BY school_name ASC
            ");
            $stmt->execute([$adminId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching schools: " . $e->getMessage());
        }
    }

    /**
     * Get teachers in school
     */
    public function getTeachers($schoolId) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id, u.email, u.first_name, u.last_name, u.unique_id, u.profile_photo, ts.joined_at
                FROM users u
                INNER JOIN teacher_schools ts ON u.id = ts.teacher_id
                WHERE ts.school_id = ? AND u.is_active = 1
                ORDER BY u.last_name ASC
            ");
            $stmt->execute([$schoolId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching teachers: " . $e->getMessage());
        }
    }

    /**
     * Get students in school
     */
    public function getStudents($schoolId) {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT u.id, u.email, u.first_name, u.last_name, u.unique_id, u.birth_date, u.profile_photo
                FROM users u
                INNER JOIN course_enrollments ce ON u.id = ce.student_id
                INNER JOIN courses c ON ce.course_id = c.id
                INNER JOIN teacher_schools ts ON c.teacher_id = ts.teacher_id
                WHERE ts.school_id = ? AND u.is_active = 1
                ORDER BY u.last_name ASC
            ");
            $stmt->execute([$schoolId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching students: " . $e->getMessage());
        }
    }

    /**
     * Get all lessons in school
     */
    public function getLessons($schoolId, $filters = []) {
        try {
            $sql = "
                SELECT l.*, c.course_name,
                       s.first_name as student_first_name, s.last_name as student_last_name,
                       t.first_name as teacher_first_name, t.last_name as teacher_last_name
                FROM lessons l
                INNER JOIN courses c ON l.course_id = c.id
                INNER JOIN users s ON l.student_id = s.id
                INNER JOIN users t ON l.teacher_id = t.id
                INNER JOIN teacher_schools ts ON t.id = ts.teacher_id
                WHERE ts.school_id = ?
            ";

            $params = [$schoolId];

            if (isset($filters['date_from'])) {
                $sql .= " AND l.lesson_date >= ?";
                $params[] = $filters['date_from'];
            }

            if (isset($filters['date_to'])) {
                $sql .= " AND l.lesson_date <= ?";
                $params[] = $filters['date_to'];
            }

            if (isset($filters['teacher_id'])) {
                $sql .= " AND l.teacher_id = ?";
                $params[] = $filters['teacher_id'];
            }

            if (isset($filters['student_id'])) {
                $sql .= " AND l.student_id = ?";
                $params[] = $filters['student_id'];
            }

            if (isset($filters['status'])) {
                $sql .= " AND l.status = ?";
                $params[] = $filters['status'];
            }

            $sql .= " ORDER BY l.lesson_date DESC, l.start_time DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching lessons: " . $e->getMessage());
        }
    }

    /**
     * Add teacher to school
     */
    public function addTeacher($schoolId, $teacherId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO teacher_schools (teacher_id, school_id)
                VALUES (?, ?)
            ");

            return $stmt->execute([$teacherId, $schoolId]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Unique constraint violation
                throw new Exception("Teacher already added to this school");
            }
            throw new Exception("Error adding teacher to school: " . $e->getMessage());
        }
    }

    /**
     * Remove teacher from school
     */
    public function removeTeacher($schoolId, $teacherId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM teacher_schools
                WHERE school_id = ? AND teacher_id = ?
            ");

            return $stmt->execute([$schoolId, $teacherId]);
        } catch (PDOException $e) {
            throw new Exception("Error removing teacher from school: " . $e->getMessage());
        }
    }

    /**
     * Check if teacher belongs to school
     */
    public function hasTeacher($schoolId, $teacherId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM teacher_schools
                WHERE school_id = ? AND teacher_id = ?
            ");
            $stmt->execute([$schoolId, $teacherId]);
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (PDOException $e) {
            throw new Exception("Error checking teacher: " . $e->getMessage());
        }
    }

    /**
     * Get schools for a teacher
     */
    public function getTeacherSchools($teacherId) {
        try {
            $stmt = $this->db->prepare("
                SELECT s.*, ts.joined_at,
                       u.first_name as admin_first_name,
                       u.last_name as admin_last_name
                FROM schools s
                INNER JOIN teacher_schools ts ON s.id = ts.school_id
                INNER JOIN users u ON s.admin_id = u.id
                WHERE ts.teacher_id = ? AND s.is_active = 1
                ORDER BY s.school_name ASC
            ");
            $stmt->execute([$teacherId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching teacher schools: " . $e->getMessage());
        }
    }
}
