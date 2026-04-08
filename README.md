https://drive.google.com/drive/u/0/mobile/folders/1vLrSKCMe3FxoBL29o9aBb1-LWELT_SXD?usp=sharing

# 🔐 SSO (Single Sign-On) - Laravel Multi-App Authentication

## 📌 Overview

This project implements a **Single Sign-On (SSO)** system between two independent Laravel 9 applications — **Ecommerce App** and **Foodpanda App**. When a user logs in to the Ecommerce App, they are automatically authenticated in the Foodpanda App without re-entering credentials. Logging out from either app terminates the session on both.

---

## 🌐 Live Demo

| App | URL | Credentials |
|-----|-----|-------------|
| Ecommerce App | https://zs-ecm.smcglobal.shop | test@example.com / password123 |
| Foodpanda App | https://zsfp.smartrecovery.vip | test@example.com / password123 |

---

## 🏗️ Tech Stack

- **Framework:** Laravel 9
- **Database:** MySQL
- **Authentication:** Laravel Auth + Custom SSO via HMAC-SHA256 Signed Tokens
- **Session Driver:** Database
- **Frontend:** Bootstrap 5

---

## ⚙️ How SSO Works

### Login Flow
```
User logs in to Ecommerce App
        ↓
Ecommerce generates a signed payload (email + timestamp)
        ↓
User clicks "Go to Foodpanda" button
        ↓
Ecommerce redirects to Foodpanda with payload & HMAC signature
        ↓
Foodpanda verifies signature using shared SSO_SECRET
        ↓
Foodpanda checks token expiry (5 minutes max)
        ↓
Foodpanda auto-logins the user ✅
```

### Logout Flow
```
User clicks logout on Ecommerce App
        ↓
Ecommerce sends signed HTTP POST request to Foodpanda /sso/logout
        ↓
Foodpanda verifies signature and deletes user's DB session
        ↓
Both apps are logged out simultaneously ✅
```

### Security Measures
- **HMAC-SHA256** signature verification on every SSO request
- **Token expiry** — tokens are invalid after 5 minutes
- **Shared secret key** — stored in `.env`, never exposed publicly
- **hash_equals()** — used to prevent timing attacks

---

## 🚀 Local Setup Instructions

### Prerequisites
- PHP >= 8.0
- Composer
- MySQL
- Laravel 9

### Step 1: Clone the Repository
```bash
git clone https://github.com/your-username/sso-laravel.git
cd sso-laravel
```

### Step 2: Setup Ecommerce App
```bash
cd ecommerce-app
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```env
APP_NAME=EcommerceApp
APP_URL=http://localhost:8000
DB_DATABASE=ecommerce_db
DB_USERNAME=root
DB_PASSWORD=

SSO_SECRET=your-super-secret-key-123
FOODPANDA_URL=http://localhost:8001
```

```bash
php artisan migrate
php artisan db:seed --class=UserSeeder
php artisan serve --port=8000
```

### Step 3: Setup Foodpanda App
```bash
cd foodpanda-app
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```env
APP_NAME=FoodpandaApp
APP_URL=http://localhost:8001
DB_DATABASE=foodpanda_db
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
SSO_SECRET=your-super-secret-key-123
ECOMMERCE_URL=http://localhost:8000
```

```bash
php artisan migrate
php artisan db:seed --class=UserSeeder
php artisan serve --port=8001
```

### Step 4: Test SSO
1. Visit `http://localhost:8000` and login
2. Click **"Go to Foodpanda"** button on dashboard
3. You will be automatically logged in at `http://localhost:8001` ✅
4. Logout from Ecommerce — Foodpanda session also ends ✅

---

## 📁 Project Structure

```
sso-laravel/
├── ecommerce-app/
│   ├── app/Http/Controllers/
│   │   ├── AuthController.php       # Login, logout, dashboard
│   │   └── SSOController.php        # Token generation & Foodpanda notify
│   └── resources/views/
│       ├── auth/login.blade.php
│       └── dashboard.blade.php
│
└── foodpanda-app/
    ├── app/Http/Controllers/
    │   ├── AuthController.php       # Login, logout, dashboard
    │   └── SSOController.php        # Token verification & auto-login
    └── resources/views/
        ├── auth/login.blade.php
        └── dashboard.blade.php
```

---

## 🔑 Key Environment Variables

| Variable | Description |
|----------|-------------|
| `SSO_SECRET` | Shared secret key (must be identical in both apps) |
| `FOODPANDA_URL` | Foodpanda app base URL (set in Ecommerce .env) |
| `ECOMMERCE_URL` | Ecommerce app base URL (set in Foodpanda .env) |
| `SESSION_DRIVER` | Must be `database` in Foodpanda app for SSO logout |

---

## 👤 Default Test Credentials

```
Email:    test@example.com
Password: password123
```

---

---

## 👨‍💻 Author

- Developed by Ruhul Amin Sujon
- Laravel Developer
- Mid-Level Hiring Task – 2026
