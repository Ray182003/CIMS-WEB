CREATE TABLE IF NOT EXISTS books_ranges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    start_year INT NOT NULL,
    end_year INT NOT NULL,
    label VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_range (start_year, end_year)
);
