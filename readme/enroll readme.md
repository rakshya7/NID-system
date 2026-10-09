# NID Enrollment Case-Study Prototype

A PHP/MySQL web-based prototype for demonstrating an online National Identity Card (NID) enrollment workflow. The system guides a user through mobile verification, personal and demographic information, citizenship details, contact/address information, family details, education/occupation, identification, document acknowledgement, appointment scheduling, and final review/submission.

> **Prototype notice:** This project is a case-study demonstration. It is not connected to the official government NID database or real payment/SMS systems. OTP, appointment availability, and token numbers are mock/demo data.

## Features

- Citizenship number and mobile number verification
- Demo OTP verification with expiry and attempt limits
- Existing application detection before starting or submitting an application
- Multi-step enrollment form with progress indicator
- Personal and demographic information collection
- Citizenship information collection
- Contact and address details
- Family member information
- Education and occupation information
- Identification/document checklist acknowledgement
- Appointment office, date, and time selection
- Review and edit before final submission
- CSRF protection for form submissions
- Server-side validation
- Session-based enrollment state
- Database transaction during final submission
- Application number generation
- Mock appointment token generation
- Application status history

## Technology Stack

- **Backend:** PHP
- **Database:** MySQL/MariaDB through PHP PDO
- **Frontend:** HTML/CSS with PHP-rendered forms
- **Session management:** PHP Sessions
- **Security:** CSRF validation, password hashing for demo OTP storage, prepared SQL statements
- **Web server:** Apache/PHP environment such as XAMPP

## Enrollment Flow

The enrollment process is divided into an initial verification stage followed by 10 displayed enrollment steps:

1. **Verification** — Citizenship number and mobile number
2. **Personal** — Personal information
3. **Birth & Demographic** — Birth and demographic information
4. **Citizenship** — Citizenship details
5. **Contact & Address** — Contact and address information
6. **Family** — Family information
7. **Education / Occupation** — Education and occupation
8. **Identification** — Identification information
9. **Documents** — Document checklist acknowledgement
10. **Appointment** — Office, date, and time
11. **Review** — Review and final submission

The source code internally uses 11 steps because the initial mobile verification stage is kept separate from the 10 displayed enrollment steps.

## Demo OTP

For this prototype, the OTP is:

```text
123456
```

No real SMS is sent. The OTP is stored as a password hash in the session and expires after 5 minutes. Incorrect attempts are limited to five before a new OTP is required.

## Requirements

Before running the project, make sure you have:

- PHP 8.x or a compatible PHP version
- MySQL or MariaDB
- Apache or another PHP-compatible web server
- A configured project database
- The project's required `includes/` files
- Database tables for users, applications, personal details, citizenship details, contact details, education/occupation, addresses, family members, offices, appointments, and status history

## Project Setup

### 1. Place the project in your web server directory

For XAMPP on macOS, a typical location is:

```text
/Applications/XAMPP/xamppfiles/htdocs/
```

Place the complete project folder inside the `htdocs` directory.

### 2. Configure the database

Create the project's MySQL/MariaDB database and import the provided database/schema SQL file if available.

Make sure the database connection used by:

```text
includes/bootstrap.php
```

contains the correct database host, database name, username, and password.

### 3. Check required includes

The enrollment page depends on:

```text
includes/bootstrap.php
includes/fields.php
includes/header.php
includes/footer.php
```

The application also expects supporting functions/data such as `$pdo`, `$STEPS`, `$T`, `$EN`, `csrf()`, `check_csrf()`, `fld()`, `doc_checklist()`, `vis()`, `lbl()`, `valid_date()`, `ins()`, and `pick()`.

### 4. Start Apache and MySQL

Using XAMPP:

- Start **Apache**
- Start **MySQL**

Then open the project through the local Apache server rather than opening the PHP file directly from Finder.

Example:

```text
http://localhost/<project-folder>/
```

or, if the enrollment file is directly accessible:

```text
http://localhost/<project-folder>/enroll.php
```

## How to Use

1. Open the enrollment page.
2. Enter a citizenship number.
3. Enter a valid Nepali mobile number beginning with `97` or `98`.
4. Select **Check and Send OTP**.
5. Enter the demo OTP `123456`.
6. Complete each enrollment section.
7. Review the document checklist.
8. Select an available appointment office, date, and time.
9. Review the entered information.
10. Confirm the information and submit the enrollment.
11. The system creates the application and appointment records and redirects to the success page.

## Database Behavior

During final submission, the system uses a database transaction. It:

1. Rechecks whether an active application already exists.
2. Finds or creates the citizen/user record.
3. Inserts personal details.
4. Inserts citizenship, contact, and education/occupation details.
5. Stores permanent and current addresses.
6. Stores applicable family members.
7. Creates an application with `appointment_scheduled` status.
8. Generates an application number in the format:

```text
NID-2026-XXXXXX
```

9. Generates a mock appointment token using the selected office code.
10. Creates the appointment record.
11. Records application status history.
12. Commits the transaction.

The implementation performs the duplicate-application check both before OTP verification and again immediately before creating the application. This behavior is implemented in the enrollment source. 

## Security and Validation

The prototype includes several server-side controls:

- CSRF checking on POST requests
- Prepared SQL statements
- Session-based state management
- Password hashing and verification for the demo OTP
- OTP expiration
- OTP attempt limiting
- Nepali mobile-number validation
- Date validation
- Email validation where applicable
- Appointment-office availability validation
- Verification of the mobile number before continuing with enrollment
- Re-verification requirement if the verified mobile number is changed

These behaviors are implemented directly in the enrollment workflow. fileciteturn0file0L132-L167

## Important Prototype Limitations

This is **not a production government NID system**.

The current prototype explicitly states that:

- OTP verification is mocked.
- No real SMS is sent.
- Appointment availability is mock data.
- Token numbers are mock identifiers.
- The system is not connected to the official government NID database.
- Real-world document verification is outside the prototype.
- Real payment systems are not connected.

The source code includes this prototype disclaimer and mock-data notice. fileciteturn0file0L110-L123

## Project Structure

A typical project structure is expected to look similar to:

```text
project-root/
├── enroll.php
├── success.php
├── existing-application.php
├── includes/
│   ├── bootstrap.php
│   ├── fields.php
│   ├── header.php
│   └── footer.php
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
└── database/
    └── schema.sql
```

The exact structure may differ depending on the rest of the project files.

## Future Improvements

For a production-ready system, the following would need to be replaced or expanded:

- Real SMS/OTP provider integration
- Real identity/NID database integration
- Secure authentication and user accounts
- Real document upload and verification
- Production appointment availability management
- Stronger audit logging
- Administrative dashboard
- Email/SMS notifications
- Production-grade error handling
- Deployment and infrastructure security
- Privacy and data-protection controls
- Real government workflow and verification rules

## Author

**Rakshya Shrestha**

Case-study / academic prototype for demonstrating an improved digital NID enrollment workflow.

## License

This project is intended for academic and demonstration purposes. Add an appropriate open-source license if the project is intended for public distribution.
