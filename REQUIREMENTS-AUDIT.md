# AviTON requirements audit

Audited: 31 August 2026. Scope: the current PHP project and local XAMPP site at `http://localhost/aviton/`.

The application covers most of the technical requirements. The submission package is not complete in the supplied files. The main gaps are diagrams, a clear explanation of the architecture/design pattern, evidence of completed individual contributions, and presentation preparation. The current connection and sample accounts are for a local demonstration, not public deployment.

## Sources and limits

- [Project instructions](C:/xampp2/htdocs/WEB-APPLICATION-DEVELOPMENT-PROJECT.pdf): all 19 pages reviewed. Minimum requirements are on pages 6–8; presentation and recitation requirements on pages 12–14; submission and contribution requirements on page 15; grading on pages 16–18.
- [AviTON proposal](C:/Users/maru/OneDrive/AVITON.pdf): both pages reviewed, including its five-member assignment table.
- Current source code, SQL definitions, README and non-destructive HTTP/database checks.

“Implemented” below means the code contains the feature. “Verified” means the stated behavior was also exercised during this audit. Successful account creation, equipment writes, borrowing and return completion were not repeated against the live data. No application source code or database rows were changed during this audit. This report is the only new project file.

## Minimum functional requirements

| PDF requirement | Assessment | Evidence and practical limit |
|---|---|---|
| Login, logout, registration/user creation | Implemented; login/logout verified | [save.php](C:/xampp2/htdocs/aviton/save.php) verifies password hashes, creates USER accounts and clears sessions. Registration rejects invalid input even when sent straight to save.php. No new account was created during this audit. |
| At least two roles with different permissions | Verified | ADMIN and USER exist. A user cannot open the admin dashboard or call equipment administration, release confirmation, return confirmation or report endpoints. ADMIN borrowing is rejected. |
| Multiple related database tables | Verified | [schema.sql](C:/xampp2/htdocs/aviton/database/schema.sql) defines user, equipment and records. The installed database has these three tables and four foreign keys linking records to borrowers, equipment, releasing admins and receiving admins. |
| Meaningful JOIN operations | Implemented; report reads verified | sp_record_list combines records, borrowers, equipment and confirming admins. sp_report_equipment uses LEFT JOIN; sp_report_users uses RIGHT JOIN; sp_report_full uses LEFT/RIGHT JOIN with UNION ALL for a FULL JOIN equivalent. |
| Meaningful CRUD | Implemented; reads verified | Equipment create/update/delete are implemented in Config, save.php and sp_equipment_save/sp_equipment_delete. Delete is permanent but blocked when an item has borrowing history. The PDF explicitly does not require every table to support all four operations. Successful write operations were code-reviewed, not repeated on live data. |
| Search/filtering | Verified | Equipment search and record status filters return the expected results. SQL-looking search text is treated as a search value, not executed as SQL. |
| Server/client validation | Implemented; invalid submissions verified | [form-validation.php](C:/xampp2/htdocs/aviton/includes/form-validation.php) applies shared rules. Registration AJAX checks individual fields, then the complete form; save.php checks again. Invalid names, emails, passwords and quantities are rejected. |
| Secure coding | Implemented, with deployment limitations | Prepared CALLs, escaped PHP output, JavaScript textContent, CSRF checks, password hashing and role/ownership checks are present. Missing CSRF and forbidden requests were rejected. Root/blank DB credentials and known sample passwords remain intentional local settings. |
| Meaningful OOP | Implemented | Config encapsulates its private PDO connection. Validation owns private input/error state and validation methods. App handles request/session services. HttpError extends RuntimeException. Dashboard is a JavaScript class. The PDF says inheritance is required where appropriate, not that every class needs a subclass. |
| Identifiable architecture and design pattern | Partially documented | Views/includes, request endpoints, validation and database access are separated. Config can be explained as a data-access facade. The proposal currently describes SB Admin and PHP OOP instead of explaining an application design pattern. Add a diagram and explain the actual request flow and responsibilities. |
| Sessions and restricted functionality | Implemented; access checks verified | [request-helpers.php](C:/xampp2/htdocs/aviton/includes/request-helpers.php) uses HttpOnly/SameSite cookies, session ID regeneration, session expiry, CSRF and authorization. Login rotates the session ID; logout prevents subsequent protected reads. Expiry and password-change session revocation were code-reviewed, not time-simulated. |

## Borrowing workflow review

The implementation matches the later project decisions:

1. A USER chooses a positive whole-number quantity and requests equipment.
2. The pending request does not reduce stock.
3. ADMIN confirms physical release; the procedure reduces stock and records borrowed_at/released_by.
4. The borrower requests a return; stock is still on loan.
5. ADMIN confirms receipt; the procedure restores stock and records returned_at/received_by.

The procedures contain transactions, row locks, state checks and rollback handlers. A repeated borrow token is checked against the original request. Admins cannot borrow, and borrower actions check record ownership. The tested attempt to return an already returned loan was rejected without modifying data. Concurrent transactions and a full successful borrow/return cycle were not exercised during this audit.

There is no borrowing deadline. Due dates and damaged-item tracking occur in a suggested application example on PDF page 6; they are not universal minimum requirements. The supplied AviTON proposal does not require those features, and the user explicitly chose no deadline.

## Submission checklist

| Required submission, PDF page 15 | Found in supplied material? | Remaining work |
|---|---|---|
| Functional application and complete source | Yes, with the verification limits above | Rehearse a complete successful CRUD and borrow/return demonstration on a separate demo dataset. |
| Database structure/export | Yes: schema.sql plus PHP installation/population pages | Include all three setup files and the README. A separate data export is optional unless the instructor asks for one. |
| Database ERD | Not found | Draw user → records ← equipment; include the additional user relationships for released_by and received_by, key columns and cardinalities. |
| System architecture diagram | Not found | Show browser/forms → PHP endpoints → validation/authentication → Config → stored procedures/database, and the response path. |
| Short system documentation | Partly complete | README covers setup and workflow. Add the architecture/pattern explanation, OOP examples, JOIN purposes, security decisions, limitations and test evidence. |
| Group member list | Yes, proposal page 2 | Include the proposal or the same verified member list in the submission package. |
| Individual task assignments | Yes, proposal page 2 | The five technical roles are assigned; update them if actual responsibilities changed. |
| Evidence of individual contributions | Not found | Add Member / Assigned task / Completed work / Evidence. Use real source changes, dated records, test results or existing commits. Do not invent authorship or commit history. |
| Final presentation/demo | Not found in supplied material | Prepare and rehearse the 20-minute demonstration with participation from all five members. Slides alone do not establish technical contribution. |

The proposal already lists five members, so group formation and initial task assignment should not be reported as missing. It does not establish that the assigned work has been completed by each member. Originality and approval relative to other groups also cannot be established from the repository; confirm those with the instructor.

## Prioritized findings

1. **Complete the ERD and architecture diagram before submission.** Both are explicit deliverables, and neither was found in the project or proposal.
2. **Correct the architecture/design-pattern explanation.** SB Admin is the UI template, not the backend architecture or design pattern. Explain the existing layers and Config's data-access facade with a real code example. There is no PDF requirement to replace PHP with Laravel, Node or another application framework.
3. **Complete the contribution record and technical rehearsal.** The proposal contains assignments, but not completed-work evidence. Individual contribution/hands-on performance and presentation/recitation account for 25 of the 100 rubric points and cannot be evaluated through automated checks.
4. **Keep the current credentials confined to a local demo.** [config.php](C:/xampp2/htdocs/aviton/config/config.php:18) intentionally connects as root with an empty password. [populatedb.php](C:/xampp2/htdocs/aviton/database/populatedb.php:22) supplies known Admin/User sample passwords. Before real deployment, change sample passwords, use an appropriately restricted database account, require HTTPS and disable browser setup pages. These changes were not made because the current local settings were explicitly requested.
5. **Improve the connection-failure response when permitted.** [config.php](C:/xampp2/htdocs/aviton/config/config.php:33) uses the exact requested die("Error: Code 0001"). It avoids exposing credentials, but does not set HTTP 503 or return the normal AJAX response format. This is an error-handling limitation, not evidence that the currently working connection fails.

Also correct the proposal's description of POST as “information hiding”: POST keeps form fields out of the URL, but does not encrypt traffic. Password hashing, HTTPS and authorization serve different purposes.

## Verification performed

- All 26 PHP files passed syntax checks; all five authored JavaScript files passed syntax checks.
- 64 non-destructive checks passed: public/protected pages, source-file access restrictions, setup restrictions, field validation, invalid direct-save rejection, CSRF rejection, Admin/User login, session rotation, logout, dashboards, data reads, search/filtering, report permissions, admin borrowing prohibition, user administration prohibition and invalid quantity/state rejection.
- Before/after database row hashes matched. The database still has 2 users, 6 equipment entries and 1 borrowing record, with 4 foreign keys.
- Setup was not run, passwords were not changed, and no successful equipment or borrowing writes were performed.
- No full browser/device matrix, load test, concurrent-transaction test or instructor presentation assessment was performed during this audit.

No numerical grade is assigned. The implementation evidence supports much of the technical checklist, but missing submission artifacts and unverified individual/presentation performance prevent a defensible final score.
