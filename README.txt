Hipolito, Claus Marvin -              Project Lead / Architecture
Carreon, Baskin Robin  -              Database / Backend
Mercado, Christine Joy A. -           Core Features
Navarrete, Ma. Julia Sophia L. -      Frontend / Integration
Sister, Nash S. -                     Authentication / Security



AVITON - AVIATION EQUIPMENT INVENTORY & BORROWING SYSTEM
=========================================================
AviTON is our system for borrowing aviation equipment (headsets, GPS
units, tool kits, safety gear, etc.) without needing a paper logbook.
Regular users request the equipment they need, and staff (admin)
confirm when it's actually handed over and returned. No due dates,
no overdue tracking - just request, release, return, receive.


HOW TO SET IT UP?

1. Put the project folder here:
   C:/xampp2/htdocs/aviton/

2. Start Apache and MySQL in XAMPP.

3. In your browser, open these two links:
   
First Open this on URL 
   http://localhost/aviton/database/createdb.php
   -this builds the database and installs everything

Then, Second open this also in URL
   http://localhost/aviton/database/populatedb.php
   -this adds the sample accounts and sample equipment

4. Then just go to:
   http://localhost/aviton/

A few things to know:
- Those two setup links only work if you open them on the same PC
  running XAMPP. They won't work from another computer.

- It's safe to run them again later - it won't delete anything,
  it just adds whatever's missing.

- If setup doesn't work, check that MySQL is actually running, and
  that config/config.php still has:
  host=localhost
  db=inventorydb
  user=root
  AND no password.


 LOGIN DETAILS

  Role    Username   Password     
  ADMIN    Admin      Admin
  USER     User       User

These only show up after populatedb.php runs. Just demo logins for
testing, passwords are still hashed, not stored as plain
text. Change both before this ever goes live for real.

(Yeah these two are shorter than the usual password rule - that's on
purpose, just for the two demo accounts. Real registered accounts
still need 12+ characters.)




USING IT AS A USER

- Sign in, or hit "Create an account" if you're new.

- Go to "Borrow Item" - search for what you need, pick a quantity,
  and submit. This doesn't take anything out of stock yet, it just
  puts your request in the queue for staff to approve.

- Check "My Borrowed Items" to see where your request is at:
    PENDING         - waiting on staff to release it
    BORROWED        - you have it
    RETURN_PENDING  - you sent it back, waiting on staff to confirm
    RETURNED        - done, loan closed
    REJECTED        - staff said no
    CANCELLED       - you cancelled it

- When you're done with an item, hit "Request Return" on that
  record. Stock only goes back up once staff confirms they got it.

- You can change your password anytime under "Account."

Note: Admins can't borrow stuff through their Admin account, so if a
staff member wants to borrow something too, they need their own
separate USER account.


USING IT AS AN ADMIN

- Log in with an ADMIN account, you'll land on the Admin Dashboard.

- "Admin Monitor" - this is where you see every borrow/return request
  from everyone. Confirm release once you've actually handed the item
  over (this is what drops the available stock). Confirm receipt once
  you get it back (this restores the stock). You can also reject a
  request, just give a reason.

- "Equipment Management" - add, edit, or delete equipment. You can't
  delete something if it's ever been borrowed - keeps the history
  intact.

- "Reports & Joins" - three built-in reports (equipment usage, user
  activity, and a combined one).

- "Account" - same password change option as users get.




RULES TO KEEP IN MIND

- Only USER accounts can borrow. Admins can't.
- Quantities have to be whole numbers.
- No due dates, no late tracking.
- A request doesn't touch stock - only when staff actually confirms
  release or return does the number change.
- Returns are all-or-nothing for the quantity on that record.
- Can't delete equipment that's ever had a borrow record.




IF SOMETHING BREAKS

"Error: Code 0001" - MySQL's probably not running, or createdb.php
hasn't been run yet.

Can't reach MySQL - start it from the XAMPP panel and reload.

Equipment list is empty - you probably skipped populatedb.php.

Locked out after a few failed logins - just wait a bit, that's
intentional (stops repeated password guessing).
