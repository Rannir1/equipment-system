-- =====================================================
-- Seed Data
-- =====================================================
SET NAMES utf8mb4;

-- Default admin: ID=000000000 pass=Admin1234
INSERT INTO users (id_number, full_name, email, phone, role, password_hash) VALUES
('000000000', 'מנהל מחסן', 'admin@school.ac.il', '050-0000000', 'admin',
 '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uFpCCLBim');

-- Default settings
INSERT INTO settings (`key`, `value`) VALUES
('institution_name', 'בית הספר לתקשורת'),
('institution_subtitle', 'מחסן ציוד'),
('loan_policy', 'הציוד מוחזר במועד שנקבע. נזק לציוד יחויב על השואל. אין להעביר ציוד לצד שלישי.'),
('warehouse_hours_enabled', '0'),
('closure_enforcement', '0'),
('auto_approve', '0'),
('max_loan_days', '14'),
('items_per_page', '20');

-- Journals and inventory are inserted by 03_import_data.sql
-- (from the real inventory.csv and students_demo_100.csv files)
