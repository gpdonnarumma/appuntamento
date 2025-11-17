<?php
/**
 * Lesson Model
 * Handles all lesson-related database operations
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';

class Lesson {
    private $db;

    public function __construct() {
        $this->db = getDB();
    }

    /**
     * Create new lesson
     */
    public function create($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO lessons (
                    course_id, student_id, teacher_id, lesson_date, start_time, end_time,
                    classroom, private_notes, objectives, is_recurring, recurrence_pattern,
                    parent_lesson_id, status, skip_notification
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['course_id'],
                $data['student_id'],
                $data['teacher_id'],
                $data['lesson_date'],
                $data['start_time'],
                $data['end_time'],
                $data['classroom'] ?? null,
                $data['private_notes'] ?? null,
                $data['objectives'] ?? null,
                $data['is_recurring'] ?? 0,
                $data['recurrence_pattern'] ?? null,
                $data['parent_lesson_id'] ?? null,
                $data['status'] ?? 'scheduled',
                $data['skip_notification'] ?? 0
            ]);

            $lessonId = $this->db->lastInsertId();

            // If recurring, create future lessons
            if (isset($data['is_recurring']) && $data['is_recurring'] == 1 && isset($data['recurrence_pattern'])) {
                $this->createRecurringLessons($lessonId, $data);
            }

            return $lessonId;
        } catch (PDOException $e) {
            throw new Exception("Error creating lesson: " . $e->getMessage());
        }
    }

    /**
     * Create recurring lessons
     */
    private function createRecurringLessons($parentLessonId, $data) {
        try {
            $occurrences = 52; // Create lessons for 1 year
            $currentDate = new DateTime($data['lesson_date']);

            for ($i = 1; $i <= $occurrences; $i++) {
                if ($data['recurrence_pattern'] === 'weekly') {
                    $currentDate->modify('+1 week');
                } elseif ($data['recurrence_pattern'] === 'monthly') {
                    $currentDate->modify('+1 month');
                }

                $stmt = $this->db->prepare("
                    INSERT INTO lessons (
                        course_id, student_id, teacher_id, lesson_date, start_time, end_time,
                        classroom, private_notes, objectives, is_recurring, recurrence_pattern,
                        parent_lesson_id, status, skip_notification
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $data['course_id'],
                    $data['student_id'],
                    $data['teacher_id'],
                    $currentDate->format('Y-m-d'),
                    $data['start_time'],
                    $data['end_time'],
                    $data['classroom'] ?? null,
                    $data['private_notes'] ?? null,
                    $data['objectives'] ?? null,
                    $data['recurrence_pattern'],
                    $parentLessonId,
                    'scheduled',
                    $data['skip_notification'] ?? 0
                ]);
            }
        } catch (PDOException $e) {
            error_log("Error creating recurring lessons: " . $e->getMessage());
        }
    }

    /**
     * Get lesson by ID
     */
    public function getById($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT l.*,
                       c.course_name,
                       s.first_name as student_first_name, s.last_name as student_last_name, s.email as student_email,
                       t.first_name as teacher_first_name, t.last_name as teacher_last_name
                FROM lessons l
                INNER JOIN courses c ON l.course_id = c.id
                INNER JOIN users s ON l.student_id = s.id
                INNER JOIN users t ON l.teacher_id = t.id
                WHERE l.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Error fetching lesson: " . $e->getMessage());
        }
    }

    /**
     * Update lesson
     */
    public function update($id, $data, $updateRecurring = false) {
        try {
            $fields = [];
            $values = [];

            if (isset($data['lesson_date'])) {
                $fields[] = "lesson_date = ?";
                $values[] = $data['lesson_date'];
            }

            if (isset($data['start_time'])) {
                $fields[] = "start_time = ?";
                $values[] = $data['start_time'];
            }

            if (isset($data['end_time'])) {
                $fields[] = "end_time = ?";
                $values[] = $data['end_time'];
            }

            if (isset($data['classroom'])) {
                $fields[] = "classroom = ?";
                $values[] = $data['classroom'];
            }

            if (isset($data['private_notes'])) {
                $fields[] = "private_notes = ?";
                $values[] = $data['private_notes'];
            }

            if (isset($data['objectives'])) {
                $fields[] = "objectives = ?";
                $values[] = $data['objectives'];
            }

            if (isset($data['status'])) {
                $fields[] = "status = ?";
                $values[] = $data['status'];
            }

            if (empty($fields)) {
                throw new Exception("No fields to update");
            }

            $values[] = $id;

            $sql = "UPDATE lessons SET " . implode(', ', $fields) . " WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($values);

            // If updating recurring lessons
            if ($updateRecurring) {
                $lesson = $this->getById($id);
                if ($lesson['parent_lesson_id']) {
                    // This is a child lesson, get all siblings
                    $this->updateRecurringSiblings($lesson['parent_lesson_id'], $data, $id);
                } elseif ($lesson['is_recurring']) {
                    // This is the parent, update all children
                    $this->updateRecurringSiblings($id, $data);
                }
            }

            return $result;
        } catch (PDOException $e) {
            throw new Exception("Error updating lesson: " . $e->getMessage());
        }
    }

    /**
     * Update recurring sibling lessons
     */
    private function updateRecurringSiblings($parentLessonId, $data, $exceptId = null) {
        try {
            $fields = [];
            $values = [];

            // Only update certain fields for recurring lessons
            if (isset($data['start_time'])) {
                $fields[] = "start_time = ?";
                $values[] = $data['start_time'];
            }

            if (isset($data['end_time'])) {
                $fields[] = "end_time = ?";
                $values[] = $data['end_time'];
            }

            if (isset($data['classroom'])) {
                $fields[] = "classroom = ?";
                $values[] = $data['classroom'];
            }

            if (isset($data['objectives'])) {
                $fields[] = "objectives = ?";
                $values[] = $data['objectives'];
            }

            if (empty($fields)) {
                return;
            }

            $values[] = $parentLessonId;

            $sql = "UPDATE lessons SET " . implode(', ', $fields) . " WHERE parent_lesson_id = ?";

            if ($exceptId) {
                $sql .= " AND id != ?";
                $values[] = $exceptId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Error updating recurring siblings: " . $e->getMessage());
        }
    }

    /**
     * Delete lesson (soft delete)
     */
    public function delete($id, $deleteRecurring = false, $skipNotification = false) {
        try {
            $lesson = $this->getById($id);

            $stmt = $this->db->prepare("UPDATE lessons SET status = 'cancelled' WHERE id = ?");
            $result = $stmt->execute([$id]);

            // If deleting recurring lessons
            if ($deleteRecurring) {
                if ($lesson['parent_lesson_id']) {
                    // Delete all siblings
                    $this->deleteRecurringSiblings($lesson['parent_lesson_id'], $id);
                } elseif ($lesson['is_recurring']) {
                    // Delete all children
                    $this->deleteRecurringSiblings($id);
                }
            }

            return $result;
        } catch (PDOException $e) {
            throw new Exception("Error deleting lesson: " . $e->getMessage());
        }
    }

    /**
     * Delete recurring sibling lessons
     */
    private function deleteRecurringSiblings($parentLessonId, $exceptId = null) {
        try {
            $sql = "UPDATE lessons SET status = 'cancelled' WHERE parent_lesson_id = ?";
            $values = [$parentLessonId];

            if ($exceptId) {
                $sql .= " AND id != ?";
                $values[] = $exceptId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("Error deleting recurring siblings: " . $e->getMessage());
        }
    }

    /**
     * Get lessons by filters
     */
    public function getByFilters($filters = []) {
        try {
            $sql = "
                SELECT l.*,
                       c.course_name,
                       s.first_name as student_first_name, s.last_name as student_last_name,
                       t.first_name as teacher_first_name, t.last_name as teacher_last_name
                FROM lessons l
                INNER JOIN courses c ON l.course_id = c.id
                INNER JOIN users s ON l.student_id = s.id
                INNER JOIN users t ON l.teacher_id = t.id
                WHERE 1=1
            ";

            $params = [];

            if (isset($filters['teacher_id'])) {
                $sql .= " AND l.teacher_id = ?";
                $params[] = $filters['teacher_id'];
            }

            if (isset($filters['student_id'])) {
                $sql .= " AND l.student_id = ?";
                $params[] = $filters['student_id'];
            }

            if (isset($filters['course_id'])) {
                $sql .= " AND l.course_id = ?";
                $params[] = $filters['course_id'];
            }

            if (isset($filters['date_from'])) {
                $sql .= " AND l.lesson_date >= ?";
                $params[] = $filters['date_from'];
            }

            if (isset($filters['date_to'])) {
                $sql .= " AND l.lesson_date <= ?";
                $params[] = $filters['date_to'];
            }

            if (isset($filters['status'])) {
                $sql .= " AND l.status = ?";
                $params[] = $filters['status'];
            } else {
                // By default, exclude cancelled lessons
                $sql .= " AND l.status != 'cancelled'";
            }

            $sql .= " ORDER BY l.lesson_date ASC, l.start_time ASC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching lessons: " . $e->getMessage());
        }
    }

    /**
     * Get next lesson for student
     */
    public function getNextLesson($studentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT l.*,
                       c.course_name,
                       t.first_name as teacher_first_name, t.last_name as teacher_last_name
                FROM lessons l
                INNER JOIN courses c ON l.course_id = c.id
                INNER JOIN users t ON l.teacher_id = t.id
                WHERE l.student_id = ?
                AND l.lesson_date >= DATE('now')
                AND l.status = 'scheduled'
                ORDER BY l.lesson_date ASC, l.start_time ASC
                LIMIT 1
            ");
            $stmt->execute([$studentId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Error fetching next lesson: " . $e->getMessage());
        }
    }

    /**
     * Get lesson history for student
     */
    public function getHistory($studentId, $limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT l.*,
                       c.course_name,
                       t.first_name as teacher_first_name, t.last_name as teacher_last_name
                FROM lessons l
                INNER JOIN courses c ON l.course_id = c.id
                INNER JOIN users t ON l.teacher_id = t.id
                WHERE l.student_id = ?
                ORDER BY l.lesson_date DESC, l.start_time DESC
                LIMIT ?
            ");
            $stmt->execute([$studentId, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching lesson history: " . $e->getMessage());
        }
    }

    /**
     * Get upcoming lessons (for reminders)
     */
    public function getUpcomingLessons($hoursAhead = 1) {
        try {
            $stmt = $this->db->prepare("
                SELECT l.*
                FROM lessons l
                WHERE l.lesson_date = DATE('now')
                AND l.start_time BETWEEN TIME('now') AND TIME('now', '+' || ? || ' hours')
                AND l.status = 'scheduled'
            ");
            $stmt->execute([$hoursAhead]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Error fetching upcoming lessons: " . $e->getMessage());
        }
    }

    /**
     * Mark lesson as completed
     */
    public function markCompleted($id) {
        try {
            $stmt = $this->db->prepare("UPDATE lessons SET status = 'completed' WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            throw new Exception("Error marking lesson as completed: " . $e->getMessage());
        }
    }

    /**
     * Check for schedule conflicts
     */
    public function hasConflict($teacherId, $lessonDate, $startTime, $endTime, $excludeLessonId = null) {
        try {
            $sql = "
                SELECT COUNT(*) as count
                FROM lessons
                WHERE teacher_id = ?
                AND lesson_date = ?
                AND status = 'scheduled'
                AND (
                    (start_time < ? AND end_time > ?)
                    OR (start_time >= ? AND start_time < ?)
                    OR (end_time > ? AND end_time <= ?)
                )
            ";

            $params = [
                $teacherId,
                $lessonDate,
                $endTime, $startTime,
                $startTime, $endTime,
                $startTime, $endTime
            ];

            if ($excludeLessonId) {
                $sql .= " AND id != ?";
                $params[] = $excludeLessonId;
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();

            return $result['count'] > 0;
        } catch (PDOException $e) {
            throw new Exception("Error checking conflict: " . $e->getMessage());
        }
    }
}
