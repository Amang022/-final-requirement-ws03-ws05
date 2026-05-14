# 🌾 HarvestHub — Agricultural Inventory System

## Overview
HarvestHub is a web-based agricultural inventory management system designed to streamline the tracking of crops, fertilizers, tools, and other supplies. It features a modern, premium user interface, robust security, and role-based access control to help farmers and administrators efficiently manage their stock.

## How It Works
The system allows users to seamlessly manage inventory through an intuitive dashboard. 
- **Real-Time Tracking**: Monitor the total number of items and quickly identify low-stock products (items with a quantity of 10 or less).
- **Unlimited Inventory Management**: All users can freely add unlimited agricultural items directly into the active inventory.
- **Categorization & Search**: Items are categorized (e.g., Grains, Vegetables, Supplies) and can be easily searched.
- **Data Security**: Built with strong web security principles, including protection against SQL injection, XSS, and CSRF attacks.

## 👥 User Roles
- **Super Admin**: Full system control. Can add/archive administrators, reset admin passwords, and manage the entire inventory.
- **Admin**: Can add, update, archive, or restore items, as well as manage regular user accounts.
- **Regular User**: Can view and search the active inventory, and seamlessly add new items to the stock without arbitrary limits.

## 🔐 Data Security Implementation
HarvestHub implements several crucial security layers to protect the database and users:

| Threat | Implementation |
|---|---|
| **SQL Injection** | Uses `mysqli_prepare()` and `bind_param()` on every database query. |
| **XSS (Cross-Site Scripting)** | Uses `htmlspecialchars()` (via the `e()` helper function) on all user-provided output. |
| **CSRF (Cross-Site Request Forgery)** | Requires a per-session token that is validated on every form submission. |
| **Password Security** | Passwords are hashed using the secure BCRYPT algorithm (cost factor 12). |
| **ID Exposure** | Database IDs are never exposed in URLs. They are protected using `hash_hmac('sha256', $id, SECRET_KEY)`. |
| **Session Hijacking** | Uses `session_regenerate_id(true)` upon login and enforces secure, `HttpOnly` cookies. |

## 📁 Project Structure
```text
agri-inventory/
├── index.php              ← Login page
├── dashboard.php          ← Dashboard with stats and quick links
├── config/
│   └── db.php             ← Database connection settings & security constants
├── auth/
│   ├── login.php          ← Login processing
│   ├── logout.php         ← Session destruction
│   └── session.php        ← Session guarding & role verification helpers
├── pages/
│   ├── items.php          ← Inventory tracking (Add, View, Edit, Archive)
│   ├── users.php          ← User management
│   └── approvals.php      ← Item approval queue (Legacy/Admin)
├── includes/
│   ├── csrf.php           ← Security tokens for forms
│   ├── hash.php           ← URL ID encryption helpers
│   ├── functions.php      ← Shared helper functions
│   └── navbar.php         ← Website navigation bar
├── assets/
│   ├── css/style.css      ← Premium UI stylesheet
│   └── js/main.js         ← Interactive components (modals, search)
├── database/
│   └── agri_inventory.sql ← Database schema & seed data
└── README.md              ← Documentation
```

## 🚀 Quick Setup
1. **Clone the repository**: `git clone https://github.com/your-username/agri-inventory.git`
2. **Move to web server**: Place the folder in your `htdocs` (XAMPP) or `www` (WAMP) directory.
3. **Database Setup**: Create a database and import the `database/agri_inventory.sql` file.
4. **Configure Connection**: Edit `config/db.php` with your database credentials.


## 🧑‍💻 Development Team
- **Olympio Corpuz** (Frontend Developer)
- **Mark Christian Mendoza** (Frontend Developer)
- **Chester Wesley Yuzon** (Backend Developer)
