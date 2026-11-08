-- ── Hala PWA — MySQL Database Schema ────────────────────────
-- Run this once in your Hostinger MySQL to create all tables.
-- Hostinger control panel → MySQL Databases → phpMyAdmin → SQL tab
-- ─────────────────────────────────────────────────────────────

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Categories
CREATE TABLE IF NOT EXISTS categories (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    name      VARCHAR(100) NOT NULL UNIQUE,
    icon      VARCHAR(50),
    colour    VARCHAR(20),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO categories (name, icon, colour) VALUES
  ('Masjid',     'mosque',     '#2266cc'),
  ('Café',        'coffee',     '#c9a84c'),
  ('Restaurant',  'fork-knife', '#e05a20'),
  ('Culture',     'landmark',   '#8020c0'),
  ('Shopping',    'bag',        '#20c080'),
  ('Dessert',     'cake',       '#c02060'),
  ('Experience',  'star',       '#e0b020');

-- Places
CREATE TABLE IF NOT EXISTS places (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(255) NOT NULL,
    description  TEXT,
    category     VARCHAR(100),
    address      VARCHAR(500),
    lat          DECIMAL(10,7),
    lng          DECIMAL(10,7),
    directions_url VARCHAR(1000),
    image_url    VARCHAR(1000),
    offer        VARCHAR(500),
    offer_expiry DATE,
    is_featured  TINYINT(1) DEFAULT 0,
    is_active    TINYINT(1) DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_featured (is_featured),
    INDEX idx_active   (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Users (Firebase UID as primary key)
CREATE TABLE IF NOT EXISTS users (
    firebase_uid VARCHAR(128) PRIMARY KEY,
    name         VARCHAR(255),
    email        VARCHAR(255),
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Favourites
CREATE TABLE IF NOT EXISTS favourites (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    firebase_uid VARCHAR(128) NOT NULL,
    place_id     INT NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_fav (firebase_uid, place_id),
    FOREIGN KEY (place_id) REFERENCES places(id) ON DELETE CASCADE,
    INDEX idx_user (firebase_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
