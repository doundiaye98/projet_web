-- Projet web - Club de lecture (V1)
-- MySQL 8+ / MariaDB 10.4+
-- Charset/collation recommandés: utf8mb4 / utf8mb4_unicode_ci

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE DATABASE IF NOT EXISTS projet_web
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE projet_web;

-- ------------------------------------------------------------
-- Users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','moderateur','membre') NOT NULL DEFAULT 'membre',
  statut ENUM('actif','suspendu') NOT NULL DEFAULT 'actif',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role),
  KEY idx_users_statut (statut),
  KEY idx_users_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Books (Lectures)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS books (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  titre VARCHAR(255) NOT NULL,
  auteur VARCHAR(255) NULL,
  description TEXT NULL,
  cover_path VARCHAR(512) NULL,
  date_debut DATE NULL,
  date_fin DATE NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  -- Index préfixés pour compatibilité MySQL/MariaDB (limite 767 bytes avec utf8mb4)
  KEY idx_books_titre (titre(191)),
  KEY idx_books_auteur (auteur(191)),
  KEY idx_books_dates (date_debut, date_fin),
  KEY idx_books_created_by (created_by),
  CONSTRAINT fk_books_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Documents
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS documents (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  book_id BIGINT UNSIGNED NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  stored_filename VARCHAR(255) NOT NULL,
  filepath VARCHAR(700) NOT NULL,
  mime VARCHAR(120) NOT NULL,
  size_bytes BIGINT UNSIGNED NOT NULL,
  uploaded_by BIGINT UNSIGNED NULL,
  uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_documents_book (book_id),
  KEY idx_documents_uploaded_by (uploaded_by),
  KEY idx_documents_uploaded_at (uploaded_at),
  CONSTRAINT fk_documents_book
    FOREIGN KEY (book_id) REFERENCES books(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_documents_uploaded_by
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Reviews (Avis)
-- 1 avis max par user et book.
-- Modération: masquage sans suppression.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS reviews (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  book_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  note TINYINT UNSIGNED NOT NULL,
  commentaire TEXT NULL,
  is_hidden TINYINT(1) NOT NULL DEFAULT 0,
  hidden_by BIGINT UNSIGNED NULL,
  hidden_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_reviews_book_user (book_id, user_id),
  KEY idx_reviews_book (book_id),
  KEY idx_reviews_user (user_id),
  KEY idx_reviews_hidden (is_hidden),
  CONSTRAINT chk_reviews_note_range CHECK (note BETWEEN 1 AND 5),
  CONSTRAINT fk_reviews_book
    FOREIGN KEY (book_id) REFERENCES books(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_reviews_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_reviews_hidden_by
    FOREIGN KEY (hidden_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Progress
-- 1 ligne max par user et book (update au lieu d'insert multiples).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS progress (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  book_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  pourcentage TINYINT UNSIGNED NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_progress_book_user (book_id, user_id),
  KEY idx_progress_book (book_id),
  KEY idx_progress_user (user_id),
  CONSTRAINT chk_progress_pourcentage_range CHECK (pourcentage BETWEEN 0 AND 100),
  CONSTRAINT fk_progress_book
    FOREIGN KEY (book_id) REFERENCES books(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_progress_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Sessions (live/rencontre)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  book_id BIGINT UNSIGNED NOT NULL,
  titre VARCHAR(255) NOT NULL,
  date_heure DATETIME NOT NULL,
  lien VARCHAR(700) NULL,
  lieu VARCHAR(255) NULL,
  description TEXT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_sessions_book (book_id),
  KEY idx_sessions_date (date_heure),
  KEY idx_sessions_created_by (created_by),
  CONSTRAINT fk_sessions_book
    FOREIGN KEY (book_id) REFERENCES books(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_sessions_created_by
    FOREIGN KEY (created_by) REFERENCES users(id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Session attendance (inscription/présence)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS session_attendance (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  session_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NOT NULL,
  statut ENUM('inscrit','present','absent','annule') NOT NULL DEFAULT 'inscrit',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_attendance_session_user (session_id, user_id),
  KEY idx_attendance_user (user_id),
  KEY idx_attendance_statut (statut),
  CONSTRAINT fk_attendance_session
    FOREIGN KEY (session_id) REFERENCES sessions(id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_attendance_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

