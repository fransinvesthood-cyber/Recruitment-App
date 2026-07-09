-- Schema for calendar_events (used by save_event.php / delete_event.php / calendar.php)
-- Execute in your database (recruitment_db or your configured DB)

CREATE TABLE IF NOT EXISTS calendar_events (
  event_id INT NOT NULL AUTO_INCREMENT,
  created_by INT NOT NULL,
  title VARCHAR(255) NOT NULL,
  description TEXT NULL,
  event_type VARCHAR(50) NOT NULL DEFAULT 'Other',
  event_date DATE NOT NULL,
  start_time TIME NULL,
  end_time TIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (event_id),
  KEY idx_calendar_events_date (event_date),
  KEY idx_calendar_events_type (event_type),
  CONSTRAINT fk_calendar_events_created_by
    FOREIGN KEY (created_by) REFERENCES users (user_id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

