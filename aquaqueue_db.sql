-- ============================================================
--  AquaQueue — Complete Database Schema + Seed Data
--  XAMPP (MySQL 5.7+ / MariaDB 10.3+)
--  1. Open phpMyAdmin  →  http://localhost/phpmyadmin
--  2. Click "Import"   →  choose this file  →  Go
--  (or via CLI: mysql -u root < aquaqueue_db.sql)
-- ============================================================

CREATE DATABASE IF NOT EXISTS aquaqueue_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE aquaqueue_db;

-- ── 1. ROLES ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS roles (
    id          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(30)  NOT NULL UNIQUE,
    label       VARCHAR(60)  NOT NULL,
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO roles (name, label, description) VALUES
('developer',     'Developer',       'Full system access. Manages all admins, roles, settings, and audit logs.'),
('admin',         'Main Admin',      'Manages all booking services and their service admins.'),
('service_admin', 'Service Admin',   'Manages ONE assigned booking service only.'),
('user',          'Registered User', 'Can book appointments, track queue, and manage personal profile.'),
('client',        'Guest Client',    'Can browse and book as a guest. Limited access after booking.');

-- ── 2. USERS ─────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id         TINYINT UNSIGNED NOT NULL DEFAULT 4,
    first_name      VARCHAR(60)  NOT NULL,
    last_name       VARCHAR(60)  NOT NULL,
    email           VARCHAR(120) NOT NULL UNIQUE,
    phone           VARCHAR(25),
    password_hash   VARCHAR(255) NOT NULL,
    avatar_url      VARCHAR(500),
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    email_verified  TINYINT(1)   NOT NULL DEFAULT 0,
    last_login_at   TIMESTAMP NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role  ON users(role_id);

-- Seed users (INSERT IGNORE = safe to re-run without duplicates)
INSERT IGNORE INTO users (role_id, first_name, last_name, email, phone, password_hash, is_active, email_verified) VALUES
-- Developer  (role 1)  →  dev@secure2025
(1, 'Carl',  'Binghay',  'dev@aquaqueue.ph',           '+63 917 000 0001', '$2b$12$syAlLsbI.NCvl/ebyw/Uc.nHA1hDo4Wl8CwqhVluOccBZHkvYBgJ6',    1, 1),
-- Main Admin (role 2)  →  admin@secure2025
(2, 'Admin', 'User',     'admin@aquaqueue.ph',          '+63 917 000 0002', '$2b$12$ivk6.9EVcgKRZQf3BY.DdO4CH.WhIarXLlQSPkC7YNn./7PrbtAKC',  1, 1),
-- CB Admin   (role 2)  →  12345678
(2, 'CB',    'Admin',    'cb@aqua.com',                 '+63 917 000 0009', '$2b$12$LKJG1hq/5Xu7drn/y6a1.eR5BWkDerwsrx2ODAh4O2bGsBTvFkvZq',     1, 1),
-- Legacy admin         →  admin123
(2, 'Admin', 'Test',     'admin@test.com',              '+63 917 000 0003', '$2b$12$4DuklTOKnoIde5J2a.x12.xZAPLY.I9sOsqXVV8INN1Pr8p.VZlpy', 1, 1),
-- Service Admins (role 3)  →  svcadmin@2025
(3, 'Maria', 'Cruz',     'admin.medical@aquaqueue.ph',  '+63 917 000 0010', '$2b$12$T.Ortbt6nw2VhLiU/S9/VOaicKsOT7vvGaqc8XWcJEtIJBTL6bSeG',    1, 1),
(3, 'Jose',  'Santos',   'admin.salon@aquaqueue.ph',    '+63 917 000 0011', '$2b$12$T.Ortbt6nw2VhLiU/S9/VOaicKsOT7vvGaqc8XWcJEtIJBTL6bSeG',    1, 1),
(3, 'Ana',   'Reyes',    'admin.dental@aquaqueue.ph',   '+63 917 000 0012', '$2b$12$T.Ortbt6nw2VhLiU/S9/VOaicKsOT7vvGaqc8XWcJEtIJBTL6bSeG',    1, 1),
-- Regular User (role 4)  →  password123
(4, 'John',  'User',     'user@test.com',               '+63 917 000 0020', '$2b$12$O/jFlv6zIFMVV99nn1nYkeR6vgly9BO.m3ix3q9ncNdH/hrtDHkie',  1, 1);

-- ── 3. BOOKING SERVICES ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS booking_services (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug        VARCHAR(60)  NOT NULL UNIQUE,
    name        VARCHAR(120) NOT NULL,
    description TEXT,
    icon_class  VARCHAR(60),
    color_hex   VARCHAR(7)   DEFAULT '#71C9CE',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_by  INT UNSIGNED,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_bsvc_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

INSERT IGNORE INTO booking_services (slug, name, description, icon_class, color_hex) VALUES
('medical',  'Medical Consultation', 'General practitioner consultations and specialist referrals.',  'fa-stethoscope', '#71C9CE'),
('salon',    'Hair Salon',           'Haircut, styling, coloring, and treatment services.',            'fa-cut',         '#F9A8D4'),
('dental',   'Dental Checkup',       'General and cosmetic dental services.',                          'fa-tooth',       '#86EFAC'),
('legal',    'Legal Consultation',   'Corporate, family, and general legal advisory services.',        'fa-gavel',       '#FCD34D'),
('vehicle',  'Vehicle Service',      'Car maintenance, diagnostics, and repair.',                      'fa-car',         '#93C5FD'),
('business', 'Business Meeting',     'Coworking space and meeting room reservations.',                 'fa-briefcase',   '#C4B5FD');

-- ── 4. SERVICE ADMIN ASSIGNMENTS ─────────────────────────────────
CREATE TABLE IF NOT EXISTS service_admin_assignments (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id               INT UNSIGNED NOT NULL,
    service_id            INT UNSIGNED NOT NULL,
    assigned_by           INT UNSIGNED,
    can_manage_queue      TINYINT(1) NOT NULL DEFAULT 1,
    can_manage_bookings   TINYINT(1) NOT NULL DEFAULT 1,
    can_view_reports      TINYINT(1) NOT NULL DEFAULT 0,
    can_manage_locations  TINYINT(1) NOT NULL DEFAULT 0,
    assigned_at           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_service (user_id, service_id),
    CONSTRAINT fk_saa_user     FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_saa_service  FOREIGN KEY (service_id) REFERENCES booking_services(id) ON DELETE CASCADE,
    CONSTRAINT fk_saa_assigner FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Assign service admins (IDs: Maria=5, Jose=6, Ana=7 based on INSERT order above; Admin=2)
INSERT IGNORE INTO service_admin_assignments (user_id, service_id, assigned_by, can_manage_queue, can_manage_bookings, can_view_reports)
SELECT u.id, bs.id, 2, 1, 1, 1
FROM users u, booking_services bs
WHERE (u.email='admin.medical@aquaqueue.ph' AND bs.slug='medical')
   OR (u.email='admin.salon@aquaqueue.ph'   AND bs.slug='salon')
   OR (u.email='admin.dental@aquaqueue.ph'  AND bs.slug='dental');

-- ── 5. SERVICE LOCATIONS ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS service_locations (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id   INT UNSIGNED NOT NULL,
    name         VARCHAR(120) NOT NULL,
    address      VARCHAR(255),
    phone        VARCHAR(25),
    email        VARCHAR(120),
    hours        VARCHAR(120),
    price        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    duration_min SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    is_active    TINYINT(1) NOT NULL DEFAULT 1,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_loc_service FOREIGN KEY (service_id) REFERENCES booking_services(id) ON DELETE CASCADE
);

INSERT IGNORE INTO service_locations (service_id, name, address, phone, hours, price, duration_min)
SELECT id, CONCAT(name, ' — Main Branch'), 'Quezon City, Philippines', '+63 2 8123 4567', 'Mon–Fri 9AM–6PM', 0.00, 30
FROM booking_services;

-- ── 6. APPOINTMENTS ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS appointments (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED,
    service_id       INT UNSIGNED NOT NULL,
    location_id      INT UNSIGNED NOT NULL,
    queue_number     VARCHAR(10)  NOT NULL,
    appointment_date DATE         NOT NULL,
    appointment_time TIME         NOT NULL,
    priority         ENUM('standard','express','vip') NOT NULL DEFAULT 'standard',
    base_price       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes            TEXT,
    guest_name       VARCHAR(120),
    guest_email      VARCHAR(120),
    guest_phone      VARCHAR(25),
    status           ENUM('pending','confirmed','in_queue','serving','completed','cancelled','no_show') NOT NULL DEFAULT 'pending',
    confirmed_at     TIMESTAMP NULL,
    served_at        TIMESTAMP NULL,
    completed_at     TIMESTAMP NULL,
    cancelled_at     TIMESTAMP NULL,
    cancellation_reason VARCHAR(255),
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_apt_user     FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_apt_service  FOREIGN KEY (service_id) REFERENCES booking_services(id),
    CONSTRAINT fk_apt_location FOREIGN KEY (location_id) REFERENCES service_locations(id)
);
CREATE INDEX IF NOT EXISTS idx_apt_date    ON appointments(appointment_date);
CREATE INDEX IF NOT EXISTS idx_apt_user    ON appointments(user_id);
CREATE INDEX IF NOT EXISTS idx_apt_status  ON appointments(status);

-- ── 7. QUEUE STATUS ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS queue_status (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    location_id    INT UNSIGNED NOT NULL,
    queue_date     DATE NOT NULL,
    current_number VARCHAR(10),
    counter_prefix CHAR(1) NOT NULL DEFAULT 'A',
    last_issued    SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_paused      TINYINT(1) NOT NULL DEFAULT 0,
    is_open        TINYINT(1) NOT NULL DEFAULT 1,
    updated_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_loc_date (location_id, queue_date),
    CONSTRAINT fk_qs_location FOREIGN KEY (location_id) REFERENCES service_locations(id) ON DELETE CASCADE
);

-- ── 8. NOTIFICATIONS ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id        INT UNSIGNED,
    appointment_id INT UNSIGNED,
    type           ENUM('confirmation','reminder','turn_approaching','served','cancellation','system') NOT NULL,
    message        TEXT NOT NULL,
    is_read        TINYINT(1) NOT NULL DEFAULT 0,
    sent_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id)        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_apt  FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
);

-- ── 9. AUDIT LOG ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS audit_log (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_id    INT UNSIGNED,
    actor_role  VARCHAR(30),
    action      VARCHAR(80) NOT NULL,
    target_type VARCHAR(40),
    target_id   INT UNSIGNED,
    meta        JSON,
    ip_address  VARCHAR(45),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ── 10. PASSWORD RESET TOKENS ────────────────────────────────────
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(120) NOT NULL,
    token_hash VARCHAR(64)  NOT NULL,
    expires_at TIMESTAMP    NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_prt_email (email),
    INDEX idx_prt_token (token_hash)
);

-- ── CREDENTIAL REFERENCE (keep private — remove before production) ──
-- dev@aquaqueue.ph           →  dev@secure2025
-- admin@aquaqueue.ph         →  admin@secure2025
-- cb@aqua.com                →  12345678
-- admin@test.com             →  admin123
-- admin.medical@aquaqueue.ph →  svcadmin@2025
-- admin.salon@aquaqueue.ph   →  svcadmin@2025
-- admin.dental@aquaqueue.ph  →  svcadmin@2025
-- user@test.com              →  password123
