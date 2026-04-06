-- =====================================================
-- Equipment Management System — Schema
-- =====================================================
SET NAMES utf8mb4;
SET time_zone = '+02:00';

-- -------------------------------------------------------
-- Users
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    id_number   VARCHAR(20) NOT NULL UNIQUE COMMENT 'תעודת זהות',
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(150) UNIQUE,
    phone       VARCHAR(20),
    role        ENUM('admin','student') NOT NULL DEFAULT 'student',
    password_hash VARCHAR(255) NOT NULL,
    is_active   TINYINT(1) NOT NULL DEFAULT 1,
    login_attempts  INT NOT NULL DEFAULT 0,
    locked_until    DATETIME NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Journals (equipment categories / calendars)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS journals (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    color       VARCHAR(7) DEFAULT '#3b82f6',
    is_hidden   TINYINT(1) NOT NULL DEFAULT 0,
    sort_order  INT NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Inventory items
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS inventory (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    barcode         VARCHAR(50) UNIQUE,
    name            VARCHAR(150) NOT NULL,
    description     TEXT,
    journal_id      INT NULL,
    category        VARCHAR(100),
    brand           VARCHAR(100),
    model           VARCHAR(100),
    serial_number   VARCHAR(100),
    location        VARCHAR(100),
    condition_status ENUM('תקין','פגום','בתיקון','מושבת') NOT NULL DEFAULT 'תקין',
    is_loanable     TINYINT(1) NOT NULL DEFAULT 1,
    quantity        INT NOT NULL DEFAULT 1,
    quantity_available INT NOT NULL DEFAULT 1,
    purchase_date   DATE NULL,
    purchase_price  DECIMAL(10,2) NULL,
    notes           TEXT,
    image_path      VARCHAR(255),
    is_removed      TINYINT(1) NOT NULL DEFAULT 0,
    removed_at      DATETIME NULL,
    removed_reason  TEXT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Orders
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    order_number    VARCHAR(20) NOT NULL UNIQUE,
    user_id         INT NOT NULL,
    inventory_id    INT NOT NULL,
    status          ENUM('ממתין לאישור','אושר','מוכן','סופק','הוחזר חלקית','הוחזר','לא נלקח','נדחה') NOT NULL DEFAULT 'ממתין לאישור',
    loan_date       DATE NOT NULL,
    loan_time       TIME NOT NULL DEFAULT '09:00:00',
    return_date     DATE NOT NULL,
    return_time     TIME NOT NULL DEFAULT '17:00:00',
    purpose         VARCHAR(255),
    notes           TEXT,
    admin_notes     TEXT,
    recurring_group VARCHAR(50) NULL,
    is_deleted      TINYINT(1) NOT NULL DEFAULT 0,
    deleted_at      DATETIME NULL,
    created_by      INT NOT NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (inventory_id) REFERENCES inventory(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Notifications
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    title       VARCHAR(200) NOT NULL,
    body        TEXT,
    type        ENUM('info','success','warning','error') DEFAULT 'info',
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    related_order_id INT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (related_order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Settings
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    `key`       VARCHAR(100) PRIMARY KEY,
    `value`     TEXT,
    updated_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Activity log
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS activity_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NULL,
    action      VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id   INT NULL,
    details     JSON,
    ip_address  VARCHAR(45),
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Warehouse closures
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS warehouse_closures (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(150) NOT NULL,
    start_date  DATE NOT NULL,
    end_date    DATE NOT NULL,
    message     TEXT,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes
CREATE INDEX idx_inventory_journal ON inventory(journal_id);
CREATE INDEX idx_inventory_barcode ON inventory(barcode);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_inventory ON orders(inventory_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_loan_date ON orders(loan_date);
CREATE INDEX idx_notifications_user ON notifications(user_id, is_read);
CREATE INDEX idx_activity_log_user ON activity_log(user_id);
