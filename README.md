# AviTON — Team Checklist and File Assignments

AviTON is a PHP and MariaDB inventory borrowing website using Bootstrap 4.6 and SB Admin 2. This README lists what to submit, which files each role should handle, and what still needs checking.

The member names and roles come from the AviTON proposal. The file assignments below are a working guide, not proof of who wrote the existing code. Record actual completed work and evidence in the contribution document.

## What needs to be submitted

The project instructions list these deliverables on page 15.

| Required item | Files to include | Status / next step |
|---|---|---|
| Working website and source | Root PHP pages, `assets/`, `config/`, `includes/`, `database/` and supplied `.htaccess` files | Present; rehearse the complete website on XAMPP. |
| Database structure | `database/schema.sql`, `database/createdb.php`, `database/populatedb.php` | Present; include executable files, not only the Word dictionary. |
| Database ERD | `documents/02-Database-ERD-and-Data-Dictionary.docx` | Created; Baskin reviews the relationships and fields. |
| Architecture diagram | `documents/03-System-Architecture-and-Design-Pattern.docx` | Created; Claus reviews and explains the request flow and pattern. |
| Short system documentation | `documents/01-System-Documentation.docx` | Created; all members check their sections. |
| Group member list | `documents/04-Team-Assignments-and-Contribution-Record.docx` | Five members listed; confirm spelling. |
| Individual assignments | This README and document 04 | Listed; update if actual responsibilities change. |
| Contribution evidence | Completed document 04 and real evidence files | Still needed from every member. |
| Final presentation/demo | `documents/06-Presentation-and-Technical-Defense-Guide.docx` | Guide prepared; rehearse and present as a team. |

`documents/05-Testing-and-Requirements-Compliance.docx` contains the audit summary and acceptance-test forms. `REQUIREMENTS-AUDIT.md` contains the earlier findings. Neither replaces actual test results or contribution evidence.

The Word files passed structural checks, but full-page rendering was unavailable. Open them in Word and check page breaks, tables and diagrams before submitting.

## Team roles and their files

### Claus Marvin Hipolito — Project Lead / Architecture

**Handle:** project structure, integration, the architecture explanation and the final submission.

| Files | Responsibility |
|---|---|
| `dashboard.php`, `admin-dashboard.php` | Dashboard entry points and how each role reaches its page. |
| `includes/dashboard-layout.php`, `includes/page-helpers.php` | Shared structure and page includes. Coordinate access checks with Nash. |
| `config/config.php`, `read.php`, `validation.php`, `save.php` | Review how the layers connect; coordinate changes with feature owners. |
| `README.md`, `REQUIREMENTS-AUDIT.md` | Keep assignments and remaining tasks clear. Distinguish code review from executed tests. |
| Documents 01, 03 and 06 | System explanation, architecture diagram and presentation plan, with input from everyone. |

**Still needed:** review the request flow, combine the final files, schedule rehearsal and confirm that everyone can explain their own work.

### Baskin Carreon — Database / Backend

**Handle:** the three tables, relationships, procedures, JOINs, PDO calls and database setup.

| Files | Responsibility |
|---|---|
| `database/schema.sql` | Tables, keys, constraints, JOINs, transactions and stock/state rules. |
| `database/createdb.php` | Create the database and load the schema and procedures. |
| `database/populatedb.php` | Add missing samples without resetting existing data; coordinate password hashing with Nash. |
| `config/config.php` | PDO connection and named methods that execute prepared procedure calls. |
| `read.php` | Agree on query parameters, returned fields and reports with Christine. |
| Document 02 | Review the ERD, four foreign keys, data dictionary and JOIN explanations. |

**Still needed:** check setup on a separate demo database, verify the stock cycle with Christine, and test competing releases and duplicate requests.

### Nash Sister — Authentication / Security

**Handle:** registration, login/logout, hashing, sessions, CSRF, authorization and server validation.

| Files | Responsibility |
|---|---|
| `index.php`, `register.php` | Login/registration behavior; coordinate form appearance with Julia. |
| `includes/request-helpers.php` | Sessions, CSRF, roles, request handling, throttling and controlled errors. |
| `includes/form-validation.php` | Shared server rules; coordinate equipment and borrowing rules with Christine. |
| `validation.php`, `validate_registration.php` | Complete-form and registration field checks. |
| `save.php` | Registration, login, logout and password changes; repeated validation before saving. |
| `assets/js/auth.js`, `assets/js/ajax.js` | Authentication form binding and registration AJAX; share message work with Julia. |
| `includes/account-settings.php`, `includes/setup-access.php` | Password form and local setup restrictions. |
| Root `.htaccess`, `database/.htaccess`, `documents/.htaccess` | Review which files Apache may serve. |
| Documents 01 and 05 | Security explanation and security-test evidence. |

**Still needed:** test successful registration, password changes across two sessions, session expiry, missing CSRF, wrong-role actions and access to another user's records.

### Christine Mercado — Core Features

**Handle:** equipment CRUD, quantities, borrowing, admin confirmations, records, search and reports.

| Files | Responsibility |
|---|---|
| `read.php` | Equipment, summaries, records, history, search/filtering and report requests. |
| `save.php` | Equipment save/delete and borrow/return transitions; coordinate security with Nash. |
| `assets/js/dashboard.js`, `assets/js/admin.js` | Dashboard actions, refreshes, equipment forms and admin-mode initialization. |
| `assets/js/data.js` | Shared backend requests, XML responses, form submission and safe DOM updates; coordinate with Nash and Julia. |
| `includes/equipment-section.php`, `includes/borrow-return-records.php` | Equipment controls, records and confirmation actions. |
| `includes/inventory-overview.php`, `includes/inventory-reports.php`, `includes/dashboard-dialogs.php` | Totals, reports and action dialogs; Julia handles their appearance. |
| `database/schema.sql`, `config/config.php` | Agree on procedure parameters and stock/state rules with Baskin. |
| Documents 01, 05 and 06 | Workflows, feature-test results and the live CRUD/borrowing demonstration. |

**Still needed:** run a successful CRUD test and full borrow/return cycle; check rejection/cancellation, stale edits and deletion of equipment with history.

### Julia Navvarete — Frontend / Integration

**Handle:** Bootstrap/SB Admin 2 layout, responsive pages, navigation, forms and clear AJAX messages.

| Files | Responsibility |
|---|---|
| `assets/css/styles.css` | Styling, mobile layout, field messages, tables and dialogs. |
| `assets/vendor/` | Retain Bootstrap, SB Admin 2, jQuery, icons and license notices. These are third-party assets, not original team code. |
| `includes/page-head.php`, `includes/sidebar-navigation.php`, `includes/top-navigation.php` | Styles/scripts, sidebar and profile navigation. |
| `includes/dashboard-layout.php`, `includes/inventory-overview.php` | Responsive layout, greeting and cards; coordinate structure with Claus. |
| `includes/equipment-section.php`, `includes/borrow-return-records.php`, `includes/inventory-reports.php` | Readable tables, controls and empty states; coordinate actions with Christine. |
| `includes/dashboard-dialogs.php`, `includes/account-settings.php`, `includes/setup-result.php` | Dialogs, account form and setup result page. |
| `index.php`, `register.php`, `assets/js/ajax.js` | Login/registration layout, inline spans and friendly messages; Nash reviews validation rules. |
| `assets/js/dashboard.js`, `assets/js/data.js` | Review interface updates and keyboard behavior with Christine. |
| Documents 01, 05 and 06 | Interface instructions, mobile-test evidence and the website overview demo. |

**Still needed:** check small screens, keyboard navigation, dialogs, readable tables, password confirmation and messages after correcting invalid input.

## How to work on shared files

- `save.php`: Nash handles account actions; Christine handles equipment/borrowing actions. Agree before changing shared request handling.
- `read.php`: Christine handles request flow; Baskin handles procedure results. Keep field names consistent.
- `config/config.php` and `database/schema.sql`: Baskin coordinates; Nash reviews account security and Christine reviews stock behavior.
- `assets/js/ajax.js`: Julia handles the field-message experience; Nash keeps it aligned with server validation.
- UI includes: Julia handles appearance; Christine handles feature behavior; Claus checks integration.
- Every member completes their own entries in document 04. A role assignment does not prove a contribution.

## System requirements to demonstrate

The implementation contains these features. Rehearse a working example for each; this list does not claim every path has been tested.

| Requirement | Where to demonstrate it | Lead |
|---|---|---|
| Authentication and two roles | Registration, login/logout and USER/ADMIN dashboards | Nash |
| Related tables and JOINs | `schema.sql`, ERD, records and inventory reports | Baskin |
| Create, read, update and delete | Equipment controls and backend actions | Christine |
| Search and filtering | Equipment search and record status filters | Christine |
| Client/server validation | Registration spans, validation endpoints and checks in `save.php` | Nash + Julia |
| Security and sessions | Hashing, CSRF, role/ownership checks, protected pages and logout | Nash |
| OOP and a design pattern | `Config`, `Validation`, `App`, `HttpError`; layered architecture and Config's data-access facade | Claus, with the class reviewers |
| Usable interface | Bootstrap/SB Admin 2 pages, mobile layout and feedback | Julia |

Keep these agreed project rules when making changes:

- Only USER accounts borrow. An administrator needs a separate USER account to borrow.
- Users choose positive whole-number quantities. There are no due dates or overdue rules.
- Pending requests do not reserve stock. ADMIN confirms physical release and receipt; only those confirmations change available stock.
- A return covers the whole record quantity. Equipment deletion is permanent but blocked by any linked borrowing record.
- Keep exactly three tables. Definitions and transactions stay in `schema.sql`; Config uses prepared `CALL` statements. Setup PHP files may contain SQL.
- Use backend requests and PHP sessions. Do not replace them with localStorage or JSON-based application data.

## Remaining work before submission

- [ ] Review assigned files and correct mismatches between code and documents.
- [ ] Run successful registration, equipment CRUD and a complete borrow/return cycle on a demo database.
- [ ] Run the remaining cases in document 05 and record results, including concurrency, session and mobile checks.
- [ ] Add real contribution evidence for all five members to document 04, with dates and specific changes or test records. Do not invent authorship or commits.
- [ ] Check all Word documents in Word, including diagrams, tables and page breaks.
- [ ] Rehearse the 20-minute presentation with all five members and prepare for technical questions.
- [ ] Package the source, database files, documents and evidence. Keep required vendor assets and license notices.
- [ ] Confirm instructor-specific submission format, originality approval or other requirements.

The earlier audit recorded 26 PHP syntax checks, five authored JavaScript syntax checks and 64 non-destructive checks passing. It did not rerun successful writes, a full borrowing cycle, concurrent stock updates or the complete mobile/session test matrix. Record remaining checks in document 05 rather than treating that audit as full coverage.

## Quick XAMPP setup

1. Keep the project directly in `C:/xampp2/htdocs/aviton/` and start Apache and MySQL.
2. Use `localhost`, port 3306, database `inventorydb`, username `root` and an empty password. The Config constructor and both setup files must agree.
3. Open `http://localhost/aviton/database/createdb.php`, then `http://localhost/aviton/database/populatedb.php` on the same computer.
4. Open `http://localhost/aviton/`.

| Username | Password | Role |
|---|---|---|
| Admin | Admin | ADMIN |
| User | User | USER |

These credentials work if the sample accounts were populated and their passwords have not changed. Setup stores hashes and does not reset existing passwords or stock. Registration and password changes require at least 12 characters; sample passwords are a setup-only exception.

Back up an existing database before setup. Reinstalling keeps compatible tables and rows; older incompatible drafts need a separate migration. If `Error: Code 0001` appears, check MySQL, credentials and database creation. Do not drop tables to fix a connection error. PHP must be able to write to its configured session/temp directory.

Root with an empty password and these sample accounts are for local demos. Before public deployment, replace sample passwords, use a restricted database account, enable HTTPS and disable browser setup pages.
