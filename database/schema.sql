CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NULL,
  google_id VARCHAR(255) NULL,
  created_at DATETIME NOT NULL
);

CREATE TABLE IF NOT EXISTS transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type ENUM('income','expense') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  category VARCHAR(64) NOT NULL,
  notes TEXT NULL,
  occurred_on DATE NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_user_date (user_id, occurred_on),
  CONSTRAINT fk_transactions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS budgets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  category VARCHAR(64) NOT NULL,
  limit_amount DECIMAL(12,2) NOT NULL,
  period_month TINYINT NOT NULL,
  period_year SMALLINT NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_budget_period (user_id, category, period_year, period_month),
  CONSTRAINT fk_budgets_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS goals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  name VARCHAR(100) NOT NULL,
  target_amount DECIMAL(12,2) NOT NULL,
  saved_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  deadline DATE NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_goals_user (user_id),
  CONSTRAINT fk_goals_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS reminders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  type VARCHAR(50) NOT NULL,
  remind_on DATE NOT NULL,
  status ENUM('pending','done') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL,
  INDEX idx_reminders_date (user_id, remind_on),
  CONSTRAINT fk_reminders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

