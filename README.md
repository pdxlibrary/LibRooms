LibRooms
========

# Hosting Information
Platform: (PHP, MySQL, JavaScript) 
Public Open Source Repository: http://github.com/pdxlibrary/librooms
Status: Active (Since Fall 2012) 

# Software Dependencies: 
PEAR Modules (Required)
- DB
- MDB2
- Mail
- Mail_Mime
- Net_SMTP
- Net_Socket

PSU Library Hours Database
Hours data for the reservation calendar is drawn-in from the PSU Library Hours Database
https://github.com/pdxlibrary/Library-Hours-Admin

# Installation
1. copy source code to installation location from code repository
1. create a MySQL database for that application
1. load db/librooms.sql for the database table structure
1. edit "config/config.inc.php"
  - set values for database connection, email smtp server and web root location

# Accounts and Authentication
User accounts are created and synched from a User Authentication Source (III PatronAPI, Ex Libris Alma or CAS) when a user logs-in or a reservation is created for a user. User accounts can also be manually synched by admin users on the Users administrative screen  (<application_root>/manage_users.php).
Once a user account exists, the user may be given elevated permissions on the Users administrative screen (<application_root>/manage_users.php).
When a user logs-in their account will be synched with the User Authentication Source and updated with any changes.

# CSS Styling
- core styles: <application_root>/css/core.css
- calendar styles: <application_root>/css/calendar.css
- IE specific styles: <application_root>/css/core-ie.css

# Authentication
Can be set to use III PatronAPI, Ex Libris Alma or CAS as the Authentication Source

# Application Config
core config file: <application_root>/config/config.php

# Application Logging
Transactions table in database contains all database inserts and updates

# Controller Functions
All core functionality is done in: <application_root>/load.php

# E-mail Templates
Email templates for notices: <application_root>/email_templates/*

# Scheduled tasks 
/cron.php
Loads/updates reservation calendar hours, cancels reservations for no shows, sends overdue notices. Recommended Schedule: run every 15 minutes.
