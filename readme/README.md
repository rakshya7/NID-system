# NID Service Portal: Case-Study Prototype

An academic prototype of a National ID (NID) service website for Nepal, built with plain PHP, MySQL and a little JavaScript. It is **not** connected to the official DONIDCR/NID system. Every token, OTP, payment and office in it is mock data.

## The idea behind it

People usually don't fail at NID enrollment because the form is hard. They fail because they show up at the office without the right documents. So the whole site is built around one idea:

> Tell the citizen what to bring, at every point where they might wonder.

That idea lives in one function, `doc_checklist()`, which is reused on the home page ("What do I need?"), the enrollment steps, the review page, the success page, the replacement receipt and the status page. Write the checklist once, show it everywhere.

Around that sit four citizen services: **enroll**, **check status**, **replace a card**, and **read information/FAQ**. The site works in English and Nepali.

## How the site is put together

Think of it as a small government office:

| Part | Office analogy | Files |
|---|---|---|
| Shared setup (database, language, security helpers) | The building's electricity and plumbing. Every room uses it. | `includes/bootstrap.php` |
| Header and footer | The signboard and the exit door. Every page is wrapped in them. | `includes/header.php`, `includes/footer.php` |
| Field definitions | A blueprint the form builder reads. Change the blueprint and the form changes. | `includes/fields.php` |
| Pages | The service counters. | `index.php`, `enroll.php`, `success.php`, `status.php`, `replacement.php`, `information.php` |
| Language files | A phrasebook with two editions. | `lang/en.php`, `lang/ne.php` |
| Look and feel | Paint and signage. | `assets/style.css` |
| Database | The filing room. | `database/nid_prototype.sql`, `upgrade_v2.sql`, `upgrade_v3.sql` |

### Folder layout

```
nid-prototype/
├── index.php            Home page + "What do I need?" tool
├── enroll.php           OTP gate + 11-step enrollment wizard
├── success.php          Confirmation after enrollment
├── status.php           Status lookup + progress timeline
├── replacement.php      Card replacement + mock payment
├── information.php      Help, requirements, FAQ, contact
├── includes/
│   ├── bootstrap.php    Session, DB, language, helpers, checklist
│   ├── header.php       Top bar, logo, navigation, prototype banner
│   ├── footer.php       Footer + small script for show/hide fields
│   └── fields.php       Form field definitions and form renderer
├── lang/
│   ├── en.php           English text
│   └── ne.php           Nepali text
├── assets/
│   ├── style.css
│   └── govlogo.png      Optional emblem (see Notes)
└── database/
    ├── nid_prototype.sql
    ├── upgrade_v2.sql
    └── upgrade_v3.sql
```

## What happens on each page

Every page starts with `require 'includes/header.php'`, which loads `bootstrap.php`. That one include gives the page a database connection (`$pdo`), the language (`$L`, `t()`), and the helpers below.

### Shared helpers (`bootstrap.php`)

- **`t('key')`** looks up text in the current language file, falls back to English, then to the key itself. Output is escaped.
- **`e($value)`** escapes anything printed into HTML.
- **`csrf()` / `check_csrf()`** put a hidden token in every form and reject any POST that doesn't carry it.
- **`valid_date()`** checks that a date is real (and optionally not in the past).
- **`ins($table, $data)`** inserts an associative array into a table and reports which table and columns failed if something goes wrong.
- **`pick($data, 'perm_')`** pulls out all keys starting with a prefix and strips it. So `perm_district` becomes `district`, ready to insert.
- **`doc_checklist($type)`** builds the "Bring these documents" box for `new_enrollment` or `replacement`.

### Home (`index.php`)

Hero banner, four service cards, and the "What do I need?" dropdown. Picking *New NID* or *Replace my NID* reloads the page with `?w=...` and shows the matching checklist.

### Enrollment (`enroll.php` + `fields.php`)

This is the biggest part. It works like a **paper form kept in a folder at the front desk**: everything the citizen types is held in the session (`$_SESSION['enr']`) until the final step, when it is written to the database in one go.

1. **Gate (internal step 1).** The citizen enters citizenship number + mobile. The page first checks whether an active application already exists for that citizenship number (duplicate block), then issues a demo OTP. The OTP is stored hashed with a 5-minute expiry and a 5-attempt limit. Demo code: `123456`.
2. **Form sections (internal steps 2–8).** Personal, Birth & Demographic, Citizenship, Contact & Address, Family, Education/Occupation, Identification. Each section is described in `fields.php` as a list of fields; `enroll.php` loops over that list, draws each input with `fld()`, and validates it by type (text, mobile, date, email, number, select, radio).
3. **Documents (step 9).** Shows the checklist and asks the citizen to confirm they've read it.
4. **Appointment (step 10).** Choose an available office, a future date and a time slot.
5. **Review (step 11).** Everything is shown back, each block with an *Edit* button that jumps to that section and returns to review.
6. **Submit.** In one database transaction it creates the citizen record, personal, citizenship, contact, education, address and family rows, the application (`NID-2026-000123` style number), the appointment with a mock token, and the status history. Then it redirects to `success.php`.

Because the *citizen is shown 10 steps* but the code counts 11 (the OTP gate is step 1), the progress bar subtracts one.

#### How `fields.php` works

Each field is a small array:

```
[ field_name, type, required, options_or_label, hint_or_condition ]
```

Types: `t` text, `m` mobile, `d` date, `e` email, `n` number, `s` select, `r` radio.

A field can carry a condition, for example the spouse fields only show when `pd_marital = Married`, and the current-address fields only show when `same_addr = No`. `vis()` decides whether a field is visible; the same check is used when drawing, validating and reviewing, so a hidden field is never required and never shown on the review page.

Field names use prefixes (`pd_` personal, `cz_` citizenship, `ct_` contact, `perm_`/`curr_` address, `fa_`/`mo_`/`sp_`/`gu_` family, `ed_` education, `id_` identification). The prefix tells `pick()` which table the value belongs to.

### Success (`success.php`)

Reads the confirmation from the session, shows application number, office, date, time and token, plus the checklist. Without a completed enrollment it sends the visitor back to `enroll.php`.

### Status (`status.php`)

The citizen enters application number + date of birth. The page shows a six-stage timeline:

`Submitted → Verified → Appointment Scheduled → Biometric Enrollment → NID Processing → Card Ready`

Each stage is marked done, current or pending. If the application is `action_required`, a red notice shows the office's remarks.

### Replacement (`replacement.php`)

A four-stage flow driven by `?stage=`: **form → pay → processing → done**.

- The form takes NIN (`123-456-789-0`), mobile, name in English and Nepali, an office, and a CAPTCHA.
- The pay stage shows a summary (NPR 500) and a choice of Khalti, eSewa or Other. No real payment happens.
- Processing is a 3-second page refresh; the done stage prints a mock transaction ID, a token and an appointment 14 days out, plus the replacement checklist.

### Information (`information.php`)

A static help page with a jump menu, document guidance, the process, status meanings, FAQ accordions and a contact block.

## Language switching

`?lang=ne` or `?lang=en` stores the choice in the session. `bootstrap.php` loads `lang/en.php` as the base and `lang/{choice}.php` on top, so any missing Nepali string quietly falls back to English. Dropdown values such as "Married" stay in English; labels and messages are translated.

## Database

MySQL database `nid_prototype`, utf8mb4.

| Table | Holds |
|---|---|
| `offices` | Three mock offices (Kathmandu, Lalitpur, Bhaktapur) |
| `users` | One row per citizen (citizenship number, date of birth, mobile) |
| `applications` | One application per citizen (unique key on `user_id`), status, remarks |
| `personal_details`, `citizenship_details`, `contact_details`, `addresses`, `family_members`, `education_occupation` | The enrollment form, split by topic |
| `appointments` | Office, date, time, token |
| `status_history` | Every status an application has passed through |
| `otp_verifications` | OTP records (added in v3) |
| `replacement_requests` | Replacement payments and appointments |

`nid_prototype.sql` already contains the v2 and v3 changes, so a fresh install needs only that file. The `upgrade_*.sql` files are for databases created before those versions.

## Security choices

- Prepared statements (PDO) everywhere, with emulation turned off.
- CSRF token on every POST form.
- All output escaped through `e()` / `t()`.
- Server-side validation; HTML `required` is only a convenience.
- OTP stored as a hash, with expiry and attempt limit.
- The wizard cannot be entered or continued without a verified OTP, and changing the verified mobile number forces re-verification.
- Generic error messages for database connection failures.

## Setup (XAMPP)

1. Copy the project folder into `C:\xampp\htdocs\` and start Apache and MySQL.
2. In phpMyAdmin, import `database/nid_prototype.sql`.
3. Open `http://localhost/nid-prototype/`.
4. Database credentials are in `includes/bootstrap.php` (default: user `root`, empty password).

## Demo data

| What | Value |
|---|---|
| Enrollment OTP | `123456` |
| Status lookup, Appointment Scheduled | `NID-2026-000101` / `1995-04-12` |
| Status lookup, Card Ready | `NID-2026-000102` / `1990-01-20` |
| Status lookup, Action Required | `NID-2026-000103` / `2000-07-08` |
| Citizenship number that triggers the duplicate block | `00-01-70-00001` |
| Replacement payment | Any valid NIN format, any method, CAPTCHA shown on screen |

## Notes

- **Emblem:** drop an image at `assets/govlogo.png` to replace the placeholder in the header. Check the licensing of any official emblem yourself.
- **Not built:** citizen dashboard, admin panel, mock document upload, correction workflow, dependent province/district dropdowns.

## Known gaps to fix before a demo

These came from reading the code, so test each one in your own setup.

1. **`enroll.php` is missing `existing-application.php`.** The duplicate check redirects to it, but that page isn't in the file set.
2. **Typo at final submit.** `$$pdo->commit();` should be `$pdo->commit();`. As written, the submission fails and rolls back.
3. **Form field names don't match database column names.** `pick()` passes the field names through, so some inserts will fail unless they line up. Examples: `cz_type`, `cz_issuing_office`, `cz_issue_date`, `cz_issuing_authority` vs the table's `cit_type`, `cit_office`, `cit_issue_date`, `cit_authority`; `ct_mobile_verified` has no column in `contact_details`; `perm_house`/`curr_house` vs `house_no`; `ed_organization`, `ed_designation`, `ed_other_occupation` vs `employer`, `employment`. Either rename the fields or map them before inserting.
4. **Duplicate check uses a status that doesn't exist.** `verification_pending` is not in the `applications.status` enum.
5. **`information.php` mentions uploading a citizenship image, but no upload exists.** The rest of the prototype says upload is not built. Either reword that page or add the feature.
6. **Header has a stray `</a>`** after the Information link, and an empty link in the top bar.
7. **`en.php` defines some keys more than once** (for example `repl`, `info`, `status`, `mobile`). PHP keeps the last one, so it works, but it's easy to edit the wrong copy.
8. **Enrollment page text is hard-coded in English** and doesn't go through `t()`, so it won't change when the language is switched.
