<div align="center">

<img src="https://via.placeholder.com/80x80/1c57b2/ffffff?text=HG" alt="HelpGuard Logo" width="80" height="80" style="border-radius: 16px;" />

# HelpGuard

### Community Safety Network

**A real-time, crowd-sourced safety reporting platform that empowers communities to share, monitor, and respond to local incidents.**

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479A1?logo=mysql&logoColor=white)](https://mysql.com)
[![Leaflet](https://img.shields.io/badge/Leaflet.js-1.9.4-199900?logo=leaflet&logoColor=white)](https://leafletjs.com)
[![OpenStreetMap](https://img.shields.io/badge/OpenStreetMap-Nominatim-7EBC6F?logo=openstreetmap&logoColor=white)](https://nominatim.org)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

[Live Demo](#) · [Report a Bug](issues) · [Request a Feature](issues)

---

<!-- DEMO PLACEHOLDER -->
> 📸 **Screenshot – Landing Page**
> 
> ![HelpGuard Landing Page](.github/screenshots/landing-page.png)
> *Replace with actual screenshot of the hero/landing page*

</div>

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Screenshots](#-screenshots)
- [Tech Stack](#-tech-stack)
- [Database Schema](#-database-schema)
- [Project Structure](#-project-structure)
- [Getting Started](#-getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
  - [Configuration](#configuration)
- [Usage Guide](#-usage-guide)
  - [User Workflow](#user-workflow)
  - [Admin Workflow](#admin-workflow)
- [API Reference](#-api-reference)
- [Security Considerations](#-security-considerations)
- [Roadmap](#-roadmap)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🌐 Overview

HelpGuard is a **community-driven safety reporting web application** built with PHP and MySQL. It enables citizens to post geo-tagged incident reports — from crime sightings and flooding to road accidents and health hazards — making that information immediately visible to neighbors and first responders on an interactive map.

Each report is tagged with a severity status, an incident category, a GPS-pinned location, and an estimated affected radius. Community members can upvote or downvote reports to signal credibility, while administrators have a dedicated control panel for moderation, user management, and audit logging.

**Key highlights:**
- 🗺️ Real-time interactive map powered by Leaflet.js + OpenStreetMap
- 📍 GPS auto-detection and reverse geocoding via Nominatim
- 🔐 Role-based access control (User / Admin)
- 🌙 Dark mode support across the entire dashboard
- 📊 Live statistics and activity feed

---

## ✨ Features

### For Community Members
| Feature | Description |
|---|---|
| **Incident Reporting** | Submit reports with title, description, category, status level, and a GPS-pinned location |
| **Interactive Map** | Browse all active reports on a Leaflet map with colored circle overlays indicating affected radius and severity |
| **Community Voting** | Upvote or downvote reports to surface accurate information |
| **Feed & Filters** | Filter reports by status (`Dangerous`, `Caution`, `Safe`), category, keyword, or your own submissions |
| **User Profile** | Customizable avatar color, saved GPS home location, and activity history |
| **Dark Mode** | Full dark/light theme toggle persisted across sessions |

### For Administrators
| Feature | Description |
|---|---|
| **Admin Dashboard** | Overview statistics: total users, active reports, archived reports, danger count |
| **Report Moderation** | View, archive, or delete any community report |
| **User Management** | Browse registered users, view account details, manage roles |
| **Login Audit Log** | Per-request log of login attempts including IP address, device, and success/failure status |
| **Secure Access** | Admin panel protected by role-based session checks |

### Platform-Level
- PHP session-based authentication with `password_hash` (bcrypt, cost 12)
- CORS-safe reverse geocoding via a server-side Nominatim proxy
- Automatic database schema migration scripts for existing installs
- Mobile-responsive UI with animated transitions

---

## 📸 Screenshots

> 💡 *Replace each placeholder image below with actual screenshots from your deployment.*

### Landing Page

<!-- DEMO PLACEHOLDER -->
![Landing Page](.github/screenshots/01-landing.png)
*Hero section with animated gradient background, feature cards, and call-to-action buttons.*

---

### Sign Up & Login

<!-- DEMO PLACEHOLDER -->
![Sign Up](.github/screenshots/02-signup.png)
*Split-panel signup form with inline validation and password strength indicator.*

<!-- DEMO PLACEHOLDER -->
![Login](.github/screenshots/03-login.png)
*Login page with toggle between User and Admin modes.*

---

### User Dashboard — Feed View

<!-- DEMO PLACEHOLDER -->
![Dashboard Feed](.github/screenshots/04-dashboard-feed.png)
*Live incident feed with status badges, category icons, vote counters, and filter bar.*

---

### User Dashboard — Map View

<!-- DEMO PLACEHOLDER -->
![Dashboard Map](.github/screenshots/05-dashboard-map.png)
*Interactive Leaflet map with color-coded circle overlays representing each incident and its affected radius.*

---

### Post a Report

<!-- DEMO PLACEHOLDER -->
![Post Report Modal](.github/screenshots/06-post-report.png)
*Report submission form with GPS auto-detect, manual location input, category selection, and status picker.*

---

### User Profile

<!-- DEMO PLACEHOLDER -->
![User Profile](.github/screenshots/07-profile.png)
*Profile panel with avatar color picker, saved home GPS coordinates, and account info.*

---

### Dark Mode

<!-- DEMO PLACEHOLDER -->
![Dark Mode](.github/screenshots/08-dark-mode.png)
*Full dark mode across the dashboard, feed cards, map overlay, and all modals.*

---

### Admin Dashboard

<!-- DEMO PLACEHOLDER -->
![Admin Dashboard](.github/screenshots/09-admin-dashboard.png)
*Administrator overview with stats cards, report management table, and user list.*

---

### Admin — Login Audit Log

<!-- DEMO PLACEHOLDER -->
![Login Audit Log](.github/screenshots/10-admin-audit-log.png)
*Per-request login log with timestamps, IP addresses, and device information.*

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | PHP 8.0+ (procedural + MySQLi) |
| **Database** | MySQL 8.0 (InnoDB, utf8mb4) |
| **Frontend** | Vanilla HTML5, CSS3, JavaScript (ES6+) |
| **Map Engine** | [Leaflet.js 1.9.4](https://leafletjs.com) |
| **Tile Provider** | OpenStreetMap |
| **Geocoding** | [Nominatim](https://nominatim.org) (server-proxied) |
| **Icons** | [Font Awesome 6.5](https://fontawesome.com) |
| **Typography** | Google Fonts — Poppins |
| **Session Auth** | PHP native sessions + bcrypt (`password_hash`) |
| **Server** | Apache / Nginx + PHP-FPM |

---

## 🗄 Database Schema

HelpGuard uses four core tables:

```
users
├── id             INT (PK, AI)
├── first_name     VARCHAR(100)
├── last_name      VARCHAR(100)
├── email          VARCHAR(191) UNIQUE
├── password       VARCHAR(255)      -- bcrypt hash
├── role           ENUM('user','admin')
├── avatar_color   VARCHAR(20)       -- added via migration
├── gps_lat        DECIMAL(10,7)     -- added via migration
├── gps_lng        DECIMAL(10,7)     -- added via migration
└── created_at     TIMESTAMP

reports
├── id             INT (PK, AI)
├── user_id        INT (FK → users.id, CASCADE)
├── title          VARCHAR(255)
├── description    TEXT
├── location_name  VARCHAR(255)
├── barangay       VARCHAR(150)
├── city           VARCHAR(150)
├── province       VARCHAR(150)
├── latitude       DECIMAL(10,7)     -- GPS pin
├── longitude      DECIMAL(10,7)     -- GPS pin
├── radius_m       INT DEFAULT 200   -- affected area
├── status         ENUM('dangerous','caution','safe')
├── category       ENUM('crime','accident','flooding','fire',
│                       'health','infrastructure','other')
├── upvotes        INT DEFAULT 0
├── downvotes      INT DEFAULT 0
├── is_archived    TINYINT(1) DEFAULT 0
├── created_at     TIMESTAMP
└── updated_at     TIMESTAMP

report_votes
├── id             INT (PK, AI)
├── report_id      INT (FK → reports.id, CASCADE)
├── user_id        INT (FK → users.id, CASCADE)
├── vote           ENUM('up','down')
└── created_at     TIMESTAMP
      UNIQUE KEY (report_id, user_id)

login_logs
├── id             INT (PK, AI)
├── user_id        INT (nullable)
├── email          VARCHAR(191)
├── ip_address     VARCHAR(100)
├── device         TEXT
├── status         ENUM('Success','Failed')
└── created_at     TIMESTAMP
```

---

## 📁 Project Structure

```
helpguard/
│
├── index.php               # Public landing page (hero, features, stats)
├── login.php               # Login page — user & admin mode toggle
├── signup.php              # User registration
├── logout.php              # Session destroy + redirect
│
├── dashboard.php           # Authenticated user dashboard (feed + map)
├── admin.php               # Admin control panel (reports, users, logs)
│
├── api.php                 # JSON API endpoint (reports CRUD, voting, profile)
├── geocode_proxy.php       # Server-side Nominatim reverse-geocode proxy
├── db_connect.php          # MySQLi connection (credentials config here)
│
├── helpguard.sql           # 🆕 Full schema — use for fresh installs
├── map_migration.sql       # 📦 Adds lat/lng/radius columns to existing installs
└── profile_migration.sql   # 📦 Adds avatar_color, gps_lat, gps_lng columns
```

> **Migration note:** If you have an existing HelpGuard database, run `map_migration.sql` and `profile_migration.sql` instead of `helpguard.sql` to avoid dropping your data.

---

## 🚀 Getting Started

### Prerequisites

- **PHP** 8.0 or higher
- **MySQL** 8.0 (or MariaDB 10.4+)
- **Apache** or **Nginx** web server with PHP support
- **Composer** is not required — no external PHP dependencies
- A local development stack such as [XAMPP](https://apachefriends.org), [Laragon](https://laragon.org), or [MAMP](https://mamp.info)

---

### Installation

**1. Clone the repository**

```bash
git clone https://github.com/your-username/helpguard.git
cd helpguard
```

**2. Move files to your web server root**

```bash
# For XAMPP
cp -r . /xampp/htdocs/helpguard/

# For Linux/Apache
cp -r . /var/www/html/helpguard/
```

**3. Create the database**

Open phpMyAdmin (or MySQL CLI) and import the schema:

```bash
# Using MySQL CLI
mysql -u root -p -e "CREATE DATABASE helpguard CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
mysql -u root -p helpguard < helpguard.sql
```

Or paste the contents of `helpguard.sql` directly into the phpMyAdmin SQL editor.

**4. Configure the database connection**

Open `db_connect.php` and update the credentials:

```php
$servername = "localhost";
$db_user    = "root";        // Your MySQL username
$db_pass    = "";            // Your MySQL password
$dbname     = "helpguard";
```

**5. Start your server and visit the app**

```
http://localhost/helpguard/
```

---

### Configuration

#### Database Credentials
Edit `db_connect.php` — this is the single source of truth for database connection.

#### Admin Credentials
By default, admin login uses hardcoded credentials defined in `login.php`:

```
Username: admin
Password: admin
```

> ⚠️ **Change these immediately before any public deployment.** See [Security Considerations](#-security-considerations) for details.

#### Geocoding
Reverse geocoding is routed through `geocode_proxy.php`, which calls the Nominatim public API server-side. No API key is required. For high-traffic production use, consider hosting your own Nominatim instance or switching to a commercial geocoding provider.

---

## 📖 Usage Guide

### User Workflow

1. **Register** at `/signup.php` — provide your name, email, and a password (min. 8 characters).
2. **Log in** at `/login.php` using User mode.
3. From the **Dashboard**, switch between the **Feed** (list view) and **Map** (geographic view) using the sidebar.
4. Click **Post Report** to submit a new incident:
   - Enter a title and description
   - Select a **category** (Crime, Accident, Flooding, Fire, Health, Infrastructure, Other)
   - Set a **status** level (Dangerous, Caution, Safe)
   - Use **Detect My Location** to auto-fill GPS coordinates, or type a location manually
   - Adjust the **affected radius** slider
5. **Vote** on reports using the thumbs up/down buttons to help the community assess credibility.
6. Use the **filter bar** to narrow reports by keyword, status, category, or view only your own submissions.
7. Visit your **Profile** panel to change your avatar color or save your home GPS location for quick access.
8. Toggle **Dark Mode** using the moon icon in the top bar.

---

### Admin Workflow

1. Log in at `/login.php` — switch to **Admin Mode** and use the admin credentials.
2. The **Admin Dashboard** shows four summary stats: total users, active reports, archived reports, and dangerous reports.
3. Navigate via the sidebar to:
   - **Reports** — view all active reports, archive or permanently delete individual ones
   - **Users** — browse all registered users with join date and role
   - **Login Logs** — review authentication history including IP, device, and success/failure status
4. Use the top-right **logout** button to end the admin session securely.

---

## 🔌 API Reference

All API calls go to `api.php` and require an active authenticated session. Responses are JSON.

### `GET api.php?action=get_reports`
Returns all non-archived reports ordered by newest first.

**Response:**
```json
{
  "status": "success",
  "reports": [
    {
      "id": 42,
      "title": "Flooded road near barangay hall",
      "description": "...",
      "location_name": "Rizal Street",
      "barangay": "San Antonio",
      "city": "Quezon City",
      "province": "Metro Manila",
      "latitude": 14.6760,
      "longitude": 121.0437,
      "radius_m": 300,
      "status": "dangerous",
      "category": "flooding",
      "upvotes": 12,
      "downvotes": 1,
      "poster_name": "Juan dela Cruz",
      "user_vote": "up",
      "created_at": "2025-07-01 14:22:05"
    }
  ]
}
```

---

### `POST api.php?action=post_report`
Creates a new incident report.

**Form fields:**

| Field | Type | Required | Description |
|---|---|---|---|
| `title` | string | ✅ | Short incident title |
| `description` | string | ✅ | Detailed description |
| `location_name` | string | ✅ | Freeform location label |
| `barangay` | string | | Barangay / neighborhood |
| `city` | string | ✅ | City |
| `province` | string | | Province |
| `latitude` | decimal | | GPS latitude |
| `longitude` | decimal | | GPS longitude |
| `radius_m` | integer | | Affected radius in meters (default: 200) |
| `status` | enum | ✅ | `dangerous` / `caution` / `safe` |
| `category` | enum | ✅ | `crime` / `accident` / `flooding` / `fire` / `health` / `infrastructure` / `other` |

---

### `POST api.php?action=vote`

| Field | Type | Description |
|---|---|---|
| `report_id` | integer | Target report ID |
| `vote` | string | `up` or `down` |

---

### `GET api.php?action=get_profile`
Returns the current user's profile details including `avatar_color`, saved `gps_lat`, and `gps_lng`.

---

### `POST api.php?action=update_profile`

| Field | Type | Description |
|---|---|---|
| `avatar_color` | string | CSS hex color e.g. `#3a8dff` |
| `gps_lat` | decimal | Home latitude |
| `gps_lng` | decimal | Home longitude |

---

## 🔒 Security Considerations

> These are **strongly recommended** before deploying HelpGuard to a public-facing server.

**1. Change the hardcoded admin credentials**

In `login.php`, replace the hardcoded admin check with a proper database-backed admin account:

```php
// Remove or replace this block in login.php:
if ($mode === 'admin' && $email === 'admin' && $password === 'admin') { ... }
```

**2. Move `db_connect.php` outside the web root**

```php
// In each PHP file, reference it as:
require '/var/secrets/db_connect.php';
```

**3. Use environment variables for credentials**

Store sensitive config in `.env` and load with `getenv()` or a library like [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv).

**4. Enable HTTPS**

All sessions, login forms, and API calls should be served over HTTPS. Configure an SSL certificate (free via Let's Encrypt).

**5. Add CSRF protection**

Protect all POST forms and API mutating endpoints with a per-session CSRF token.

**6. Set a production `php.ini`**

```ini
display_errors = Off
log_errors = On
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Strict
```

**7. Rate-limit the Nominatim proxy**

The `geocode_proxy.php` is authenticated (session required), but consider adding per-user request throttling to prevent geocoding abuse.

---

## 🗺 Roadmap

- [ ] Email verification on signup
- [ ] Password reset via email
- [ ] Push notifications for nearby high-severity reports
- [ ] Image attachments on reports
- [ ] Report comments / discussion thread
- [ ] Public read-only embeddable map widget
- [ ] REST API with token authentication for mobile clients
- [ ] PWA manifest for installable mobile experience
- [ ] Multi-language / localization support
- [ ] Export reports to CSV/PDF (admin)

---

## 🤝 Contributing

Contributions are welcome! Here's how to get started:

1. **Fork** the repository
2. **Create** a feature branch: `git checkout -b feature/your-feature-name`
3. **Commit** your changes: `git commit -m "feat: add your feature"`
4. **Push** to your branch: `git push origin feature/your-feature-name`
5. **Open** a Pull Request and describe what you changed and why

Please make sure your code follows the existing style, and test locally before submitting. Bug reports and feature requests can be submitted via [GitHub Issues](issues).

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).

---

<div align="center">

Built with ❤️ for safer communities.

**[Back to top ↑](#helpguard)**

</div>
