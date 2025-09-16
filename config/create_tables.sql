CREATE DATABASE IF NOT EXISTS schedule_db CHARACTER SET utf8 COLLATE utf8_general_ci;

USE schedule_db;

CREATE TABLE IF NOT EXISTS schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_name VARCHAR(255) NOT NULL,
    group_name VARCHAR(255) NOT NULL,
    monday VARCHAR(100) DEFAULT '-',
    tuesday VARCHAR(100) DEFAULT '-',
    wednesday VARCHAR(100) DEFAULT '-',
    thursday VARCHAR(100) DEFAULT '-',
    friday VARCHAR(100) DEFAULT '-',
    saturday VARCHAR(100) DEFAULT '-',
    sunday VARCHAR(100) DEFAULT '-',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Добавляем тестового администратора (пароль: admin123)
INSERT INTO admin_users (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Добавляем тестовые данные расписания
INSERT INTO schedule (teacher_name, group_name, monday, tuesday, wednesday, thursday, friday, saturday, sunday) VALUES
('Иванов И.И.', 'Хор "Радуга"', '9:00-10:30', '-', '9:00-10:30', '-', '9:00-10:30', '10:00-11:30', '-'),
('Петрова А.С.', 'Танцы "Звездочки"', '-', '15:00-16:30', '-', '15:00-16:30', '-', '14:00-15:30', '-'),
('Сидоров П.П.', 'Театр "Маска"', '11:00-12:30', '11:00-12:30', '-', '11:00-12:30', '11:00-12:30', '-', '16:00-17:30');