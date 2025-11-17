-- إنشاء جدول الكتب
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    grade_from VARCHAR(20),
    grade_to VARCHAR(20),
    price INT NOT NULL,
    quantity INT DEFAULT 0,
    series VARCHAR(100),
    term VARCHAR(20)
);

-- إنشاء جدول العمليات
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    type VARCHAR(20) NOT NULL, -- وارد أو صرف
    quantity INT NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- إدخال بيانات الكتب والسلاسل
-- سلاسل الإنجليزي
INSERT INTO books (title, category, grade_from, grade_to, price, quantity, series, term) VALUES
('Rise Up', 'انجليزي', 'الرابع', 'السادس', 1200, 0, 'Rise Up', NULL),
('Learn English - Core', 'انجليزي', 'الرابع', 'السادس', 500, 0, 'Learn English', NULL),
('Learn English - Work', 'انجليزي', 'الرابع', 'السادس', 500, 0, 'Learn English', NULL),
('Better - ترم أول', 'انجليزي', 'الرابع', 'السادس', 800, 0, 'Better', 'ترم أول'),
('Better - ترم ثاني', 'انجليزي', 'الرابع', 'السادس', 800, 0, 'Better', 'ترم ثاني'),

-- سلاسل الحاسوب
('ويندوز 7', 'حاسوب', 'الأول', 'الإعدادي', 500, 0, 'ويندوز 7', NULL),
('المستقبل الرقمي', 'حاسوب', 'الأول', 'الإعدادي', 500, 0, 'المستقبل الرقمي', NULL),

-- سلاسل التمهيدي
('الطفل المبدع - عربي', 'تمهيدي', NULL, NULL, 600, 0, 'الطفل المبدع', NULL),
('الطفل المبدع - انجليزي', 'تمهيدي', NULL, NULL, 600, 0, 'الطفل المبدع', NULL),
('الطفل المبدع - حساب', 'تمهيدي', NULL, NULL, 600, 0, 'الطفل المبدع', NULL),
('الطفل المبدع - قرآن', 'تمهيدي', NULL, NULL, 600, 0, 'الطفل المبدع', NULL),
('المبدع الصغير - عربي', 'تمهيدي', NULL, NULL, 0, 0, 'المبدع الصغير', 'أساسي'),
('المبدع الصغير - حساب', 'تمهيدي', NULL, NULL, 0, 0, 'المبدع الصغير', 'أساسي'),
('المبدع الصغير - إسلامية', 'تمهيدي', NULL, NULL, 0, 0, 'المبدع الصغير', 'أساسي'),
('المبدع الصغير - انجليزي', 'تمهيدي', NULL, NULL, 0, 0, 'المبدع الصغير', 'أساسي'),
('المبدع الصغير - علوم', 'تمهيدي', NULL, NULL, 0, 0, 'المبدع الصغير', 'إضافي'),
('المبدع الصغير - كمبيوتر', 'تمهيدي', NULL, NULL, 0, 0, 'المبدع الصغير', 'إضافي'),

-- تربية فنية
('تربية فنية', 'تربية فنية', 'الأول', 'الثاني الإعدادي', 500, 0, NULL, NULL);

-- تحديث أسعار وكمية المبدع الصغير (أساسي وإضافي)
UPDATE books SET price = 4000 WHERE series = 'المبدع الصغير';
UPDATE books SET price = 2400 WHERE series = 'الطفل المبدع'; 