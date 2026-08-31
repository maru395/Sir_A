# AviTON

PHP 8 and MariaDB inventory borrowing website, using local Bootstrap 4.6 and Start Bootstrap SB Admin 2 assets. No application build step is needed.

## XAMPP setup

1. Start Apache and MySQL in XAMPP. The database connection uses port 3306.
2. The PDO constructor in `config/config.php` uses `localhost`, database `inventorydb`, username `root` and an empty password. Each setup page has its own matching PDO connection settings. This root connection is for local XAMPP use only.
3. Open **http://localhost/aviton/database/createdb.php** to install `schema.sql`. Setup uses the same root connection; it does not create database accounts, change their passwords or rewrite config.php.
4. Open **http://localhost/aviton/database/populatedb.php** to add the sample accounts and initial equipment. Existing passwords, equipment and borrowing quantities are not reset.
5. Open **http://localhost/aviton/**.

| Username | Password | Role |
|---|---|---|
| Admin | Admin | ADMIN |
| User | User | USER |

These are local demonstration credentials. Passwords are stored as hashes. Change both passwords in Account before sharing the website. Registration and password changes require at least 12 characters; the sample accounts are a setup-only exception.

## Project structure

```text
aviton/
  assets/
    css/                   Project styles
    js/                    AJAX and interface scripts
    vendor/                Bootstrap, SB Admin 2, slim jQuery and fonts
  config/
    config.php             Connection settings and Config PDO class
  database/
    createdb.php           Browser-loadable schema installation
    populatedb.php         Browser-loadable sample population
    schema.sql             Tables and stored procedures
  includes/                Shared page sections, validation, sessions and setup
    request-helpers.php    Sessions, access checks and request handling
    page-helpers.php       Page access and shared form fields
    form-validation.php    Server-side form validation
    setup-access.php       Local setup access and locking
    setup-result.php       Setup result page
    dashboard-layout.php   Shared user and admin dashboard layout
    page-head.php          Page title, styles and scripts
    sidebar-navigation.php Sidebar menu
    top-navigation.php     Top navigation bar
    inventory-overview.php Greeting and inventory summary
    equipment-section.php  Equipment catalog and borrowing controls
    borrow-return-records.php Borrowing records and admin confirmations
    inventory-reports.php  Admin inventory reports
    account-settings.php   Account details and password form
    dashboard-dialogs.php  Action, equipment and history dialogs
  index.php
  register.php
  dashboard.php
  admin-dashboard.php
  read.php
  validation.php
  validate_registration.php
  save.php
```

The config folder contains only `config.php`. Named Config methods prepare direct `CALL sp_...` statements, execute bound parameters and fetch results. They do not read or parse SQL files. Table definitions, procedure bodies and borrowing transactions stay in `schema.sql`. Schema loading belongs only to `createdb.php`, and sample inserts belong only to `populatedb.php`. The two setup pages use direct PDO statements, following the supplied PHP examples: `createdb.php` creates the database and loads the schema; `populatedb.php` inserts sample arrays with prepared statements and hashed passwords.

## Data and borrowing

There are exactly three tables: `user`, `equipment` and `records`. History is stored as text in the relevant equipment or record row. Each history line contains a timestamp, event type, actor ID and hex-encoded actor/text fields separated by tabs. Encoding preserves punctuation, tabs and newlines in user text without allowing it to change the event format.

Only USER accounts can borrow. ADMIN accounts manage equipment and confirm physical release and receipt; an administrator needs a separate USER account to borrow. Users choose positive whole-number quantities. There are no due dates or overdue rules.

A pending request does not consume stock. Admin release reduces available stock, a return request leaves it on loan, and admin receipt restores the quantity. Stored procedures use row locks and transactions so failed or concurrent operations cannot corrupt stock. Equipment supports create, read, update and permanent delete. Admins must confirm deletion; it removes the entire equipment entry, all its units and its equipment change history. Deletion is blocked whenever any borrowing record references the item, including returned, rejected or cancelled requests, so transaction history remains intact. Stale edits and deletion attempts are rejected. Reports include INNER, LEFT, RIGHT and a FULL OUTER JOIN equivalent.

Registration uses inline `checkField(...)` input/blur handlers and `XMLHttpRequest` in `assets/js/ajax.js`, following the supplied passenger-form example. Per-field requests go to `validate_registration.php` and return `VALID` or `INVALID|message` for the adjacent span. Username availability is checked on blur and submission; changing a password also rechecks its confirmation. Requests are debounced and stale responses are ignored. Passwords are never trimmed. Only the exact registration handlers are permitted by the page's content security policy.

On submission, registration waits for field checks, validates the complete form through `validation.php`, then posts to `save.php`. Those complete-form responses use XML. Save repeats all server validation, so bypassing JavaScript cannot save invalid input. Other forms retain their existing AJAX submission flow. Authentication uses PHP sessions, password hashing, CSRF tokens, role checks and ownership checks. Browser storage is not used for application data.

## Server files and troubleshooting

PHP uses XAMPP's configured session directory, normally `C:/xampp2/tmp`. Login-attempt counters and setup locks are plain files in that same server directory, outside the website. PHP must be able to write there. No additional project storage directory is created.

Setup pages are restricted to direct local access. Reopening `createdb.php` reinstalls stored procedures and keeps existing tables and rows. Reopening `populatedb.php` adds missing sample accounts and equipment, but does not reset existing passwords, roles or stock. A sample username with a conflicting role stops population before inserts. Population can be retried after a failure to add any remaining samples. Setup runs only one request at a time; no completion marker prevents a fresh database from being populated. The SQL uses separate table and procedure blocks, like the supplied airline example. Reinstalling the current three-table design preserves existing rows. Older incompatible drafts need a separate migration; the main schema no longer contains legacy conversion code.

If you see `Error: Code 0001`, the PDO connection failed. Start MySQL, check the port and credentials in `config/config.php`, and open `database/createdb.php` locally. Do not drop tables to fix connection errors. Back up the database separately before moving the project. Never publish database credentials, and disable both setup pages before exposing the website publicly.

The supplied slim jQuery asset has unused structured-data parsing removed. SB Admin 2 uses native smooth scrolling instead of the optional effects plugin. Vendor license notices are retained.
