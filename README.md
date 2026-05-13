# 🌾 AgriStock — Agricultural Inventory System

A web-based inventory management system for agricultural products with role-based access control and full web security implementation.

---

## 👥 User Roles

| Role        | Capabilities |
|-------------|-------------|
| Super Admin | Add/archive admins, reset admin passwords, full access |
| Admin       | Add/update/archive/restore items, approve items, manage regular users |
| Regular     | View & search approved items, add items (pending approval) |

---

## ⚙️ Requirements

- PHP 7.4 or higher
- MySQL 5.7+ / MariaDB 10.3+
- Apache or Nginx with mod_rewrite
- A web browser (Chrome, Firefox, Edge)

---

## 🚀 Setup Instructions

### 1. Clone the repository
```bash
git clone https://github.com/your-username/agri-inventory.git
cd agri-inventory
```

### 2. Import the database
```bash
mysql -u root -p < database/schema.sql
```
Or import via phpMyAdmin.

### 3. Configure the database
Edit `config/db.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_mysql_user');
define('DB_PASS', 'your_mysql_password');
define('DB_NAME', 'agri_inventory');
define('SECRET_KEY', 'replace_with_random_32_char_string');
```

### 4. Place in web server root
Move/copy the folder to your `htdocs` (XAMPP) or `www` (WAMP) directory.

### 5. Login with the default Super Admin account
| Field    | Value                  |
|----------|------------------------|
| Email    | superadmin@agri.local  |
| Password | Admin@1234             |

> ⚠️ **Change this password immediately after your first login!**
>
> If you imported the schema before this fix, re-import `database/schema.sql` or reset the `superadmin@agri.local` password via phpMyAdmin.

---

## 🔐 Security Implementation

| Threat       | Implementation |
|--------------|---------------|
| SQL Injection | `mysqli_prepare()` + `bind_param()` on every query |
| XSS          | `htmlspecialchars()` (`e()` helper) on all output |
| CSRF         | Per-session token, rotated after each POST |
| Password     | `password_hash()` with BCRYPT cost 12 |
| ID Exposure  | `hash_hmac('sha256', $id, SECRET_KEY)` for all URL/form IDs |
| Session Fix. | `session_regenerate_id(true)` on login |
| Cookie Sec.  | `HttpOnly`, `SameSite=Strict` session cookies |

---

## 📁 Project Structure

```
agri-inventory/
├── index.php              ← Login page
├── dashboard.php          ← Dashboard (stats & quick links)
├── config/
│   └── db.php             ← mysqli connection & constants
├── auth/
│   ├── login.php          ← Login POST handler
│   ├── logout.php         ← Session destroy
│   └── session.php        ← Session guard & role helpers
├── pages/
│   ├── items.php          ← Item CRUD (add, edit, archive, restore, search)
│   ├── users.php          ← User management (add, archive, reset password)
│   └── approvals.php      ← Item approval queue
├── includes/
│   ├── csrf.php           ← Token generate, field output, validate
│   ├── hash.php           ← ID encode/decode helpers
│   ├── functions.php      ← Shared helpers (e, log_activity, flash, redirect)
│   └── navbar.php         ← Shared navigation bar
├── assets/
│   ├── css/style.css      ← Full stylesheet
│   └── js/main.js         ← Modal, search, alert helpers
├── database/
│   └── schema.sql         ← Full DB schema + default super admin
└── README.md
```

---

## 🧑‍💻 Group Members

| Member                 | Role                 |
|------------------------|----------------------|
|  Olympio Corpuz        | Frontend Developer 1 |
|  Mark Christian Mendoza| Frontend Developer 2 |
|  Chester Wesley Yuzon  | Backend Developer    |

---

## 📌 Notes
- All database queries use prepared statements — no raw string interpolation in SQL.
- Item IDs and User IDs are never exposed as plain integers in URLs or form fields.
- The `activity_log` table records all significant actions for audit purposes.
- Regular users' added items remain `pending` until an Admin approves them.
