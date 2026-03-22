# Tirana Point — Digital Restaurant Menu System

A fully self-contained, multi-tenant digital menu platform built with **PHP 8.2 + SQLite**. No Composer, no external dependencies — just PHP and a browser.

## Features

- **Multi-tenant** — Unlimited restaurants sharing one database, each with a unique slug URL
- **Multilingual** — English, Arabic (RTL), Kurdish (RTL) — user selects language on splash page
- **Dynamic theming** — Each restaurant has its own brand color, background, and font
- **Splash page** — Language selection with optional video background
- **Full admin CMS** — Manage restaurants, menus, categories, items, media, ads, and users
- **Role-based access** — `superadmin`, `admin`, `editor` roles
- **Media library** — Multi-file upload, image/video management
- **Ads & Videos** — Splash backgrounds, pre-menu interstitials, in-menu banners
- **Public REST API** — JSON endpoints for all restaurant/menu data
- **Mobile-first** — Fully responsive on all screen sizes, iPhone safe-area aware
- **CSRF protection** — All forms protected

## Quick Start

```bash
# Clone the repo
git clone https://github.com/YOUR_USERNAME/tirana-point.git
cd tirana-point

# Seed the database
php database/seed.php

# Start the PHP dev server
php -S localhost:8000 index.php
```

Open [http://localhost:8000](http://localhost:8000)

**Admin panel:** [http://localhost:8000/admin/](http://localhost:8000/admin/)
**Default credentials:** `admin@tirana.local` / `admin123`

> ⚠️ Change the default password immediately after first login.

## URL Structure

| URL | Description |
|-----|-------------|
| `/` | Landing page — lists all active restaurants |
| `/{slug}` | Restaurant splash / language selection page |
| `/{slug}/menu` | Full digital menu |
| `/admin/` | Admin login |
| `/admin/dashboard.php` | Admin dashboard |
| `/api/restaurants` | JSON list of all restaurants |
| `/api/restaurants/{slug}/menu` | Full menu JSON for a restaurant |

## Project Structure

```
TiranaPoint/
├── index.php              # Main router
├── .htaccess              # Apache URL rewriting
├── core/
│   ├── DB.php             # PDO SQLite database class
│   ├── Auth.php           # Session-based authentication
│   └── helpers.php        # Utility functions
├── templates/
│   ├── splash.php         # Language selection page
│   ├── menu.php           # Menu page
│   ├── landing.php        # Home/landing page
│   └── 404.php
├── admin/                 # Admin CMS pages
│   ├── index.php          # Login
│   ├── dashboard.php
│   ├── restaurants.php
│   ├── menus.php
│   ├── items.php
│   ├── media.php
│   ├── ads.php
│   └── users.php
├── api/
│   └── index.php          # REST API endpoints
├── database/
│   ├── schema.sql         # Database schema
│   └── seed.php           # Demo data seeder
└── assets/
    ├── css/
    │   ├── admin.css
    │   └── front.css
    ├── js/
    │   ├── admin.js
    │   └── menu.js
    └── images/
        ├── tirana-logo.svg        # Square logo (login)
        ├── tirana-logo-wide.svg   # Horizontal logo (nav/sidebar)
        └── tirana-logo-white.svg  # White version (dark backgrounds)
```

## Upload Size Guidelines

| Type | Recommended Size | Max File Size | Format |
|------|-----------------|---------------|--------|
| Restaurant Logo | 400 × 400 px | 1 MB | PNG / WebP |
| Category Icon | 200 × 200 px | 500 KB | PNG (transparent) |
| Menu Item Image | 800 × 600 px (4:3) | 2 MB | JPG / WebP |
| Ad Banner | 1200 × 400 px | 2 MB | JPG / PNG |
| Ad Popup | 800 × 800 px | 2 MB | JPG / PNG |
| Splash Video | 1080p or 720p, ≤15s | 50 MB | MP4 (H.264) |
| General Media | Max 1920 px wide | 5 MB (img) / 100 MB (video) | JPG/PNG/MP4 |

## Requirements

- PHP 8.1+ with `pdo_sqlite` extension
- Apache with `mod_rewrite` (or use PHP built-in server)
- No Composer, no Node.js, no build tools

## License

MIT
