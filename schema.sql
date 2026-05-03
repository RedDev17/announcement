-- Supabase PostgreSQL schema for Announcement Board
-- Run this in Supabase SQL Editor (Dashboard > SQL Editor > New Query)

-- Users table (quoted because "user" is a reserved keyword)
CREATE TABLE IF NOT EXISTS "user" (
    id SERIAL PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    access_level VARCHAR(50) NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT NOW()
);

-- Announcement image (only one row at a time)
CREATE TABLE IF NOT EXISTS image (
    id SERIAL PRIMARY KEY,
    file VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT NOW()
);

-- Module folders
CREATE TABLE IF NOT EXISTS module_folders (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Files (PDFs inside folders)
CREATE TABLE IF NOT EXISTS files (
    id SERIAL PRIMARY KEY,
    folder_id INTEGER REFERENCES module_folders(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    description TEXT,
    uploaded_at TIMESTAMP DEFAULT NOW()
);

-- Todo list
CREATE TABLE IF NOT EXISTS todo_list (
    id SERIAL PRIMARY KEY,
    task VARCHAR(255) NOT NULL,
    description TEXT,
    deadline DATE NOT NULL,
    deadline_time TIME DEFAULT '23:59:59',
    created_at TIMESTAMP DEFAULT NOW()
);

-- Calendar events
CREATE TABLE IF NOT EXISTS calendar_events (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME,
    color VARCHAR(20) DEFAULT '#3b82f6',
    created_at TIMESTAMP DEFAULT NOW()
);
