-- ============================================================
-- C Cube Event Management System - Database Schema
-- Database: ccube_event_management
-- ============================================================

CREATE DATABASE IF NOT EXISTS ccube_event_management
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE ccube_event_management;

-- ─── USERS ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(120)  NOT NULL,
    email           VARCHAR(180)  NOT NULL,
    password_hash   VARCHAR(255)  NOT NULL,
    phone           VARCHAR(30)   DEFAULT NULL,
    institution     VARCHAR(200)  DEFAULT NULL,
    role            ENUM('participant','admin') NOT NULL DEFAULT 'participant',
    account_status  ENUM('active','inactive','banned') NOT NULL DEFAULT 'active',
    created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email),
    INDEX idx_users_role (role),
    INDEX idx_users_status (account_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── CATEGORIES ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    slug        VARCHAR(120) NOT NULL,
    description TEXT         DEFAULT NULL,
    icon        VARCHAR(80)  DEFAULT 'fa-trophy',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── EVENTS ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS events (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id           INT UNSIGNED DEFAULT NULL,
    title                 VARCHAR(200)  NOT NULL,
    slug                  VARCHAR(220)  NOT NULL,
    short_description     VARCHAR(400)  DEFAULT NULL,
    description           LONGTEXT      DEFAULT NULL,
    banner_path           VARCHAR(300)  DEFAULT NULL,
    event_date            DATETIME      DEFAULT NULL,
    registration_deadline DATETIME      DEFAULT NULL,
    venue                 VARCHAR(300)  DEFAULT NULL,
    registration_fee      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    capacity              INT UNSIGNED  DEFAULT NULL,
    minimum_team_size     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    maximum_team_size     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    eligibility           TEXT          DEFAULT NULL,
    rules                 TEXT          DEFAULT NULL,
    prize_information     TEXT          DEFAULT NULL,
    status                ENUM('draft','published','closed','cancelled') NOT NULL DEFAULT 'draft',
    created_at            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_events_slug (slug),
    INDEX idx_events_status (status),
    INDEX idx_events_date (event_date),
    INDEX idx_events_deadline (registration_deadline),
    CONSTRAINT fk_events_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── REGISTRATIONS ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS registrations (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    registration_reference VARCHAR(20) NOT NULL,
    user_id              INT UNSIGNED NOT NULL,
    event_id             INT UNSIGNED NOT NULL,
    participation_type   ENUM('individual','team') NOT NULL DEFAULT 'individual',
    team_name            VARCHAR(150) DEFAULT NULL,
    additional_info      TEXT         DEFAULT NULL,
    registration_status  ENUM('pending_payment','pending_approval','approved','rejected','cancelled') NOT NULL DEFAULT 'pending_payment',
    admin_note           TEXT         DEFAULT NULL,
    reviewed_by          INT UNSIGNED DEFAULT NULL,
    reviewed_at          DATETIME     DEFAULT NULL,
    created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reg_reference (registration_reference),
    UNIQUE KEY uq_reg_user_event (user_id, event_id),
    INDEX idx_reg_status (registration_status),
    INDEX idx_reg_event (event_id),
    CONSTRAINT fk_reg_user    FOREIGN KEY (user_id)      REFERENCES users(id)  ON DELETE CASCADE,
    CONSTRAINT fk_reg_event   FOREIGN KEY (event_id)     REFERENCES events(id) ON DELETE CASCADE,
    CONSTRAINT fk_reg_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── TEAM MEMBERS ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS team_members (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    registration_id     INT UNSIGNED NOT NULL,
    member_name         VARCHAR(120) NOT NULL,
    member_email        VARCHAR(180) DEFAULT NULL,
    member_institution  VARCHAR(200) DEFAULT NULL,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tm_reg (registration_id),
    CONSTRAINT fk_tm_registration FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── PAYMENTS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS payments (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    registration_id       INT UNSIGNED NOT NULL,
    transaction_reference VARCHAR(200) DEFAULT NULL,
    amount                DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    receipt_path          VARCHAR(300)  DEFAULT NULL,
    payment_status        ENUM('not_submitted','pending','verified','rejected') NOT NULL DEFAULT 'not_submitted',
    submitted_at          DATETIME      DEFAULT NULL,
    verified_by           INT UNSIGNED  DEFAULT NULL,
    verified_at           DATETIME      DEFAULT NULL,
    rejection_reason      TEXT          DEFAULT NULL,
    created_at            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_payment_reg (registration_id),
    INDEX idx_payment_status (payment_status),
    CONSTRAINT fk_pay_registration FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
    CONSTRAINT fk_pay_verifier     FOREIGN KEY (verified_by)     REFERENCES users(id)          ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── NOTIFICATIONS ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    title      VARCHAR(200) NOT NULL,
    message    TEXT         NOT NULL,
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_user   (user_id),
    INDEX idx_notif_unread (user_id, is_read),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── AUDIT LOGS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS audit_logs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT UNSIGNED DEFAULT NULL,
    action        VARCHAR(120) NOT NULL,
    entity_type   VARCHAR(80)  DEFAULT NULL,
    entity_id     INT UNSIGNED DEFAULT NULL,
    details       TEXT         DEFAULT NULL,
    ip_address    VARCHAR(45)  DEFAULT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_admin  (admin_user_id),
    INDEX idx_audit_entity (entity_type, entity_id),
    CONSTRAINT fk_audit_admin FOREIGN KEY (admin_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── SETTINGS ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS settings (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100) NOT NULL,
    setting_value TEXT         DEFAULT NULL,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── LOGIN ATTEMPTS (rate limiting) ──────────────────────────
CREATE TABLE IF NOT EXISTS login_attempts (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45)  NOT NULL,
    email      VARCHAR(180) DEFAULT NULL,
    attempted_at DATETIME   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_la_ip    (ip_address),
    INDEX idx_la_email (email),
    INDEX idx_la_time  (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
