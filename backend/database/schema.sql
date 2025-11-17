-- ============================================
-- MUSIC SCHOOL SCHEDULER DATABASE SCHEMA
-- SQLite Database
-- ============================================

-- Table: users
-- Base table for all user types (admin, teacher, student)
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    user_type VARCHAR(20) NOT NULL CHECK(user_type IN ('admin', 'teacher', 'student')),
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    birth_date DATE NOT NULL,
    profile_photo VARCHAR(255),
    unique_id VARCHAR(50) NOT NULL UNIQUE,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_unique_id ON users(unique_id);
CREATE INDEX idx_users_type ON users(user_type);

-- Table: schools
-- Schools managed by administrators
CREATE TABLE IF NOT EXISTS schools (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    admin_id INTEGER NOT NULL,
    school_name VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    unique_id VARCHAR(50) NOT NULL UNIQUE,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_schools_admin ON schools(admin_id);
CREATE INDEX idx_schools_unique_id ON schools(unique_id);
CREATE INDEX idx_schools_name ON schools(school_name);
CREATE INDEX idx_schools_city ON schools(city);

-- Table: courses
-- Courses created by teachers
CREATE TABLE IF NOT EXISTS courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    teacher_id INTEGER NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    description TEXT,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_courses_teacher ON courses(teacher_id);
CREATE INDEX idx_courses_name ON courses(course_name);

-- Table: teacher_schools
-- Many-to-many relationship between teachers and schools
CREATE TABLE IF NOT EXISTS teacher_schools (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    teacher_id INTEGER NOT NULL,
    school_id INTEGER NOT NULL,
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    UNIQUE(teacher_id, school_id)
);

CREATE INDEX idx_teacher_schools_teacher ON teacher_schools(teacher_id);
CREATE INDEX idx_teacher_schools_school ON teacher_schools(school_id);

-- Table: teacher_school_requests
-- Requests from teachers to join schools
CREATE TABLE IF NOT EXISTS teacher_school_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    teacher_id INTEGER NOT NULL,
    school_id INTEGER NOT NULL,
    status VARCHAR(20) DEFAULT 'pending' CHECK(status IN ('pending', 'approved', 'rejected')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
);

CREATE INDEX idx_teacher_school_requests_teacher ON teacher_school_requests(teacher_id);
CREATE INDEX idx_teacher_school_requests_school ON teacher_school_requests(school_id);
CREATE INDEX idx_teacher_school_requests_status ON teacher_school_requests(status);

-- Table: course_enrollments
-- Approved enrollments of students in courses
CREATE TABLE IF NOT EXISTS course_enrollments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,
    enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE(student_id, course_id)
);

CREATE INDEX idx_enrollments_student ON course_enrollments(student_id);
CREATE INDEX idx_enrollments_course ON course_enrollments(course_id);

-- Table: enrollment_requests
-- Requests from students to enroll in courses
CREATE TABLE IF NOT EXISTS enrollment_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,
    teacher_id INTEGER NOT NULL,
    status VARCHAR(20) DEFAULT 'pending' CHECK(status IN ('pending', 'approved', 'rejected')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_enrollment_requests_student ON enrollment_requests(student_id);
CREATE INDEX idx_enrollment_requests_course ON enrollment_requests(course_id);
CREATE INDEX idx_enrollment_requests_teacher ON enrollment_requests(teacher_id);
CREATE INDEX idx_enrollment_requests_status ON enrollment_requests(status);

-- Table: lessons
-- Lessons scheduled by teachers for students
CREATE TABLE IF NOT EXISTS lessons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    teacher_id INTEGER NOT NULL,
    lesson_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    classroom VARCHAR(100),
    private_notes TEXT,
    objectives TEXT,
    is_recurring INTEGER DEFAULT 0,
    recurrence_pattern VARCHAR(20) CHECK(recurrence_pattern IN ('weekly', 'monthly', NULL)),
    parent_lesson_id INTEGER,
    status VARCHAR(20) DEFAULT 'scheduled' CHECK(status IN ('scheduled', 'completed', 'cancelled')),
    skip_notification INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_lesson_id) REFERENCES lessons(id) ON DELETE SET NULL
);

CREATE INDEX idx_lessons_course ON lessons(course_id);
CREATE INDEX idx_lessons_student ON lessons(student_id);
CREATE INDEX idx_lessons_teacher ON lessons(teacher_id);
CREATE INDEX idx_lessons_date ON lessons(lesson_date);
CREATE INDEX idx_lessons_status ON lessons(status);
CREATE INDEX idx_lessons_parent ON lessons(parent_lesson_id);

-- Table: student_preferences
-- Student notification preferences
CREATE TABLE IF NOT EXISTS student_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL UNIQUE,
    notify_free_slots INTEGER DEFAULT 0,
    notify_before_lesson INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_student_preferences_student ON student_preferences(student_id);

-- Table: notifications
-- Centralized notification system
CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type VARCHAR(50) NOT NULL CHECK(type IN ('enrollment_request', 'enrollment_approved', 'enrollment_rejected', 'lesson_created', 'lesson_modified', 'lesson_cancelled', 'free_slot', 'lesson_reminder', 'teacher_request', 'teacher_approved', 'teacher_rejected', 'student_data_change')),
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read INTEGER DEFAULT 0,
    related_id INTEGER,
    related_type VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);
CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_notifications_created ON notifications(created_at);

-- Table: lesson_history
-- Track changes to lessons for auditing
CREATE TABLE IF NOT EXISTS lesson_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lesson_id INTEGER NOT NULL,
    action VARCHAR(50) NOT NULL,
    changed_by INTEGER NOT NULL,
    old_values TEXT,
    new_values TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_lesson_history_lesson ON lesson_history(lesson_id);
CREATE INDEX idx_lesson_history_user ON lesson_history(changed_by);

-- Table: available_instruments
-- Predefined list of musical instruments for courses
CREATE TABLE IF NOT EXISTS available_instruments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    instrument_name VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Insert default instruments
INSERT INTO available_instruments (instrument_name) VALUES
    ('Pianoforte'),
    ('Chitarra'),
    ('Chitarra Elettrica'),
    ('Basso'),
    ('Batteria'),
    ('Canto'),
    ('Violino'),
    ('Violoncello'),
    ('Flauto'),
    ('Sassofono'),
    ('Tromba'),
    ('Clarinetto'),
    ('Tastiera'),
    ('Ukulele'),
    ('Teoria Musicale'),
    ('Armonia'),
    ('Composizione');

-- ============================================
-- TRIGGERS
-- ============================================

-- Trigger: Update timestamp on users update
CREATE TRIGGER IF NOT EXISTS update_users_timestamp
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger: Update timestamp on schools update
CREATE TRIGGER IF NOT EXISTS update_schools_timestamp
AFTER UPDATE ON schools
FOR EACH ROW
BEGIN
    UPDATE schools SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger: Update timestamp on courses update
CREATE TRIGGER IF NOT EXISTS update_courses_timestamp
AFTER UPDATE ON courses
FOR EACH ROW
BEGIN
    UPDATE courses SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger: Update timestamp on lessons update
CREATE TRIGGER IF NOT EXISTS update_lessons_timestamp
AFTER UPDATE ON lessons
FOR EACH ROW
BEGIN
    UPDATE lessons SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger: Update timestamp on enrollment_requests update
CREATE TRIGGER IF NOT EXISTS update_enrollment_requests_timestamp
AFTER UPDATE ON enrollment_requests
FOR EACH ROW
BEGIN
    UPDATE enrollment_requests SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger: Update timestamp on teacher_school_requests update
CREATE TRIGGER IF NOT EXISTS update_teacher_school_requests_timestamp
AFTER UPDATE ON teacher_school_requests
FOR EACH ROW
BEGIN
    UPDATE teacher_school_requests SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;

-- Trigger: Update timestamp on student_preferences update
CREATE TRIGGER IF NOT EXISTS update_student_preferences_timestamp
AFTER UPDATE ON student_preferences
FOR EACH ROW
BEGIN
    UPDATE student_preferences SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
END;
