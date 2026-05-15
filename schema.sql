CREATE TABLE IF NOT EXISTS users (
    user_id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    name TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS exams (
    exam_id INTEGER PRIMARY KEY AUTOINCREMENT,
    owner_id INTEGER NOT NULL,
    exam_title TEXT NOT NULL,
    exam_code TEXT,
    question_count INTEGER NOT NULL,
    answer_key TEXT, -- Stored as JSON string
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS exam_shares (
    share_id INTEGER PRIMARY KEY AUTOINCREMENT,
    exam_id INTEGER NOT NULL,
    shared_to_user_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id),
    FOREIGN KEY (shared_to_user_id) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS students (
    student_id TEXT PRIMARY KEY,
    name TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS student_scores (
    score_id INTEGER PRIMARY KEY AUTOINCREMENT,
    exam_id INTEGER NOT NULL,
    student_id TEXT NOT NULL,
    exam_set TEXT DEFAULT 'A',
    score INTEGER NOT NULL,
    image_path TEXT,
    raw_answers TEXT,
    scanned_by INTEGER NOT NULL,
    scanned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(exam_id),
    FOREIGN KEY (scanned_by) REFERENCES users(user_id),
    UNIQUE(exam_id, student_id) -- Prevents duplicate submission for the same exam
);

-- Insert a default user for testing (password: password123)
-- bcrypt hash for 'password123'
INSERT OR IGNORE INTO users (user_id, username, password, name) 
VALUES (1, 'teacher_demo', '$2y$10$Q6wnsSGblDiH.ZPcnnF/n.m629hBzCn4zfdgQppYRZyM0FCBY4l1S', 'อาจารย์ สมชาย');
