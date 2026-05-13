CREATE DATABASE IF NOT EXISTS thesis_project;
USE thesis_project;

CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    created_at DATETIME NOT NULL
);