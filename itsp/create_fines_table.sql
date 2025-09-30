CREATE TABLE IF NOT EXISTS fines (
    fine_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    book_id INT NOT NULL,
    record_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES users(student_id),
    FOREIGN KEY (book_id) REFERENCES books(book_id),
    FOREIGN KEY (record_id) REFERENCES borrowed_books(record_id)
); 