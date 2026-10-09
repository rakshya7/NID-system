# NID Service Portal — Case-Study Prototype
**Academic prototype. Not connected to the official DONIDCR/NID system. All data, tokens and OTPs are mock/demo.**

## Problem & solution
Citizens don't know which documents to bring. This prototype puts a context-aware document checklist (`doc_checklist()` in `includes/bootstrap.php`) into the home "What do I need?" tool, the enrollment steps, preview and success pages, plus a status timeline and EN/नेपाली switching.

## Setup (XAMPP)
1. Copy `nid-prototype/` into `C:\xampp\htdocs\`. Start Apache + MySQL.
2. In phpMyAdmin, import `database/nid_prototype.sql`.
3. Open http://localhost/nid-prototype/ (DB credentials: `includes/bootstrap.php`, default root / empty).

## Demo data
- Enrollment OTP: **123456** (any citizenship number/DOB/district).
- Status lookup: `NID-2026-000101` / `1995-04-12` (Appointment Scheduled); `-000102` / `1990-01-20` (Card Ready); `-000103` / `2000-07-08` (Action Required).

## Security in this build
PDO prepared statements, CSRF tokens, server-side validation, escaped output, generic DB errors, OTP never stored.

## Not yet built (planned)
Replacement/correction workflows, mock payment, eNID page, admin panel, family members, document upload, information center/FAQ search, dependent province/district dropdowns, more translated strings.

## v2 upgrade
- Existing database: run `database/upgrade_v2.sql` (fresh installs already include it).
- Enrollment is now an 11-step, data-driven wizard (`includes/fields.php`); every field is stored in MySQL (personal, citizenship, contact, addresses, family, education, applications, appointments).
- Government-style header with emblem **placeholder**: put an emblem image at `assets/emblem.png` to replace it (check licensing yourself).
- Dropdown option values (e.g. "Married") are English only; labels and messages are bilingual.

## v3 changes
- Run `database/upgrade_v3.sql` on an existing database (fresh installs include it). It adds mobile/OTP tables, replacement payments, and a UNIQUE key so one citizen can have only one enrollment.
- Enrollment now starts with citizenship number + DOB + mobile → duplicate check → OTP (hashed, 5-min expiry, 5 attempts, 30 s resend) → form. The wizard cannot be entered without a verified OTP. Demo OTP: `123456`. Existing demo citizen to see the duplicate block: `00-01-70-00001`.
- New `replacement.php` (NPR 500 mock payment; NIN format `123-456-789-0`), combined `information.php`. eNID and Correction removed.
- Not built: citizen dashboard, admin panel, document upload.
