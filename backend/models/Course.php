<?php
/**
 * Course Model
 * Handles all course-related database operations
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';

class Course {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Create new course
     */
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO courses (teacher_id, course_name, description)
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $data['teacher_id'],
                $data['course_name'],
                $data['description'] ?? null
            ]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Error creating course: " . $e->getMessage());
        }
    }

    /**
     * Get course by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, u.first_name as teacher_first_name, u.last_name as teacher_last_name, u.unique_id as teacher_unique_id
                FROM courses c
                INNER JOIN users u ON c.teacher_id = u.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Error fetching course: " . $e->getMessage());
        }
    }

    /**
     * Update course
     */
    public function update($id, $data) {
        try {
            $fields = [];
            $values = [];

            if (isset($data['course_name'])) {
                $fields[] = "course_name = ?";
                $values[] = $data['course_name'];
            }

            if (isset($data['description'])) {
                $fields[] = "description = ?";
                $values[] = $data['description'];
            }

            if (empty($fields)) {
                throw new Exception("No fields to update");
            }

            $values[] = $id;

            $sql = "UPDATE courses SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);

            return $stmt->execute($values);
        } catch (PDOException $e) {
            throw new Exception("Error updating course: " . $e->getMessage());
        }
    }

    /**
     * Delete course (soft delete)
     */
    public function delete($id) {
        try {
            $stmt = $this->db->prepare("UPDATE courses SET is_active = 0 WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            throw new Exception("Error deleting course: " . $e->getMessage());
        }
    }

    /**
     * Get courses by teacher
     */
    public function getByTeacher($teacherId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*,
                       (SELECT COUNT(*) FROM course_enrollments WHERE course_id = c.id) as enrolled_students
                FROM courses c
                WHERE c.teacher_id = ? AND c.is_active = 1
                ORDER BY c.course_name ASC
            ");
            $stmt->execute([$teacherId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching courses: " . $e->getMessage());
        }
    }

    /**
     * Search courses by teacher unique ID
     */
    public function searchByTeacherUniqueId($teacherUniqueId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, u.first_name as teacher_first_name, u.last_name as teacher_last_name, u.unique_id as teacher_unique_id
                FROM courses c
                INNER JOIN users u ON c.teacher_id = u.id
                WHERE u.unique_id = ? AND c.is_active = 1 AND u.is_active = 1
                ORDER BY c.course_name ASC
            ");
            $stmt->execute([$teacherUniqueId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error searching courses: " . $e->getMessage());
        }
    }

    /**
     * Get enrolled students in course
     */
    public function getEnrolledStudents($courseId) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id, u.email, u.first_name, u.last_name, u.birth_date, u.unique_id, u.profile_photo, ce.enrolled_at
                FROM users u
                INNER JOIN course_enrollments ce ON u.id = ce.student_id
                WHERE ce.course_id = ? AND u.is_active = 1
                ORDER BY u.last_name ASC
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching enrolled students: " . $e->getMessage());
        }
    }

    /**
     * Enroll student in course
     */
    public function enrollStudent($courseId, $studentId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO course_enrollments (student_id, course_id)
                VALUES (?, ?)
            ");

            return $stmt->execute([$studentId, $courseId]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Unique constraint violation
                throw new Exception("Student already enrolled in this course");
            }
            throw new Exception("Error enrolling student: " . $e->getMessage());
        }
    }

    /**
     * Unenroll student from course
     */
    public function unenrollStudent($courseId, $studentId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM course_enrollments
                WHERE course_id = ? AND student_id = ?
            ");

            return $stmt->execute([$courseId, $studentId]);
        } catch (PDOException $e) {
            throw new Exception("Error unenrolling student: " . $e->getMessage());
        }
    }

    /**
     * Check if student is enrolled in course
     */
    public function isStudentEnrolled($courseId, $studentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM course_enrollments
                WHERE course_id = ? AND student_id = ?
            ");
            $stmt->execute([$courseId, $studentId]);
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (PDOException $e) {
            throw new Exception("Error checking enrollment: " . $e->getMessage());
        }
    }

    /**
     * Get courses where student is enrolled
     */
    public function getByStudent($studentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, u.first_name as teacher_first_name, u.last_name as teacher_last_name, u.unique_id as teacher_unique_id, ce.enrolled_at
                FROM courses c
                INNER JOIN users u ON c.teacher_id = u.id
                INNER JOIN course_enrollments ce ON c.id = ce.course_id
                WHERE ce.student_id = ? AND c.is_active = 1
                ORDER BY c.course_name ASC
            ");
            $stmt->execute([$studentId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching courses: " . $e->getMessage());
        }
    }

    /**
     * Get available instruments
     */
    public function getAvailableInstruments() {
        try {
            $stmt = $this->db->prepare("
                SELECT *
                FROM available_instruments
                ORDER BY instrument_name ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching instruments: " . $e->getMessage());
        }
    }

    /**
     * Add custom instrument
     */
    public function addInstrument($instrumentName) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO available_instruments (instrument_name)
                VALUES (?)
            ");

            return $stmt->execute([$instrumentName]);
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Unique constraint violation
                throw new Exception("Instrument already exists");
            }
            throw new Exception("Error adding instrument: " . $e->getMessage());
        }
    }
}
