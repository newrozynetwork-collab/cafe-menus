-- ============================================================
-- TiranaMenu Clone - Complete Database Schema (SQLite)
-- ============================================================

PRAGMA journal_mode = WAL;
PRAGMA foreign_keys = ON;

-- ── Users & Roles ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    name         TEXT    NOT NULL,
    email        TEXT    UNIQUE NOT NULL,
    password     TEXT    NOT NULL,                        -- bcrypt hash
    role         TEXT    NOT NULL DEFAULT 'editor',       -- superadmin | admin | editor
    restaurant_id INTEGER REFERENCES restaurants(id) ON DELETE SET NULL,
    is_active    INTEGER NOT NULL DEFAULT 1,
    last_login   DATETIME,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Cities / Regions ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cities (
    id    INTEGER PRIMARY KEY AUTOINCREMENT,
    name  TEXT NOT NULL,
    slug  TEXT UNIQUE NOT NULL
);

-- ── Restaurants ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS restaurants (
    id                   INTEGER PRIMARY KEY AUTOINCREMENT,
    city_id              INTEGER REFERENCES cities(id) ON DELETE SET NULL,
    name                 TEXT NOT NULL,
    slug                 TEXT UNIQUE NOT NULL,             -- URL slug: /vogue, /almajlees
    logo                 TEXT,                             -- path relative to uploads/
    theme_color          TEXT NOT NULL DEFAULT '#910000',  -- hex brand color
    body_bg              TEXT NOT NULL DEFAULT '#141414',  -- page background
    font                 TEXT NOT NULL DEFAULT 'Poppins',  -- Poppins | rabar | etc.
    default_lang         TEXT NOT NULL DEFAULT 'en',       -- en | ar | ku
    has_sections         INTEGER NOT NULL DEFAULT 0,       -- Food/Drinks/Hookah tabs
    -- Social links
    social_facebook      TEXT,
    social_instagram     TEXT,
    social_phone         TEXT,
    social_location      TEXT,
    -- Splash video
    has_splash_video     INTEGER NOT NULL DEFAULT 0,
    splash_video_url     TEXT,
    splash_video_thumb   TEXT,
    -- Advertisement
    has_ad               INTEGER NOT NULL DEFAULT 0,
    -- Meta
    is_active            INTEGER NOT NULL DEFAULT 1,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Menu Sections (Food / Drinks / Hookah) ───────────────────
CREATE TABLE IF NOT EXISTS sections (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    name_en       TEXT NOT NULL,
    name_ar       TEXT,
    name_ku       TEXT,
    sort_order    INTEGER NOT NULL DEFAULT 0,
    is_active     INTEGER NOT NULL DEFAULT 1
);

-- ── Categories ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    section_id    INTEGER REFERENCES sections(id) ON DELETE SET NULL,
    name_en       TEXT NOT NULL,
    name_ar       TEXT,
    name_ku       TEXT,
    icon          TEXT,                                    -- path to icon image
    sort_order    INTEGER NOT NULL DEFAULT 0,
    is_active     INTEGER NOT NULL DEFAULT 1
);

-- ── Items ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS items (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id  INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    category_id    INTEGER NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    name_en        TEXT NOT NULL,
    name_ar        TEXT,
    name_ku        TEXT,
    description_en TEXT,
    description_ar TEXT,
    description_ku TEXT,
    price          REAL    NOT NULL DEFAULT 0,
    image          TEXT,                                   -- path to image
    is_active      INTEGER NOT NULL DEFAULT 1,
    sort_order     INTEGER NOT NULL DEFAULT 0,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Item Variants (Small / Medium / Large, etc.) ─────────────
CREATE TABLE IF NOT EXISTS variants (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    item_id    INTEGER NOT NULL REFERENCES items(id) ON DELETE CASCADE,
    name_en    TEXT NOT NULL,
    name_ar    TEXT,
    name_ku    TEXT,
    price      REAL NOT NULL DEFAULT 0,
    sort_order INTEGER NOT NULL DEFAULT 0
);

-- ── Media Library ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS media (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id  INTEGER REFERENCES restaurants(id) ON DELETE SET NULL,
    filename       TEXT NOT NULL,
    original_name  TEXT,
    mime_type      TEXT,
    file_size      INTEGER,
    media_type     TEXT NOT NULL DEFAULT 'image',          -- image | video | ad
    path           TEXT NOT NULL,                          -- server path
    url            TEXT NOT NULL,                          -- public URL
    width          INTEGER,
    height         INTEGER,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Advertisements ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ads (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    restaurant_id INTEGER NOT NULL REFERENCES restaurants(id) ON DELETE CASCADE,
    title         TEXT,
    image         TEXT,                                    -- path to ad image
    video         TEXT,                                    -- path to ad video
    link          TEXT,                                    -- clickthrough URL
    ad_type       TEXT NOT NULL DEFAULT 'banner',          -- banner | video | popup
    position      TEXT NOT NULL DEFAULT 'pre-menu',        -- pre-menu | in-menu | splash
    starts_at     DATETIME,
    ends_at       DATETIME,
    is_active     INTEGER NOT NULL DEFAULT 1,
    sort_order    INTEGER NOT NULL DEFAULT 0,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Sessions ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sessions (
    id         TEXT PRIMARY KEY,                           -- UUID token
    user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    ip         TEXT,
    user_agent TEXT,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Audit Log ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS audit_log (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action     TEXT NOT NULL,                              -- CREATE | UPDATE | DELETE | LOGIN
    entity     TEXT,                                       -- restaurants | items | etc.
    entity_id  INTEGER,
    details    TEXT,                                       -- JSON blob
    ip         TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ── Indexes ──────────────────────────────────────────────────
CREATE INDEX IF NOT EXISTS idx_restaurants_slug    ON restaurants(slug);
CREATE INDEX IF NOT EXISTS idx_categories_rest     ON categories(restaurant_id, sort_order);
CREATE INDEX IF NOT EXISTS idx_categories_section  ON categories(section_id);
CREATE INDEX IF NOT EXISTS idx_items_category      ON items(category_id, sort_order);
CREATE INDEX IF NOT EXISTS idx_items_rest          ON items(restaurant_id);
CREATE INDEX IF NOT EXISTS idx_media_rest          ON media(restaurant_id);
CREATE INDEX IF NOT EXISTS idx_ads_rest            ON ads(restaurant_id, is_active);
CREATE INDEX IF NOT EXISTS idx_audit_user          ON audit_log(user_id);
CREATE INDEX IF NOT EXISTS idx_sessions_user       ON sessions(user_id);
