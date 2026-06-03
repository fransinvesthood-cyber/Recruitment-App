-- Create contact_messages table (admin inbox for Contact Us)
-- Run in phpMyAdmin on database: recruitment_db

CREATE TABLE IF NOT EXISTS contact_messages (
  contact_message_id INT(11) NOT NULL AUTO_INCREMENT,
  fullname VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  replied TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (contact_message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


