<?php
/**
 * Enrollment Model
 * Handles enrollment requests and teacher-school requests
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';

class Enrollment {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Create enrollment request (student -> course)
     */
    public function createEnrollmentRequest($studentId, $courseId, $teacherId) {
        try {
            // Check if already enrolled
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM course_enrollments
                WHERE student_id = ? AND course_id = ?
            ");
            $stmt->execute([$studentId, $courseId]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                throw new Exception("Student already enrolled in this course");
            }

            // Check if pending request already exists
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM enrollment_requests
                WHERE student_id = ? AND course_id = ? AND status = 'pending'
            ");
            $stmt->execute([$studentId, $courseId]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                throw new Exception("Enrollment request already pending");
            }

            // Create request
            $stmt = $this->db->prepare("
                INSERT INTO enrollment_requests (student_id, course_id, teacher_id, status)
                VALUES (?, ?, ?, 'pending')
            ");

            $stmt->execute([$studentId, $courseId, $teacherId]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Error creating enrollment request: " . $e->getMessage());
        }
    }

    /**
     * Get enrollment request by ID
     */
    public function getEnrollmentRequestById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT er.*,
                       s.first_name as student_first_name, s.last_name as student_last_name, s.email as student_email,
                       c.course_name,
                       t.first_name as teacher_first_name, t.last_name as teacher_last_name
                FROM enrollment_requests er
                INNER JOIN users s ON er.student_id = s.id
                INNER JOIN courses c ON er.course_id = c.id
                INNER JOIN users t ON er.teacher_id = t.id
                WHERE er.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Error fetching enrollment request: " . $e->getMessage());
        }
    }

    /**
     * Get pending enrollment requests for teacher
     */
    public function getPendingRequestsForTeacher($teacherId) {
        try {
            $stmt = $this->db->prepare("
                SELECT er.*,
                       s.first_name as student_first_name, s.last_name as student_last_name, s.email as student_email, s.unique_id as student_unique_id,
                       c.course_name
                FROM enrollment_requests er
                INNER JOIN users s ON er.student_id = s.id
                INNER JOIN courses c ON er.course_id = c.id
                WHERE er.teacher_id = ? AND er.status = 'pending'
                ORDER BY er.created_at DESC
            ");
            $stmt->execute([$teacherId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching enrollment requests: " . $e->getMessage());
        }
    }

    /**
     * Get all enrollment requests for teacher (including approved/rejected)
     */
    public function getAllRequestsForTeacher($teacherId) {
        try {
            $stmt = $this->db->prepare("
                SELECT er.*,
                       s.first_name as student_first_name, s.last_name as student_last_name, s.email as student_email,
                       c.course_name
                FROM enrollment_requests er
                INNER JOIN users s ON er.student_id = s.id
                INNER JOIN courses c ON er.course_id = c.id
                WHERE er.teacher_id = ?
                ORDER BY er.status ASC, er.created_at DESC
            ");
            $stmt->execute([$teacherId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching enrollment requests: " . $e->getMessage());
        }
    }

    /**
     * Approve enrollment request
     */
    public function approveEnrollmentRequest($requestId) {
        try {
            $this->db->beginTransaction();

            // Get request details
            $request = $this->getEnrollmentRequestById($requestId);

            if (!$request) {
                throw new Exception("Enrollment request not found");
            }

            if ($request['status'] !== 'pending') {
                throw new Exception("Request already processed");
            }

            // Update request status
            $stmt = $this->db->prepare("
                UPDATE enrollment_requests
                SET status = 'approved'
                WHERE id = ?
            ");
            $stmt->execute([$requestId]);

            // Add to course enrollments
            $stmt = $this->db->prepare("
                INSERT INTO course_enrollments (student_id, course_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$request['student_id'], $request['course_id']]);

            $this->db->commit();

            return true;
        } catch (PDOException $e) {
            $this->db->rollback();
            throw new Exception("Error approving enrollment request: " . $e->getMessage());
        }
    }

    /**
     * Reject enrollment request
     */
    public function rejectEnrollmentRequest($requestId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE enrollment_requests
                SET status = 'rejected'
                WHERE id = ?
            ");

            return $stmt->execute([$requestId]);
        } catch (PDOException $e) {
            throw new Exception("Error rejecting enrollment request: " . $e->getMessage());
        }
    }

    /**
     * Create teacher-school request
     */
    public function createTeacherSchoolRequest($teacherId, $schoolId) {
        try {
            // Check if already in school
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM teacher_schools
                WHERE teacher_id = ? AND school_id = ?
            ");
            $stmt->execute([$teacherId, $schoolId]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                throw new Exception("Teacher already in this school");
            }

            // Check if pending request already exists
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM teacher_school_requests
                WHERE teacher_id = ? AND school_id = ? AND status = 'pending'
            ");
            $stmt->execute([$teacherId, $schoolId]);
            $result = $stmt->fetch();

            if ($result['count'] > 0) {
                throw new Exception("Request already pending");
            }

            // Create request
            $stmt = $this->db->prepare("
                INSERT INTO teacher_school_requests (teacher_id, school_id, status)
                VALUES (?, ?, 'pending')
            ");

            $stmt->execute([$teacherId, $schoolId]);

            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Error creating teacher-school request: " . $e->getMessage());
        }
    }

    /**
     * Get teacher-school request by ID
     */
    public function getTeacherSchoolRequestById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT tsr.*,
                       t.first_name as teacher_first_name, t.last_name as teacher_last_name, t.email as teacher_email, t.unique_id as teacher_unique_id,
                       s.school_name, s.city, s.admin_id
                FROM teacher_school_requests tsr
                INNER JOIN users t ON tsr.teacher_id = t.id
                INNER JOIN schools s ON tsr.school_id = s.id
                WHERE tsr.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Error fetching teacher-school request: " . $e->getMessage());
        }
    }

    /**
     * Get pending teacher-school requests for admin
     */
    public function getPendingTeacherRequestsForSchool($schoolId) {
        try {
            $stmt = $this->db->prepare("
                SELECT tsr.*,
                       t.first_name as teacher_first_name, t.last_name as teacher_last_name, t.email as teacher_email, t.unique_id as teacher_unique_id, t.birth_date as teacher_birth_date
                FROM teacher_school_requests tsr
                INNER JOIN users t ON tsr.teacher_id = t.id
                WHERE tsr.school_id = ? AND tsr.status = 'pending'
                ORDER BY tsr.created_at DESC
            ");
            $stmt->execute([$schoolId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching teacher requests: " . $e->getMessage());
        }
    }

    /**
     * Get all teacher-school requests for admin
     */
    public function getAllTeacherRequestsForSchool($schoolId) {
        try {
            $stmt = $this->db->prepare("
                SELECT tsr.*,
                       t.first_name as teacher_first_name, t.last_name as teacher_last_name, t.email as teacher_email
                FROM teacher_school_requests tsr
                INNER JOIN users t ON tsr.teacher_id = t.id
                WHERE tsr.school_id = ?
                ORDER BY tsr.status ASC, tsr.created_at DESC
            ");
            $stmt->execute([$schoolId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching teacher requests: " . $e->getMessage());
        }
    }

    /**
     * Approve teacher-school request
     */
    public function approveTeacherSchoolRequest($requestId) {
        try {
            $this->db->beginTransaction();

            // Get request details
            $request = $this->getTeacherSchoolRequestById($requestId);

            if (!$request) {
                throw new Exception("Request not found");
            }

            if ($request['status'] !== 'pending') {
                throw new Exception("Request already processed");
            }

            // Update request status
            $stmt = $this->db->prepare("
                UPDATE teacher_school_requests
                SET status = 'approved'
                WHERE id = ?
            ");
            $stmt->execute([$requestId]);

            // Add to teacher_schools
            $stmt = $this->db->prepare("
                INSERT INTO teacher_schools (teacher_id, school_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$request['teacher_id'], $request['school_id']]);

            $this->db->commit();

            return true;
        } catch (PDOException $e) {
            $this->db->rollback();
            throw new Exception("Error approving teacher-school request: " . $e->getMessage());
        }
    }

    /**
     * Reject teacher-school request
     */
    public function rejectTeacherSchoolRequest($requestId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE teacher_school_requests
                SET status = 'rejected'
                WHERE id = ?
            ");

            return $stmt->execute([$requestId]);
        } catch (PDOException $e) {
            throw new Exception("Error rejecting teacher-school request: " . $e->getMessage());
        }
    }

    /**
     * Get teacher's pending requests
     */
    public function getTeacherPendingRequests($teacherId) {
        try {
            $stmt = $this->db->prepare("
                SELECT tsr.*,
                       s.school_name, s.city, s.unique_id as school_unique_id
                FROM teacher_school_requests tsr
                INNER JOIN schools s ON tsr.school_id = s.id
                WHERE tsr.teacher_id = ? AND tsr.status = 'pending'
                ORDER BY tsr.created_at DESC
            ");
            $stmt->execute([$teacherId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching pending requests: " . $e->getMessage());
        }
    }

    /**
     * Get enrolled students for a course
     */
    public function getEnrollmentsByCourse($courseId) {
        try {
            $stmt = $this->db->prepare("
                SELECT ce.*,
                       s.first_name as student_first_name,
                       s.last_name as student_last_name,
                       s.email as student_email,
                       s.unique_id as student_unique_id,
                       s.birth_date as student_birth_date
                FROM course_enrollments ce
                INNER JOIN users s ON ce.student_id = s.id
                WHERE ce.course_id = ?
                ORDER BY ce.enrolled_at DESC
            ");
            $stmt->execute([$courseId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching course enrollments: " . $e->getMessage());
        }
    }
}
