-- =====================================================================
-- LOCAL POSTGRESQL SCHEMA FOR ANNOUNCEMENT BOARD
-- Run this on your local PostgreSQL after creating the database.
-- =====================================================================
-- Quick start:
--   1. Open psql (or pgAdmin Query Tool)
--   2. CREATE DATABASE announcement;
--   3. \c announcement
--   4. Paste this entire file and execute
-- =====================================================================

-- ===== USER TABLE =====
CREATE TABLE IF NOT EXISTS "user" (
    id            SERIAL PRIMARY KEY,
    username      VARCHAR(50)  UNIQUE NOT NULL,
    email         VARCHAR(120) UNIQUE NOT NULL,
    password      VARCHAR(255) NOT NULL,
    access_level  VARCHAR(20)  NOT NULL DEFAULT 'user',
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ===== ANNOUNCEMENT IMAGE (single row) =====
CREATE TABLE IF NOT EXISTS image (
    id           SERIAL PRIMARY KEY,
    file         VARCHAR(255) NOT NULL,
    uploaded_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ===== MODULE FOLDERS =====
CREATE TABLE IF NOT EXISTS module_folders (
    id          SERIAL PRIMARY KEY,
    name        VARCHAR(150) NOT NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ===== MODULE FILES (PDFs) =====
CREATE TABLE IF NOT EXISTS files (
    id           SERIAL PRIMARY KEY,
    folder_id    INTEGER REFERENCES module_folders(id) ON DELETE CASCADE,
    title        VARCHAR(255) NOT NULL,
    file_name    VARCHAR(255) NOT NULL,
    description  TEXT,
    uploaded_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ===== TODO LIST =====
CREATE TABLE IF NOT EXISTS todo_list (
    id              SERIAL PRIMARY KEY,
    task            VARCHAR(255) NOT NULL,
    description     TEXT,
    deadline        DATE NOT NULL,
    deadline_time   TIME DEFAULT '23:59:59',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===== CALENDAR EVENTS =====
CREATE TABLE IF NOT EXISTS calendar_events (
    id           SERIAL PRIMARY KEY,
    title        VARCHAR(255) NOT NULL,
    description  TEXT,
    event_date   DATE NOT NULL,
    event_time   TIME,
    color        VARCHAR(20) DEFAULT '#3b82f6',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===== INDEXES =====
CREATE INDEX IF NOT EXISTS idx_files_folder       ON files(folder_id);
CREATE INDEX IF NOT EXISTS idx_calendar_date      ON calendar_events(event_date);
CREATE INDEX IF NOT EXISTS idx_todo_deadline      ON todo_list(deadline);

-- ===== SEED ADMIN USER =====
-- Username: admin
-- Password: admin123  (change after first login!)
-- Hash generated with: password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO "user" (username, email, password, access_level)
VALUES (
    'admin',
    'admin@local.test',
    '$2y$10$BTwy0DbitVjLng9H5GISnewmHHnpDPeHL9sb9oa0WxE6BJRPHNrhW',
    'admin'
)
ON CONFLICT (username) DO NOTHING;
