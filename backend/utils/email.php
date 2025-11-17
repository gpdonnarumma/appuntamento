<?php
/**
 * Email and Notification Management
 */

require_once __DIR__ . '/../config/database.php';

class EmailService {

    /**
     * Send email (placeholder - integrate with actual email service)
     */
    public static function sendEmail($to, $subject, $body) {
        // In production, integrate with services like:
        // - PHPMailer
        // - SendGrid
        // - Mailgun
        // - AWS SES

        // For now, log the email
        error_log("EMAIL TO: $to");
        error_log("SUBJECT: $subject");
        error_log("BODY: $body");

        // Return true to simulate successful send
        return true;
    }

    /**
     * Create notification in database
     */
    public static function createNotification($userId, $type, $title, $message, $relatedId = null, $relatedType = null) {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, type, title, message, related_id, related_type)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            return $stmt->execute([$userId, $type, $title, $message, $relatedId, $relatedType]);
        } catch (PDOException $e) {
            error_log("Notification creation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send enrollment request notification to teacher
     */
    public static function notifyEnrollmentRequest($teacherId, $studentName, $courseName, $requestId) {
        $title = "Nuova richiesta di iscrizione";
        $message = "$studentName ha richiesto l'iscrizione al corso $courseName";

        self::createNotification($teacherId, 'enrollment_request', $title, $message, $requestId, 'enrollment_request');

        // Send email
        $teacher = self::getUserById($teacherId);
        if ($teacher) {
            $subject = "Nuova richiesta di iscrizione - $courseName";
            $body = "Ciao {$teacher['first_name']},\n\n";
            $body .= "$studentName ha inviato una richiesta di iscrizione al tuo corso '$courseName'.\n\n";
            $body .= "Accedi alla piattaforma per approvare o rifiutare la richiesta.\n\n";
            $body .= "Cordiali saluti,\nMusic School Scheduler";

            self::sendEmail($teacher['email'], $subject, $body);
        }
    }

    /**
     * Send enrollment approval notification to student
     */
    public static function notifyEnrollmentApproved($studentId, $teacherName, $courseName) {
        $title = "Iscrizione approvata";
        $message = "La tua richiesta di iscrizione al corso $courseName è stata approvata da $teacherName";

        self::createNotification($studentId, 'enrollment_approved', $title, $message);

        // Send email
        $student = self::getUserById($studentId);
        if ($student) {
            $subject = "Iscrizione approvata - $courseName";
            $body = "Ciao {$student['first_name']},\n\n";
            $body .= "La tua richiesta di iscrizione al corso '$courseName' è stata approvata da $teacherName!\n\n";
            $body .= "Ora puoi visualizzare le tue lezioni nel calendario.\n\n";
            $body .= "Cordiali saluti,\nMusic School Scheduler";

            self::sendEmail($student['email'], $subject, $body);
        }
    }

    /**
     * Send enrollment rejection notification to student
     */
    public static function notifyEnrollmentRejected($studentId, $teacherName, $courseName) {
        $title = "Iscrizione rifiutata";
        $message = "La tua richiesta di iscrizione al corso $courseName è stata rifiutata";

        self::createNotification($studentId, 'enrollment_rejected', $title, $message);

        // Send email
        $student = self::getUserById($studentId);
        if ($student) {
            $subject = "Iscrizione non approvata - $courseName";
            $body = "Ciao {$student['first_name']},\n\n";
            $body .= "La tua richiesta di iscrizione al corso '$courseName' non è stata approvata.\n\n";
            $body .= "Per maggiori informazioni, contatta direttamente il docente.\n\n";
            $body .= "Cordiali saluti,\nMusic School Scheduler";

            self::sendEmail($student['email'], $subject, $body);
        }
    }

    /**
     * Send lesson created notification to student
     */
    public static function notifyLessonCreated($studentId, $lessonDetails) {
        $title = "Nuova lezione programmata";
        $message = "È stata programmata una nuova lezione per il {$lessonDetails['lesson_date']} alle {$lessonDetails['start_time']}";

        self::createNotification($studentId, 'lesson_created', $title, $message, $lessonDetails['lesson_id'], 'lesson');

        // Send email
        $student = self::getUserById($studentId);
        if ($student) {
            $subject = "Nuova lezione programmata";
            $body = "Ciao {$student['first_name']},\n\n";
            $body .= "È stata programmata una nuova lezione:\n\n";
            $body .= "Corso: {$lessonDetails['course_name']}\n";
            $body .= "Data: " . formatDate($lessonDetails['lesson_date']) . "\n";
            $body .= "Orario: {$lessonDetails['start_time']} - {$lessonDetails['end_time']}\n";

            if (!empty($lessonDetails['classroom'])) {
                $body .= "Aula: {$lessonDetails['classroom']}\n";
            }

            if (!empty($lessonDetails['objectives'])) {
                $body .= "\nObiettivi: {$lessonDetails['objectives']}\n";
            }

            $body .= "\nCordiali saluti,\nMusic School Scheduler";

            self::sendEmail($student['email'], $subject, $body);
        }
    }

    /**
     * Send lesson modified notification to student
     */
    public static function notifyLessonModified($studentId, $lessonDetails) {
        $title = "Lezione modificata";
        $message = "La lezione del {$lessonDetails['lesson_date']} è stata modificata";

        self::createNotification($studentId, 'lesson_modified', $title, $message, $lessonDetails['lesson_id'], 'lesson');

        // Send email
        $student = self::getUserById($studentId);
        if ($student) {
            $subject = "Modifica lezione";
            $body = "Ciao {$student['first_name']},\n\n";
            $body .= "Una tua lezione è stata modificata:\n\n";
            $body .= "Corso: {$lessonDetails['course_name']}\n";
            $body .= "Nuova data: " . formatDate($lessonDetails['lesson_date']) . "\n";
            $body .= "Nuovo orario: {$lessonDetails['start_time']} - {$lessonDetails['end_time']}\n";

            if (!empty($lessonDetails['classroom'])) {
                $body .= "Aula: {$lessonDetails['classroom']}\n";
            }

            $body .= "\nCordiali saluti,\nMusic School Scheduler";

            self::sendEmail($student['email'], $subject, $body);
        }
    }

    /**
     * Send lesson cancelled notification to student
     */
    public static function notifyLessonCancelled($studentId, $lessonDetails, $skipNotification = false) {
        $title = "Lezione annullata";
        $message = "La lezione del {$lessonDetails['lesson_date']} alle {$lessonDetails['start_time']} è stata annullata";

        self::createNotification($studentId, 'lesson_cancelled', $title, $message, $lessonDetails['lesson_id'], 'lesson');

        // Send email
        $student = self::getUserById($studentId);
        if ($student) {
            $subject = "Lezione annullata";
            $body = "Ciao {$student['first_name']},\n\n";
            $body .= "La seguente lezione è stata annullata:\n\n";
            $body .= "Corso: {$lessonDetails['course_name']}\n";
            $body .= "Data: " . formatDate($lessonDetails['lesson_date']) . "\n";
            $body .= "Orario: {$lessonDetails['start_time']} - {$lessonDetails['end_time']}\n";
            $body .= "\nCordiali saluti,\nMusic School Scheduler";

            self::sendEmail($student['email'], $subject, $body);
        }
    }

    /**
     * Notify students about free slot
     */
    public static function notifyFreeSlot($courseId, $teacherId, $lessonDate, $startTime, $endTime) {
        try {
            $db = getDB();

            // Get students enrolled in course with notify_free_slots enabled
            $stmt = $db->prepare("
                SELECT DISTINCT u.id, u.email, u.first_name
                FROM users u
                INNER JOIN course_enrollments ce ON u.id = ce.student_id
                INNER JOIN student_preferences sp ON u.id = sp.student_id
                WHERE ce.course_id = ?
                AND sp.notify_free_slots = 1
            ");
            $stmt->execute([$courseId]);
            $students = $stmt->fetchAll();

            $course = self::getCourseById($courseId);

            foreach ($students as $student) {
                $title = "Slot disponibile";
                $message = "Si è liberato uno slot per il corso {$course['course_name']} il " . formatDate($lessonDate) . " alle $startTime";

                self::createNotification($student['id'], 'free_slot', $title, $message);

                // Send email
                $subject = "Slot disponibile - {$course['course_name']}";
                $body = "Ciao {$student['first_name']},\n\n";
                $body .= "Si è liberato uno slot per il corso '{$course['course_name']}':\n\n";
                $body .= "Data: " . formatDate($lessonDate) . "\n";
                $body .= "Orario: $startTime - $endTime\n";
                $body .= "\nContatta il docente se sei interessato!\n\n";
                $body .= "Cordiali saluti,\nMusic School Scheduler";

                self::sendEmail($student['email'], $subject, $body);
            }
        } catch (PDOException $e) {
            error_log("Free slot notification error: " . $e->getMessage());
        }
    }

    /**
     * Send lesson reminder (1 hour before)
     */
    public static function sendLessonReminder($lessonId) {
        try {
            $db = getDB();

            // Get lesson details
            $stmt = $db->prepare("
                SELECT l.*, c.course_name, u.id as student_id, u.email, u.first_name
                FROM lessons l
                INNER JOIN courses c ON l.course_id = c.id
                INNER JOIN users u ON l.student_id = u.id
                INNER JOIN student_preferences sp ON u.id = sp.student_id
                WHERE l.id = ?
                AND sp.notify_before_lesson = 1
            ");
            $stmt->execute([$lessonId]);
            $lesson = $stmt->fetch();

            if ($lesson) {
                $title = "Promemoria lezione";
                $message = "Tra un'ora hai lezione di {$lesson['course_name']}";

                self::createNotification($lesson['student_id'], 'lesson_reminder', $title, $message, $lessonId, 'lesson');

                // Send email
                $subject = "Promemoria - Lezione tra 1 ora";
                $body = "Ciao {$lesson['first_name']},\n\n";
                $body .= "Ti ricordiamo che tra un'ora hai lezione:\n\n";
                $body .= "Corso: {$lesson['course_name']}\n";
                $body .= "Orario: {$lesson['start_time']} - {$lesson['end_time']}\n";

                if (!empty($lesson['classroom'])) {
                    $body .= "Aula: {$lesson['classroom']}\n";
                }

                if (!empty($lesson['objectives'])) {
                    $body .= "\nObiettivi: {$lesson['objectives']}\n";
                }

                $body .= "\nA presto!\nMusic School Scheduler";

                self::sendEmail($lesson['email'], $subject, $body);
            }
        } catch (PDOException $e) {
            error_log("Lesson reminder error: " . $e->getMessage());
        }
    }

    /**
     * Notify teacher school request to admin
     */
    public static function notifyTeacherSchoolRequest($adminId, $teacherName, $teacherEmail, $schoolName, $requestId) {
        $title = "Nuova richiesta docente";
        $message = "$teacherName ha richiesto di essere aggiunto alla scuola $schoolName";

        self::createNotification($adminId, 'teacher_request', $title, $message, $requestId, 'teacher_school_request');

        // Send email
        $admin = self::getUserById($adminId);
        if ($admin) {
            $subject = "Nuova richiesta docente - $schoolName";
            $body = "Ciao {$admin['first_name']},\n\n";
            $body .= "$teacherName ($teacherEmail) ha richiesto di essere aggiunto alla lista docenti di '$schoolName'.\n\n";
            $body .= "Accedi alla piattaforma per approvare o rifiutare la richiesta.\n\n";
            $body .= "Cordiali saluti,\nMusic School Scheduler";

            self::sendEmail($admin['email'], $subject, $body);
        }
    }

    /**
     * Notify teacher of school approval
     */
    public static function notifyTeacherSchoolApproved($teacherId, $schoolName) {
        $title = "Richiesta approvata";
        $message = "Sei stato aggiunto alla scuola $schoolName";

        self::createNotification($teacherId, 'teacher_approved', $title, $message);

        // Send email
        $teacher = self::getUserById($teacherId);
        if ($teacher) {
            $subject = "Richiesta approvata - $schoolName";
            $body = "Ciao {$teacher['first_name']},\n\n";
            $body .= "La tua richiesta di essere aggiunto a '$schoolName' è stata approvata!\n\n";
            $body .= "Cordiali saluti,\nMusic School Scheduler";

            self::sendEmail($teacher['email'], $subject, $body);
        }
    }

    /**
     * Get user by ID helper
     */
    private static function getUserById($userId) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get course by ID helper
     */
    private static function getCourseById($courseId) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM courses WHERE id = ?");
            $stmt->execute([$courseId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
}
