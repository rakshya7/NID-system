-- v3: mobile verification, duplicate-enrollment protection, replacement payments. Run on an existing v2 database.
USE nid_prototype;
ALTER TABLE users ADD mobile VARCHAR(10), ADD mobile_verified TINYINT DEFAULT 0;
ALTER TABLE applications ADD UNIQUE KEY uq_one_enrollment_per_citizen(user_id);
CREATE TABLE otp_verifications(id INT AUTO_INCREMENT PRIMARY KEY,citizen_id INT NOT NULL,otp_hash VARCHAR(255) NOT NULL,expires_at DATETIME NOT NULL,attempt_count TINYINT DEFAULT 0,verified_at DATETIME NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(citizen_id) REFERENCES users(id));
CREATE TABLE replacement_requests(id INT AUTO_INCREMENT PRIMARY KEY,nin VARCHAR(14) NOT NULL,mobile VARCHAR(10) NOT NULL,name_en VARCHAR(100) NOT NULL,name_ne VARCHAR(100) NOT NULL,office_id INT NOT NULL,amount DECIMAL(8,2) NOT NULL DEFAULT 500,payment_method VARCHAR(20),payment_status ENUM('pending','paid') DEFAULT 'pending',transaction_id VARCHAR(30),payment_date DATETIME,appt_date DATE,appt_time VARCHAR(10),token VARCHAR(20),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(office_id) REFERENCES offices(id));
