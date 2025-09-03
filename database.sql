-- Takvim Sistemi Veritabanı Kurulum Scripti
-- MySQL için tasarlanmıştır

CREATE DATABASE IF NOT EXISTS deneme CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE deneme;

-- Kullanıcılar tablosu
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Etkinlikler tablosu
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_event_date (event_date),
    INDEX idx_user_id (user_id)
);

-- Varsayılan admin kullanıcısı ekleme
-- Şifre: admin123 (hash'lenmiş hali)
INSERT INTO users (username, password, is_admin) VALUES 
('admin', '$2y$12$DOILFALGlpLMQdi5IqCNxurdQwJWBA1UpbnSLmbnduERRIGVCmuP6', 1);

-- Örnek kullanıcı ekleme
INSERT INTO users (username, password, is_admin) VALUES 
('kullanici1', '$2y$12$DOILFALGlpLMQdi5IqCNxurdQwJWBA1UpbnSLmbnduERRIGVCmuP6', 0);

-- Örnek etkinlik ekleme
INSERT INTO events (title, description, event_date, event_time, user_id) VALUES 
('Toplantı', 'Haftalık ekip toplantısı', '2024-02-15', '14:00:00', 1),
('Proje Sunumu', 'Yeni proje sunumu', '2024-02-20', '10:30:00', 2);