# Library Accounting & Inventory System

Comprehensive accounting and inventory system for school libraries. It covers acquisitions from suppliers, per-stage stock tracking, bulk sales to schools, cash-flow reporting, CSV exports, and granular role-based access (admin/user). Built with PHP (mysqli) and designed for deployment on classic LAMP/XAMPP stacks.

> Maintained by **Mohannad Nabil Ahmed Mohammed Abdullh**

---

## 📦 Features

- **Secure Authentication**
  - Password hashing with automatic upgrade for legacy accounts
  - Session hardening: secure cookies, regeneration, role enforcement
  - Double-layer CSRF protection for all forms (token + POST-only)
- **Inventory Management**
  - Books catalog with stage-level stock (`book_stages`)
  - Purchase (inbound) invoices adjust stock upwards
  - Sales (outbound) invoices deduct exact stage quantities
- **Financial Operations**
  - Track invoice totals, paid amounts, and remaining balances
  - Separate flows for single sales vs. group (school) orders
  - Editable payments and historical adjustments with rollback-safe logic
- **Reports & Export**
  - Filterable reports by year/month/day
  - CSV export for books, sales, invoices, and transactions
- **Admin Utilities**
  - Manage users, roles, and credentials with enforced uniqueness
  - Dashboard shortcuts and quick navigation

Arabic UI labels ensure native experience for users, while responsive CSS keeps screens usable on desktops and mobiles.

---

## 🗃️ Project Structure

```
library-system/
├─ assets/                 # Shared CSS styles
├─ config/
│  ├─ bootstrap.php        # Session/bootstrap loader
│  ├─ db.php               # Secure DB connection using .env/env vars
│  └─ helpers.php          # CSRF, auth guards, sanitizers, flash helpers
├─ public/                 # All HTTP endpoints (login, books, sales, reports…)
│  └─ admin/               # Admin-only screens (dashboard, users)
├─ index.php               # Auth-protected landing page
└─ schema.sql              # Latest database schema dump
```

---

## ⚙️ Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-org/library-accounting-and-inventory-system.git
   cd library-accounting-and-inventory-system
   ```

2. **Create `.env` at project root**
   ```ini
   DB_HOST=localhost
   DB_PORT=3306
   DB_USER=root
   DB_PASSWORD=
   DB_NAME=schema
   ```

3. **Import the schema**
   - Via phpMyAdmin: import `قاعده البيانات المصدره/schema.sql`
   - Or CLI:
     ```bash
     mysql -u root -p schema < "قاعده البيانات المصدره/schema.sql"
     ```

4. **Configure web server**
   - In XAMPP: copy project into `htdocs/library-system`
   - Ensure PHP 8.1+ with `mysqli` enabled

5. **Visit** `http://localhost/library-system/public/login.php`

---

## 🔁 Database Migration Notes

If upgrading an existing database instead of importing the provided dump, run:

```sql
ALTER TABLE sales_items ADD COLUMN stage VARCHAR(50) NOT NULL AFTER book_id;
ALTER TABLE sales_items MODIFY COLUMN price DECIMAL(10,2) NOT NULL;
UPDATE users SET password = '$2y$10$YgBo2WJ7DK/LRwRe0n9pB.xIL1Zw3rQsWih56ZF1kR3R2lHQTx.CG'
WHERE username = 'admin';
```

The password hash corresponds to the default `admin` credential (change it immediately after login).

---

## 🔐 Security Highlights

- Strict session cookies (`SameSite=Strict`, HttpOnly, HTTPS-aware)
- Role guards (`require_login('admin')`) on every sensitive endpoint
- CSRF tokens per-form with one-time validation
- Safe database access through prepared statements exclusively
- Sanitized output via helper `e()` to prevent XSS
- Inventory adjustments wrapped in MySQL transactions to avoid data race issues

---

## 🚀 Usage Walkthrough

1. **Admin Login**  
   - Default: `admin / admin` (hash upgraded automatically)
   - Change password via “Manage Users” immediately.

2. **Add Books**  
   - `عرض المخازن` → `إضافة كتاب جديد`: define stages + initial stock.

3. **Record Supplier Invoice (أوامر شراء)**  
   - `تسجيل عملية شراء`: select books/stages, set quantities, enter invoice number.

4. **Sell to Schools / Individuals**  
   - `بيع جماعي لمدرسة` for multi-line school invoices (supports samples / paid tracking).
   - `عرض عمليات البيع` for individual transactions.

5. **Reports & Exports**  
   - `تقارير المبيعات`: filter by period, export CSV.
   - Dedicated export buttons on books/transactions/invoices pages.

---

## 📱 Responsiveness

Primary layout components ship with CSS grid/flex adjustments and `@media (max-width: 700px)` rules to ensure:
- Smaller header height
- Single-column buttons/forms on mobile
- Larger touch targets and fonts

The shared stylesheet (`assets/style.css`) is auto-imported in the header to keep pages consistent.

---

## 🧪 Quality Checklist

- ✅ `php -l` across all PHP files
- ✅ Manual smoke tests: login, add book, purchase, sale, edit, delete
- ✅ CSV exports validated via spreadsheet import

For CI environments, add PHPUnit or API tests as needed (not bundled by default).

---

## 📄 License / Credits

Project authored and maintained by **Mohannad Nabil Ahmed Mohammed Abdullh**.  
Feel free to fork, extend, or integrate with your existing ERP flows. For commercial engagements or feature requests, please contact the maintainer directly.

