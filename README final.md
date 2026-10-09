# NID Service Portal: Case-Study Prototype

A working prototype of a National ID (NID) service website for Nepal, built for a BSc CSIT E-Governance case study. It is written in plain PHP (no framework), MySQL and a little JavaScript, and it runs on XAMPP.

> **Academic prototype. Not connected to the official DONIDCR/NID system.** All data, tokens, OTPs, offices and payments are mock/demo.

---

## Table of contents

1. [The idea behind the project](#1-the-idea-behind-the-project)
2. [What the site can do](#2-what-the-site-can-do)
3. [Technology](#3-technology)
4. [Project structure and file inventory](#4-project-structure-and-file-inventory)
5. [How a request flows through the code](#5-how-a-request-flows-through-the-code)
6. [Shared helpers (`bootstrap.php`)](#6-shared-helpers-bootstrapphp)
7. [Page-by-page reference](#7-page-by-page-reference)
8. [The enrollment wizard in detail](#8-the-enrollment-wizard-in-detail)
9. [Database](#9-database)
10. [Languages (English / Nepali)](#10-languages-english--nepali)
11. [Look and feel](#11-look-and-feel)
12. [Security](#12-security)
13. [Setup on XAMPP](#13-setup-on-xampp)
14. [Demo data and test scenarios](#14-demo-data-and-test-scenarios)
15. [Troubleshooting](#15-troubleshooting)
16. [Version history](#16-version-history)
17. [Known gaps and future work](#17-known-gaps-and-future-work)

---

## 1. The idea behind the project

People rarely fail at NID enrollment because the form is hard. They fail because they arrive at the office without the right documents, or they don't know what happens next. The prototype is built around one idea:

> Tell the citizen what to bring, at every point where they might wonder, and always end with a token and an appointment they can take to the physical office.

The "what to bring" logic lives in one function, `doc_checklist()`. It is written once and reused on the home page, the enrollment steps, the review page, the success page, the replacement receipt and the status page.

The flow the prototype demonstrates:

```
Citizen opens site → checks "What do I need?" → starts enrollment
   → verifies citizenship number + mobile (OTP)
   → fills the form in 10 steps → picks an office, date and time
   → reviews and submits
   → receives Application Number + Token + Appointment details
   → visits the physical office with original documents
   → tracks progress on the Status page
```

---

## 2. What the site can do

| Service | What it does |
|---|---|
| **Home** | Hero banner, four service cards, and a "What do I need?" tool that shows the right document checklist |
| **New Enrollment** | OTP gate, then a 10-step form, appointment booking, review and submit. Produces an application number and token |
| **Check Status** | Look up an application by number + date of birth and see a six-stage progress timeline |
| **Card Replacement** | Fill in NIN and details, pick an office, make a mock NPR 500 payment, receive a transaction ID, token and appointment |
| **Information** | Help page with requirements, process, status meanings, FAQ and contact details |
| **Language switch** | English and Nepali (नेपाली) |

---

## 3. Technology

| Layer | Choice |
|---|---|
| Server language | PHP 7.4+ (tested on PHP 8.3) |
| Database | MySQL / MariaDB, `utf8mb4`, accessed through PDO |
| Front end | Server-rendered HTML, one stylesheet, a few lines of JavaScript |
| Local server | XAMPP (Apache + MySQL) |
| Frameworks / libraries | None |

---

## 4. Project structure and file inventory

```
nid-prototype/
├── index.php                  Home page + "What do I need?" tool
├── enroll.php                 OTP gate + 11-step enrollment wizard + final submit
├── existing-application.php   Shown when a citizen already has an application
├── success.php                Confirmation: application number, token, appointment
├── status.php                 Status lookup + progress timeline
├── replacement.php            Card replacement + mock payment
├── information.php            Help / requirements / FAQ / contact
├── session-test.php           Optional diagnostic page (delete when done)
├── includes/
│   ├── bootstrap.php          Session, database, language, helper functions
│   ├── header.php             Top bar, logo, navigation, prototype banner
│   ├── footer.php             Footer + small show/hide script
│   └── fields.php             Enrollment field definitions + form renderer
├── lang/
│   ├── en.php                 English text
│   └── ne.php                 Nepali text
├── assets/
│   ├── style.css              All styling
│   └── govlogo.png            Optional emblem image (not supplied)
└── database/
    ├── nid_prototype.sql      Full schema + seed data (includes v2 and v3 changes)
    ├── upgrade_v2.sql         Upgrade for databases created before v2
    └── upgrade_v3.sql         Upgrade for databases created before v3
```

### File-by-file summary

| File | Lines (approx.) | Purpose |
|---|---:|---|
| `index.php` | 10 | Home page. Hero, service cards, "What do I need?" dropdown that reloads with `?w=new_enrollment` or `?w=replacement` |
| `enroll.php` | 1,400 | The biggest file. Holds the wizard state machine (OTP and 10 form steps), the final database submit and all the HTML for each step |
| `existing-application.php` | 40 | Duplicate-enrollment notice with the existing application number, status and date |
| `success.php` | 7 | Reads `$_SESSION['done']`, shows application number, office, date, time and token, plus the checklist |
| `status.php` | 13 | Status lookup form and timeline |
| `replacement.php` | 41 | Four-stage replacement flow: form, pay, processing, done |
| `information.php` | 300 | Static help content with a jump menu, FAQ accordions and a contact block |
| `session-test.php` | 20 | Counts page visits to prove sessions work on your server |
| `includes/bootstrap.php` | 370 | Starts the session, connects to MySQL, loads the language, defines helpers (see section 6) |
| `includes/header.php` | 8 | Opens the HTML document: top bar, emblem, titles, navigation, prototype banner |
| `includes/footer.php` | 6 | Closes the page and holds a small script for conditional fields and dependent dropdowns |
| `includes/fields.php` | 1,500 | `$STEPS` (field definitions per step), `$FIELD_LABELS`, and the functions `lbl()`, `vis()`, `fld()` |
| `lang/en.php`, `lang/ne.php` | 47 (long lines) | Translation arrays, 183 keys each. Nepali covers every English key |
| `assets/style.css` | 117 | Styling for every page |
| `database/nid_prototype.sql` | 29 | Creates the database, all tables, and seed data |
| `database/upgrade_v2.sql`, `upgrade_v3.sql` | 9 / 6 | Incremental changes for older databases |

---

## 5. How a request flows through the code

Think of the site as a small government office. `bootstrap.php` is the building's electricity and plumbing, so every room uses it. `header.php` and `footer.php` are the signboard and the exit door. Each page is a service counter.

Every page starts the same way:

```
page.php
  └─ require 'includes/header.php'
        └─ require_once 'includes/bootstrap.php'
              ├─ session_start()
              ├─ connect to MySQL        → $pdo
              ├─ pick the language       → $L, $T (selected), $EN (English fallback)
              └─ define helpers          → t(), e(), csrf(), check_csrf(), valid_date(),
                                           ins(), pick(), doc_checklist()
        └─ prints <html>, top bar, logo, navigation, prototype banner, <main>
  └─ page-specific content
  └─ require 'includes/footer.php'      → closes </main>, footer text, script
```

`enroll.php` and `replacement.php` are special. They handle form submissions and redirects, so they load `bootstrap.php` first and `header.php` only when it is time to draw HTML. A page can only send a redirect before any HTML is printed.

### Session keys used across the site

| Key | Set by | Meaning |
|---|---|---|
| `lang` | `bootstrap.php` | `en` or `ne` |
| `c` | `csrf()` | CSRF token for forms |
| `enr` | `enroll.php` | The whole enrollment in progress (see section 8) |
| `existing_application` | `enroll.php` | Details shown on `existing-application.php` |
| `done` | `enroll.php` | Application number, token and appointment shown on `success.php` |
| `cap` | `replacement.php` | The CAPTCHA text |
| `rep` | `replacement.php` | Replacement form data waiting for payment |
| `rep_id` | `replacement.php` | ID of the paid replacement request |

---

## 6. Shared helpers (`bootstrap.php`)

| Helper | What it does |
|---|---|
| `t('key')` | Looks up text in the chosen language, falls back to English, then to the key. Output is HTML-escaped |
| `e($value)` | Escapes any value for safe printing in HTML |
| `csrf()` | Returns a hidden input holding the CSRF token. Put it in every POST form |
| `check_csrf()` | Stops the request with "Invalid request." if the token is missing or wrong |
| `valid_date($date, $future = false)` | True for a real `YYYY-MM-DD` date; with `$future = true` it must not be in the past |
| `ins($table, $data)` | Inserts an associative array into a table with a prepared statement and returns the new ID. On failure it reports the table, columns and database error |
| `pick($data, 'perm_')` | Returns all keys starting with the prefix, with the prefix removed (`perm_district` becomes `district`) |
| `doc_checklist($type)` | Builds the "Bring these documents" box for `new_enrollment` or `replacement` |

### How `doc_checklist()` is built

| Context | Required | Only if applicable |
|---|---|---|
| `new_enrollment` | `doc_citizenship` | `doc_migration`, `doc_marriage`, `doc_dob`, `doc_other` |
| `replacement` | none | `doc_card`, `doc_orig`, `doc_other` |

Each document is an expandable item. Its title comes from the language key (for example `doc_migration`) and its explanation from the same key plus `_x` (`doc_migration_x`).

---

## 7. Page-by-page reference

### Home: `index.php`
- Hero with two buttons: Start New Enrollment and Check Application Status.
- Four service cards: Enroll, Status, Replacement, Information.
- **What do I need?** is a dropdown that submits itself and reloads with `?w=...`, then prints the matching `doc_checklist()`.

### New Enrollment: `enroll.php`
See [section 8](#8-the-enrollment-wizard-in-detail).

### Existing application: `existing-application.php`
Shown when the citizenship number already has an application. It displays the application number, current status and submission date, with links to Check Status, "Use a different citizenship number" (`enroll.php?new=1`, which clears the in-progress enrollment) and Home. Without session data it sends the visitor back to `enroll.php`.

### Success: `success.php`
Reads `$_SESSION['done']`, written by `enroll.php` after a successful submit. It shows:
- Application Number (for example `NID-2026-000104`)
- Office, appointment date and time
- Token (for example `NID-KTM-00530`)
- The document checklist and links to Status and Home

If there is no `done` data it redirects to `enroll.php`. This is what the citizen takes to the physical office.

### Check Status: `status.php`
- Form: application number + date of birth (the date of birth must match the citizen record).
- Lookup joins `applications` with `users`.
- Shows a six-stage timeline, each marked ✓ done, ● current or ○ pending:

  `Submitted → Verified → Appointment Scheduled → Biometric Enrollment → NID Processing → Card Ready`

- If the status is `action_required`, a red notice shows the office's remarks and no stage is marked current.
- While the status is `submitted` or `appointment_scheduled`, the document checklist is shown again.

### Replacement: `replacement.php`
A four-stage flow driven by `?stage=`:

| Stage | What happens |
|---|---|
| `form` | NIN (`123-456-789-0`), mobile (97/98 + 8 digits), name in English, name in Nepali (must contain Devanagari), office, CAPTCHA. Server-side validation |
| `pay` | Summary (NPR 500) and a method choice: Khalti, eSewa or Other. Nothing real is charged. Saves a `replacement_requests` row |
| `processing` | A 3-second "processing" page that refreshes itself |
| `done` | Mock transaction ID (`DEMO-NID-2026-######`), payment date, a token (`REP-<office>-#####`), an appointment 14 days ahead at 10:30 AM, the replacement checklist and a print button |

### Information: `information.php`
Static content: About, Eligibility, Required Documents, Enrollment Process, Document Upload, Verification, Application Status, Replacement, FAQ (expandable items), a contact box with office details, and a prototype notice. The phone and email are placeholders.

### Diagnostic: `session-test.php`
Open it and press F5. "visits" should count 1, 2, 3. It also shows the session save path, whether it is writable, whether the browser sent its cookie, and whether output started before headers. Delete it when you're finished.

---

## 8. The enrollment wizard in detail

The wizard works like **a paper form kept in a folder at the front desk**. Everything the citizen types is held in the session (`$_SESSION['enr']`) until the final step. Only then is it written to the database, all at once.

### 8.1 Steps

The citizen sees 10 steps. The code counts 11 because the OTP gate is internal step 1.

| Internal step | Shown as | Content | Handled by action |
|---:|---|---|---|
| 1 | (before step 1) | Citizenship number + mobile, then OTP | `verify`, `otp`, `resend`, `change_mobile` |
| 2 | Step 1: Personal | Names in English and Nepali, date of birth, gender, nationality, marital status, parents | `sec` |
| 3 | Step 2: Birth & Demographic | Country, province, district, local level, ward, place of birth, birth certificate details | `sec` |
| 4 | Step 3: Citizenship | Number, type, district, issuing office, issue date, authority | `sec` |
| 5 | Step 4: Contact & Address | Mobile, alternate mobile, email, permanent address, current address | `sec` |
| 6 | Step 5: Family | Father, mother, spouse (if married), guardian | `sec` |
| 7 | Step 6: Education / Occupation | Education level, occupation, organization, designation | `sec` |
| 8 | Step 7: Identification | Height, blood group, eye colour, distinguishing mark, disability | `sec` |
| 9 | Step 8: Documents | Checklist + "I have reviewed it" tick | `docs` |
| 10 | Step 9: Appointment | Office, date (tomorrow or later), time slot | `appt` |
| 11 | Step 10: Review | Everything shown back, each block with an Edit button, then Submit | `goto`, `submit` |

### 8.2 The session record: `$_SESSION['enr']`

| Key | Meaning |
|---|---|
| `step` | Current internal step (1 to 11) |
| `otp` | True once the OTP was accepted |
| `mobile_verified` | True once the mobile number is verified |
| `verification_stage` | True while the OTP screen is showing |
| `otp_hash`, `otp_expires`, `otp_attempts` | The hashed OTP, expiry time (5 minutes) and wrong-attempt counter (limit 5) |
| `ret` | True when the citizen came back to edit an earlier step |
| `from_review` | True when that edit started from the Review page, so Save returns to Review |
| `d` | All form values typed so far, keyed by field name |

The wizard refuses to continue unless both `otp` and `mobile_verified` are true. If either is lost, the citizen is sent back to step 1.

### 8.3 Actions (what each POST does)

Every form posts a hidden or button field named `a`. `enroll.php` switches on it.

| `a` | Only allowed at step | What it does |
|---|---|---|
| `verify` | 1 | Validates citizenship number and mobile (`97`/`98` + 8 digits). Checks for an existing application and redirects to `existing-application.php` if found. Otherwise creates the demo OTP (`123456`, hashed) and shows the OTP screen |
| `otp` | 1 | Checks expiry, attempts and the code. Accepts Devanagari digits and spaces. On success it sets the verified flags and moves to internal step 2 |
| `resend` | 1 | Issues a fresh demo OTP and resets attempts |
| `change_mobile` | 1 | Clears the OTP state and shows the first screen again |
| `sec` | 2 to 8 | Saves the section's values to `d`, validates each visible field by type, then moves on (or back to Review after an Edit) |
| `docs` | 9 | Requires the acknowledgement tick |
| `appt` | 10 | Requires office, a valid future date and a time; checks the office exists and is available |
| `goto` | any (after OTP) | Jumps to an earlier step. When started from Review, Save returns to Review |
| `submit` | 11 | Requires the confirmation tick, then writes everything to the database (section 8.5) |

Validation by field type: `m` mobile `97/98xxxxxxxx`, `d` real date, `e` valid email, `n` number (ward 1 to 35, height 50 to 250 cm), `s` select, `r` radio, `t` text. Required fields are checked only when the field is visible.

### 8.4 How `fields.php` works

Each field is a small array:

```
[ field_name, type, required, options_or_label, hint_or_condition ]
```

| Type | Renders |
|---|---|
| `t` | Text box |
| `m` | Telephone box (10 digits, placeholder `98XXXXXXXX`) |
| `d` | Date picker |
| `e` | Email box |
| `n` | Number box |
| `s` | Dropdown |
| `r` | Radio buttons |
| `f` | File input (defined in the renderer but not used by any step) |

A field can have a **condition**, for example the spouse fields show only when `pd_marital = Married`, and the current-address fields only when `same_addr = No`.

| Function | Job |
|---|---|
| `lbl($key)` | Returns the label from `$FIELD_LABELS`, or builds one from the key |
| `vis($field, $data)` | Decides if the field is visible. The same check is used when drawing, validating and reviewing, so a hidden field is never required and never shown on the Review page |
| `fld($field)` | Draws the label, the input, any error message and any hint |
| `fieldValue($key)` | Returns the escaped saved value |
| `field_t($key)` | A small set of English fallback messages |

Field names use prefixes that say where the value belongs:

| Prefix | Belongs to |
|---|---|
| `pd_` | Personal details |
| `birth_` | Birth information |
| `cz_` | Citizenship |
| `ct_` | Contact |
| `perm_`, `curr_`, `same_addr` | Addresses |
| `fa_`, `mo_`, `sp_`, `gu_` | Father, mother, spouse, guardian |
| `ed_` | Education / occupation |
| `id_` | Identification |
| `ap_` | Previous application |

### 8.5 The final submit (one database transaction)

When the citizen confirms and presses Submit, everything happens inside one transaction. If any insert fails, nothing is saved.

1. Check again that this citizenship number has no application.
2. Find or create the citizen in `users` (stores citizenship number, date of birth, mobile, verified flag).
3. Insert `personal_details`, `citizenship_details`, `contact_details`, `education_occupation`.
4. Insert two `addresses` rows: permanent, and current (a copy of permanent when `same_addr` is Yes).
5. Insert `family_members` rows: Father, Mother, Spouse (if married), Guardian (if given).
6. Insert the `applications` row with status `appointment_scheduled`, then set the application number `NID-<year>-<id padded to 6>`.
7. Look up the chosen office and create the token `NID-<office code>-<id + 426, padded to 5>`.
8. Insert the `appointments` row (office, date, time, token).
9. Insert two `status_history` rows: `submitted` and `appointment_scheduled`.
10. Commit, save the details in `$_SESSION['done']`, clear the enrollment session and redirect to `success.php`.

If something fails, a message appears on the Review page. While `$debugSubmit = true;` (near the top of the submit block), the message includes the raw database error. **Set it to `false` before showing the project to others.**

### 8.6 Form fields to database columns

| Form fields | Table | Columns |
|---|---|---|
| `pd_first_name`, `pd_middle_name`, `pd_last_name` (+ `_np` versions) | `personal_details` | `first_name`, `middle_name`, `last_name`; joined into `full_name` and `nepali_name` |
| `pd_dob`, `pd_gender`, `pd_nationality`, `pd_marital`, `pd_father_name`, `pd_mother_name` | `personal_details` | `dob`, `gender` (lower case), `nationality`, `marital_status`, `father_name`, `mother_name` |
| `birth_country`, `birth_province`, `birth_district`, `birth_local_level`, `birth_ward`, `birth_place` | `personal_details` | `birth_country`, `birth_province`, `birth_district`, `birth_local`, `birth_ward`, `birth_place` |
| `cz_cit_district` | `personal_details` and `citizenship_details` | `citizenship_district`, `cit_district` |
| `cz_cit_no`, `cz_type`, `cz_issuing_office`, `cz_issue_date`, `cz_issuing_authority` | `citizenship_details` | `cit_no`, `cit_type`, `cit_office`, `cit_issue_date`, `cit_authority` |
| `ct_mobile`, `ct_alt_mobile`, `ct_email` | `contact_details` | `mobile`, `alt_mobile`, `email` |
| `perm_*` | `addresses` (`kind = permanent`) and legacy columns of `contact_details` | `province`, `district`, `local_level`, `ward`, `tole`, `house_no` |
| `curr_*` | `addresses` (`kind = current`) | same columns (or a copy of permanent) |
| `fa_*`, `mo_*`, `sp_*`, `gu_*` | `family_members` | `relationship`, `name`, `nepali_name`, `citizenship_number`, `nationality` |
| `ed_education`, `ed_occupation`, `ed_organization` | `education_occupation` | `education`, `occupation`, `employer` |
| `ap_prev`, `ap_prev_nid`, `ap_prev_no` | `applications` | `prev_applied`, `prev_nid`, `prev_app_no` |
| Office, date, time | `appointments` | `office_id`, `appointment_date`, `appointment_time` |

Collected and checked but **not stored yet** (no column): `ed_designation`, `ed_other_occupation`, all `id_*` Identification fields, `birth_certificate_no`, `birth_registration_date`.

---

## 9. Database

MySQL database `nid_prototype`, character set `utf8mb4`.

### Tables

| Table | Holds | Key columns |
|---|---|---|
| `offices` | Mock enrollment offices | `id`, `code` (KTM, LTP, BKT), `name`, `address`, `contact`, `available` |
| `users` | One row per citizen | `citizenship_number` (unique), `date_of_birth`, `mobile`, `mobile_verified` |
| `applications` | One enrollment per citizen | `user_id` (unique), `application_number` (unique), `application_type`, `status`, `remarks`, `prev_applied`, `prev_nid`, `prev_app_no`, timestamps |
| `personal_details` | Name, birth and parent details | `user_id`, names, `dob`, `gender`, `nationality`, `marital_status`, birth fields, parents |
| `citizenship_details` | Citizenship certificate details | `user_id`, `cit_no`, `cit_type`, `cit_district`, `cit_office`, `cit_issue_date`, `cit_issue_bs`, `cit_authority`, `cit_doc_status` |
| `contact_details` | Mobile, email and a short address | `user_id`, `mobile`, `alt_mobile`, `email`, `district`, `municipality`, `ward`, `address` |
| `addresses` | Permanent and current address | `user_id`, `kind`, `province`, `district`, `local_level`, `ward`, `tole`, `house_no`, `street` |
| `family_members` | Father, mother, spouse, guardian | `user_id`, `relationship`, `name`, `nepali_name`, `citizenship_number`, `nationality` |
| `education_occupation` | Education and work | `user_id`, `education`, `employment`, `occupation`, `employer` |
| `appointments` | Office visit | `application_id`, `office_id`, `appointment_date`, `appointment_time`, `token_number` |
| `status_history` | Every status an application has passed through | `application_id`, `status`, `remarks`, `created_at` |
| `otp_verifications` | OTP records (created in v3; the current wizard keeps its OTP in the session instead) | `citizen_id`, `otp_hash`, `expires_at`, `attempt_count`, `verified_at` |
| `replacement_requests` | Replacement payments and appointments | `nin`, `mobile`, `name_en`, `name_ne`, `office_id`, `amount`, `payment_method`, `payment_status`, `transaction_id`, `payment_date`, `appt_date`, `appt_time`, `token` |

### Relationships

```
users 1 ──── 1 applications ──── 1 appointments ──── * offices
  │                 │
  │                 └──── * status_history
  ├──── 1 personal_details
  ├──── 1 citizenship_details
  ├──── 1 contact_details
  ├──── 1 education_occupation
  ├──── 2 addresses        (permanent, current)
  └──── * family_members

offices 1 ──── * replacement_requests
```

### Application statuses

`submitted`, `verified`, `appointment_scheduled`, `biometric_pending`, `processing`, `card_ready`, `action_required`. The status page draws the first six as the timeline.

### Rules enforced by the database
- `users.citizenship_number` is unique.
- `applications.user_id` is unique, so one citizen can have only one enrollment.
- `applications.application_number` is unique.
- Child tables reference `users` or `applications` with foreign keys.

### Installing the database
- **Fresh install:** import only `database/nid_prototype.sql`. It already contains the v2 and v3 changes.
- **Older database:** run `upgrade_v2.sql`, then `upgrade_v3.sql`.

---

## 10. Languages (English / Nepali)

- `?lang=ne` or `?lang=en` stores the choice in the session. The top-bar links do this.
- `bootstrap.php` loads `lang/en.php` as the base and `lang/<choice>.php` on top, so a missing Nepali string falls back to English.
- Both files have 183 keys, and every English key has a Nepali version.
- Dropdown values such as "Married" or "Bagmati Province" stay in English; labels and messages are translated.
- The enrollment wizard uses its own labels from `fields.php` and several hard-coded English messages. Only the per-step intro line (`sx`) and the document checklist come from the language files. Switching to Nepali therefore does not translate the whole wizard yet.
- Several keys are defined twice in `en.php` (for example `repl`, `info`, `status`, `mobile`). PHP keeps the last definition.

---

## 11. Look and feel

### `assets/style.css` (colour scheme from Nepal's flag)

| Variable | Colour | Used for |
|---|---|---|
| `--blue` | `#003893` | Top bar, hero, headings, primary buttons |
| `--red` | `#b3122d` | Header underline, accents, required stars, submit buttons |

Main classes:

| Class | Used for |
|---|---|
| `.topbar`, `header`, `nav`, `.proto` | Page header, navigation, yellow prototype banner |
| `.hero`, `.svc`, `.sc` | Home hero and service cards |
| `.card` | White content panels |
| `.btn`, `.btn.red`, `.btn.alt`, `.btn.sm` | Buttons |
| `.grid2`, `.fld` | Two-column form layout and field wrapper |
| `.err`, `.hint`, `.warn`, `.demo`, `.ok-msg`, `.rq`, `.opt` | Messages and field markers |
| `.stepper`, `.sn`, `.bar` | Progress text, progress bar and step list |
| `.bring` | The "Bring these documents" box |
| `.pv` | Review / summary blocks |
| `.tl` | Status timeline |
| `.cap`, `.otp` | CAPTCHA and OTP boxes |
| `.faq` | Expandable questions |
| `.contact-box`, `.contact-btn`, `.contact-details`, `.contact-grid` | Contact section on the Information page |
| `.logo-link` | Clickable logo |

It is responsive (a breakpoint at 700 px) and has a print style that hides navigation and buttons.

### `includes/footer.php` script
Two small features, prepared for later use:
- `data-if="field=value"` shows or hides a block depending on another field.
- `data-dist="target"` fills a dependent dropdown from `$DIST` (a province-to-districts list).

`fields.php` does not output these attributes yet, so the current wizard relies on the server for conditional fields. That means the current-address fields appear only after Save when "No" is chosen.

### Emblem
`header.php` shows `assets/govlogo.png` if the file exists. Otherwise it shows a dashed "EMBLEM placeholder" circle.

---

## 12. Security

- **Prepared statements** with PDO everywhere (emulation off), so user input cannot change SQL.
- **CSRF token** on every POST form, checked on the server.
- **Output escaping** through `e()` and `t()`.
- **Server-side validation** for every field. The browser's `required` is only a convenience.
- **OTP stored hashed** (`password_hash`), with a 5-minute expiry and a 5-attempt limit.
- **Verification cannot be skipped.** Every wizard action checks the verified flags. Changing the verified mobile number on the contact step forces a new verification.
- **One citizen, one enrollment**, enforced in code and by a unique database key.
- **Generic database-connection message** instead of raw errors.
- **Transaction on submit**, so a failed submit leaves no partial records.

Prototype shortcuts: the OTP is the fixed demo code `123456` and is shown on screen, the database user is `root` with no password, and `$debugSubmit` shows raw database errors. None of these belong in a real system.

---

## 13. Setup on XAMPP

1. Copy the project folder (for example `nid-prototype`) into `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Open phpMyAdmin and import `database/nid_prototype.sql`.
4. Open `http://localhost/nid-prototype/`.
5. Database settings are in `includes/bootstrap.php` (default: host `localhost`, database `nid_prototype`, user `root`, empty password).
6. Optional: place an emblem image at `assets/govlogo.png`. Check the licence of any official emblem yourself.

Keep the folder layout exactly as in section 4. `bootstrap.php` looks for `../lang/en.php`, and the pages look for `includes/header.php` and `assets/style.css`.

---

## 14. Demo data and test scenarios

### Demo values

| What | Value |
|---|---|
| Enrollment OTP | `123456` |
| Status lookup, Appointment Scheduled | `NID-2026-000101` / `1995-04-12` |
| Status lookup, Card Ready | `NID-2026-000102` / `1990-01-20` |
| Status lookup, Action Required | `NID-2026-000103` / `2000-07-08` |
| Citizenship numbers that trigger the duplicate notice | `00-01-70-00001`, `-00002`, `-00003` |
| Mobile format | `97` or `98` followed by 8 digits |
| Replacement NIN format | `123-456-789-0` |
| Replacement CAPTCHA | Shown on screen above the box |
| Seed offices | Kathmandu (KTM), Lalitpur (LTP), Bhaktapur (BKT) |

### Test scenarios

| Scenario | Expected result |
|---|---|
| Enroll with a new citizenship number, all steps, submit | Success page with application number, token and appointment |
| Look up that application number with the date of birth you entered | Timeline shows "Appointment Scheduled" |
| Enroll again with the same citizenship number | "Existing Application Found" page |
| Enter a wrong OTP | "Incorrect OTP" message; five wrong tries lock the OTP |
| Press Edit on the Review page, change something, Save | Returns to Review, not to the next step |
| Choose "Married" on the Personal step | Spouse fields appear on the Family step |
| Choose "No" for current address on the Contact step, Save | Current-address fields appear; fill them and Save again |
| Switch to Nepali | Home, status, replacement and information screens change language |
| Replacement with a valid NIN, a Khalti payment | Transaction ID, token and appointment 14 days ahead |

---

## 15. Troubleshooting

| Problem | Likely cause and fix |
|---|---|
| "Database unavailable. Please import database/nid_prototype.sql." | MySQL isn't running, the database isn't imported, or the credentials in `bootstrap.php` are wrong |
| After the OTP you land back on the first screen | The session lost the OTP state. Open `session-test.php` and press F5; "visits" must count up. Check that the session save path is writable and that you use one address (`localhost` or `127.0.0.1`) throughout. The wizard now shows a message when this happens |
| "Invalid request." on any form | The CSRF token and session don't match. Cookies may be blocked, or the session expired. Reload the page and try again |
| Blank white page | A PHP error is hidden. Put `ini_set('display_errors', 1); error_reporting(E_ALL);` right after `<?php` in the page, or read `C:\xampp\apache\logs\error.log` |
| "Output before headers: YES" on `session-test.php` | A file has output before `<?php`, often an invisible BOM. Re-save the named file as "UTF-8 without BOM" |
| Submit shows "We could not save your enrollment" | With `$debugSubmit = true` the message ends with the database error. It usually names a missing column, so check you imported the latest `nid_prototype.sql` |
| "Existing Application Found" for a number you never used | That number belongs to a seeded demo citizen or an earlier test. Use a different one, or delete the rows in phpMyAdmin |
| Nepali text shows as boxes | The device lacks a Devanagari font. The stylesheet asks for "Noto Sans Devanagari" first |
| Styles missing | `assets/style.css` is in the wrong place, or the folder name in the URL doesn't match |

To reset all test data, drop the `nid_prototype` database and import `nid_prototype.sql` again.

---

## 16. Version history

| Version | Highlights |
|---|---|
| **v1** | Home page, simple enrollment, status lookup, document checklist, English/Nepali switch, seed data |
| **v2** | Data-driven multi-step wizard (`fields.php`), full personal, citizenship, contact, address, family and education storage, government-style header with emblem placeholder |
| **v3** | Mobile + OTP verification before the form, duplicate-enrollment protection (unique key), `replacement.php` with a mock NPR 500 payment, combined `information.php`. eNID and Correction removed |
| **Submission fixes** | Fixed the commit typo that rolled back every submission; mapped form fields to the real database columns; duplicate check now blocks any existing application; added `existing-application.php`; height validation (50 to 250 cm); Edit on Review returns to Review; the Submit button now carries its own action |
| **OTP hardening** | Clear message when the verification session is lost; OTP accepts Devanagari digits and spaces; the form is drawn directly after a correct OTP; added `session-test.php` |

---

## 17. Known gaps and future work

**To fix before a demo**
1. Designation, other occupation, the Identification step and the birth certificate fields are collected but not saved. Add columns or a table and map them in the submit block of `enroll.php`.
2. `information.php` mentions uploading a citizenship image, but no upload exists. Reword it or build the feature.
3. `header.php` has a stray `</a>` after the Information link and an empty link in the top bar.
4. Enrollment page text is hard-coded in English and doesn't switch language.
5. `en.php` and `ne.php` define some keys twice, and contain keys the current pages no longer use (`f`, `h`, `faq`, `info_title`, `info_lead`, `info_note`).
6. `$debugSubmit = true;` must be turned off for any public demo.
7. `otp_verifications` is not used by the current wizard, which keeps the OTP in the session.
8. The emblem file name differs between the v2 note (`emblem.png`) and the code (`govlogo.png`). The code is the one that counts.

**Planned / not built**
- Citizen dashboard and admin panel
- Real document upload and office-side verification
- Dependent province, district and local-level dropdowns (the script in `footer.php` is ready for them)
- Instant show/hide of conditional fields without a server round trip
- A correction workflow
- Real SMS delivery of the OTP and a real payment gateway
- Full Nepali translation of the enrollment wizard
