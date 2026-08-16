-- ============================================
-- Army Personnel Database System
-- Import this file via phpMyAdmin before running the app
-- ============================================

CREATE DATABASE IF NOT EXISTS army_personnel_db;
USE army_personnel_db;

-- ---------- ROLES & USERS (system login accounts) ----------
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE   -- superadmin, admin, user
);

INSERT INTO roles (name) VALUES ('superadmin'), ('admin'), ('user'), ('operator'), ('Daily');

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    status ENUM('active','disabled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Default superadmin login: username = superadmin / password = Admin@123
-- (hash below corresponds to "Admin@123" — CHANGE THIS after first login)
INSERT INTO users (username, password_hash, role_id) VALUES
('superadmin', '$2y$10$MfEV5qTDLpyZghA3ADU23O0Sj1mY6O0343y9R3oMG4AvRhy13JyTS', 1);

-- ---------- LOOKUP TABLES (admin-editable dropdown options) ----------
CREATE TABLE ranks (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE);
CREATE TABLE units (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE);
CREATE TABLE cadres (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE);
CREATE TABLE platoons (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE);
CREATE TABLE blood_groups (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(10) NOT NULL UNIQUE);
CREATE TABLE courses (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL UNIQUE);
CREATE TABLE medical_categories (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE);

INSERT INTO ranks (name) VALUES ('Sergeant'),('Corporal'),('Major'),('Colonel');
INSERT INTO units (name) VALUES ('Alpha Company'),('Bravo Company'),('HQ');
INSERT INTO cadres (name) VALUES ('Infantry'),('Signals'),('Medical');
INSERT INTO blood_groups (name) VALUES ('A+'),('A-'),('B+'),('B-'),('O+'),('O-'),('AB+'),('AB-');

-- ---------- PERSONNEL (core table) ----------
CREATE TABLE personnel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personal_number VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    nid VARCHAR(20) NULL,
    photo_path VARCHAR(255) NULL,        -- Optional; can be NULL if no photo uploaded
    rank_id INT, unit_id INT, cadre_id INT, platoon_id INT, blood_group_id INT,
    appointment_id INT,
    batch VARCHAR(100),
    mobile_number VARCHAR(20),
    address TEXT,
    detailed_address TEXT,
    vill VARCHAR(255),
    po VARCHAR(255),
    ps VARCHAR(255),
    status ENUM('active','on_leave','training','punishment','retired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rank_id) REFERENCES ranks(id),
    FOREIGN KEY (unit_id) REFERENCES units(id),
    FOREIGN KEY (cadre_id) REFERENCES cadres(id),
    FOREIGN KEY (platoon_id) REFERENCES platoons(id),
    FOREIGN KEY (blood_group_id) REFERENCES blood_groups(id)
);

-- ---------- EDUCATION & TRAINING ----------
CREATE TABLE personnel_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    course_id INT NOT NULL,
    result VARCHAR(100),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id)
);

CREATE TABLE personnel_education (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    civil_education VARCHAR(150),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE
);

CREATE TABLE personnel_cadres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    cadre_id INT NOT NULL,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE,
    FOREIGN KEY (cadre_id) REFERENCES cadres(id) ON DELETE CASCADE
);

-- ---------- SERVICE DETAILS ----------
CREATE TABLE personnel_service (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    admission_date DATE,
    retirement_date DATE,
    un_mission VARCHAR(255),
    punishment_note TEXT,
    ipft_1st VARCHAR(255),
    ipft_2nd VARCHAR(255),
    ret VARCHAR(255),
    speed_march VARCHAR(255),
    cycle_1 VARCHAR(100),
    cycle_2 VARCHAR(100),
    cycle_3 VARCHAR(100),
    cycle_4 VARCHAR(100),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE
);

-- ---------- PERSONAL & FAMILY ----------
CREATE TABLE personnel_family (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    birthdate DATE,
    marriage_date DATE,
    marital_status ENUM('single','married','widowed','divorced'),
    father_name VARCHAR(200),
    father_mobile VARCHAR(30),
    mother_name VARCHAR(200),
    mother_mobile VARCHAR(30),
    spouse_name VARCHAR(200),
    spouse_mobile VARCHAR(30),
    children_count INT DEFAULT 0,
    family_member VARCHAR(30) DEFAULT 'No',
    fm_date_from DATE NULL,
    fm_date_to DATE NULL,
    fm_current_address TEXT NULL,
    living_status VARCHAR(30) DEFAULT NULL,
    family_member_notes TEXT,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE
);

-- ---------- HEALTH INFORMATION ----------
CREATE TABLE personnel_health (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    medical_category_id INT,
    height_cm DECIMAL(5,2),
    weight_kg DECIMAL(5,2),
    any_disease TEXT,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE,
    FOREIGN KEY (medical_category_id) REFERENCES medical_categories(id)
);

-- ---------- SOCIAL MEDIA LINKS ----------
CREATE TABLE personnel_social_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    platform VARCHAR(50) NOT NULL,   -- facebook, linkedin, whatsapp, etc.
    url VARCHAR(255) NOT NULL,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE
);

-- ---------- OTHER FEATURES ----------
CREATE TABLE personnel_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE
);

CREATE TABLE moqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE
);

CREATE TABLE personnel_moqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personnel_id INT NOT NULL,
    moq_id INT NOT NULL,
    result VARCHAR(100),
    FOREIGN KEY (personnel_id) REFERENCES personnel(id) ON DELETE CASCADE,
    FOREIGN KEY (moq_id) REFERENCES moqs(id)
);
