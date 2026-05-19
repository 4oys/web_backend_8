
CREATE DATABASE IF NOT EXISTS u82564_autofinder
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE u82564_autofinder;


CREATE TABLE IF NOT EXISTS autofinder_requests (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) NOT NULL,
    wishes TEXT,
    status ENUM('new', 'processed', 'completed') DEFAULT 'new',
    login VARCHAR(50) UNIQUE,
    password_hash VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY unique_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS autofinder_admin_users (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    login VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


INSERT INTO autofinder_admin_users (login, password_hash) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE login = login;


CREATE VIEW autofinder_requests_view AS
SELECT 
    id,
    name,
    phone,
    email,
    wishes,
    status,
    login,
    created_at,
    CASE 
        WHEN status = 'new' THEN '🟢 Новая'
        WHEN status = 'processed' THEN '🟡 В обработке'
        WHEN status = 'completed' THEN '🔵 Завершена'
        ELSE status
    END as status_text
FROM autofinder_requests
ORDER BY created_at DESC;