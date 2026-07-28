-- ═══════════════════════════════════════════════════════════════
--  MSU Scoring — Database Schema (MySQL 8.0+)
--  charset: utf8mb4 (รองรับภาษาไทย + emoji)
-- ═══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS users (
    user_id       INT           NOT NULL AUTO_INCREMENT,
    username      VARCHAR(100)  NOT NULL,
    password      VARCHAR(255)  NOT NULL,
    name          VARCHAR(255)  NOT NULL,
    role          VARCHAR(20)   NOT NULL DEFAULT 'user',
    email         VARCHAR(255)  DEFAULT NULL,
    google_id     VARCHAR(255)  DEFAULT NULL,
    auth_provider VARCHAR(20)   DEFAULT 'local',
    PRIMARY KEY (user_id),
    UNIQUE KEY uq_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exams (
    exam_id        INT           NOT NULL AUTO_INCREMENT,
    owner_id       INT           NOT NULL,
    exam_title     VARCHAR(255)  NOT NULL,
    exam_code      VARCHAR(100)  DEFAULT NULL,
    question_count INT           NOT NULL,
    answer_key     LONGTEXT      DEFAULT NULL COMMENT 'Stored as JSON string',
    created_at     DATETIME      DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (exam_id),
    CONSTRAINT fk_exams_owner FOREIGN KEY (owner_id) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS exam_shares (
    share_id          INT  NOT NULL AUTO_INCREMENT,
    exam_id           INT  NOT NULL,
    shared_to_user_id INT  NOT NULL,
    created_at        DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (share_id),
    CONSTRAINT fk_shares_exam FOREIGN KEY (exam_id)           REFERENCES exams (exam_id),
    CONSTRAINT fk_shares_user FOREIGN KEY (shared_to_user_id) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS students (
    student_id VARCHAR(50)  NOT NULL,
    name       VARCHAR(255) NOT NULL,
    PRIMARY KEY (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_scores (
    score_id   INT          NOT NULL AUTO_INCREMENT,
    exam_id    INT          NOT NULL,
    student_id VARCHAR(50)  NOT NULL,
    exam_set   VARCHAR(10)  DEFAULT 'A',
    score      INT          NOT NULL,
    image_path VARCHAR(500) DEFAULT NULL,
    raw_answers LONGTEXT    DEFAULT NULL,
    scanned_by INT          NOT NULL,
    scanned_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (score_id),
    UNIQUE KEY uq_exam_student (exam_id, student_id),
    CONSTRAINT fk_scores_exam    FOREIGN KEY (exam_id)    REFERENCES exams  (exam_id),
    CONSTRAINT fk_scores_scanner FOREIGN KEY (scanned_by) REFERENCES users  (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_logs (
    id         INT      NOT NULL AUTO_INCREMENT,
    user_id    INT      NOT NULL,
    action     VARCHAR(100) NOT NULL,
    exam_id    INT      DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Default admin account (password: password123) ──────────────
-- bcrypt hash สำหรับ 'password123'
INSERT IGNORE INTO users (user_id, username, password, name, role)
VALUES (1, 'teacher_demo', '$2y$10$Q6wnsSGblDiH.ZPcnnF/n.m629hBzCn4zfdgQppYRZyM0FCBY4l1S', 'อาจารย์ สมชาย', 'admin');
