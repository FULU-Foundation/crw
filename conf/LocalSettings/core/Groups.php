<?php

// *
$wgGroupPermissions['*']['runcargoqueries'] = false;    # Prevent anonymous users
$wgGroupPermissions['*']['skipcaptcha'] = false;             // Anonymous users must complete CAPTCHA.
$wgGroupPermissions['*']['createpage'] = true;
$wgGroupPermissions['*']['usermerge'] = false;

// Bot
$wgGroupPermissions['bot']['usermerge'] = false;
$wgGroupPermissions['bot']['runcargoqueries'] = true; # Allows bot users to run Cargo queries (Cargo-bot)

// Normal user
$wgGroupPermissions['user']['usermerge'] = false;
$wgGroupPermissions['user']['createpage'] = true;
$wgGroupPermissions['user']['runcargoqueries'] = true;  # Allow registered users
$wgGroupPermissions['user']['upload'] = true; // Allow registered users to upload files
$wgGroupPermissions['user']['skipcaptcha'] = false;          // Registered users must also complete CAPTCHA.
$wgGroupPermissions['user']['editsitecss'] = false;  // Normal users cannot edit site CSS.
$wgGroupPermissions['user']['move'] = false;                  // Prevent moving normal pages.
$wgGroupPermissions['user']['move-subpages'] = false;         // Prevent moving pages along with subpages.
$wgGroupPermissions['user']['movefile'] = false;              // Prevent moving uploaded files.
$wgGroupPermissions['user']['move-categorypages'] = false;    // Prevent moving category pages.

// Automatically confirmed 
$wgAutoConfirmAge = 86400*7; // seven days
$wgAutoConfirmCount = 10;

$wgGroupPermissions['autoconfirmed']['skipcaptcha'] = true; // Allow skipping captcha
$wgGroupPermissions['autoconfirmed']['templateeditor'] = true;
$wgGroupPermissions['autoconfirmed']['edit'] = true;
$wgGroupPermissions['autoconfirmed']['move'] = true;          // Allow moving normal pages.
$wgGroupPermissions['autoconfirmed']['move-subpages'] = true; // Allow moving pages along with subpages.
$wgGroupPermissions['autoconfirmed']['movefile'] = true;      // Allow moving uploaded files.
$wgGroupPermissions['autoconfirmed']['move-categorypages'] = true; // Allow moving category pages.
$wgGroupPermissions['autoconfirmed']['usermerge'] = false;

// Confirmed
$wgGroupPermissions['confirmed'] = $wgGroupPermissions['autoconfirmed'];
$wgGroupPermissions['confirmed']['skipcaptcha'] = true;
$wgGroupPermissions['confirmed']['usermerge'] = false;
$wgGroupPermissions['confirmed']['move'] = true;             // Allow moving normal pages.
$wgGroupPermissions['confirmed']['move-subpages'] = true;    // Allow moving pages along with subpages.
$wgGroupPermissions['confirmed']['movefile'] = true;         // Allow moving uploaded files.
$wgGroupPermissions['confirmed']['move-categorypages'] = true; // Allow moving category pages.

// Sysop
$wgGroupPermissions['sysop']['runcargoqueries'] = true; # Allow administrators
$wgGroupPermissions['sysop']['block'] = true;             // Block and unblock disruptive users.
$wgGroupPermissions['sysop']['delete'] = true;           // Delete pages.
$wgGroupPermissions['sysop']['protect'] = true;          // Protect and unprotect pages to control editing access.
$wgGroupPermissions['sysop']['rollback'] = true;         // Rollback edits to revert vandalism quickly.
$wgGroupPermissions['sysop']['editprotected'] = true;    // Edit pages that have been protected.
$wgGroupPermissions['sysop']['move'] = true;             // Move pages to new titles.
$wgGroupPermissions['sysop']['managechangetags'] = true; // Manage edit tags for categorization.
$wgGroupPermissions['sysop']['smitespam'] = true;        // Use SmiteSpam extension for spam cleanup.
$wgGroupPermissions['sysop']['abusefilter-modify'] = true; # Modify abuse filters to combat vandalism.
$wgGroupPermissions['sysop']['abusefilter-log'] = true;   # View logs of abuse filter activity.
$wgGroupPermissions['sysop']['abusefilter-view'] = true;  # View the list of active abuse filters.
$wgGroupPermissions['sysop']['abusefilter-privatedetails'] = true; # View private details in abuse filters.
$wgGroupPermissions['sysop']['abusefilter-privatedetails-log'] = true; # View private logs of abuse filter activity.
$wgGroupPermissions['sysop']['userrights'] = false;  // Prevent sysops from assigning or modifying user roles.
$wgGroupPermissions['sysop']['skipcaptcha'] = true;          // Sysops are exempt from CAPTCHA.
$wgGroupPermissions['sysop']['sfsblock-bypass'] = true;
$wgGroupPermissions['sysop']['abusefilter-modify'] = true;
$wgGroupPermissions['sysop']['abusefilter-view'] = true;
$wgGroupPermissions['sysop']['abusefilter-log'] = true;
$wgGroupPermissions['sysop']['editinterface'] = true;
$wgGroupPermissions['sysop']['edit'] = true;
$wgGroupPermissions['sysop']['edit-cat'] = true; //allow sysop to edit
$wgGroupPermissions['sysop']['usermerge'] = false;


// Super admin
$wgGroupPermissions['superadmin']['block'] = true;               // Block and unblock users, including sysops.
$wgGroupPermissions['superadmin']['userrights'] = true;          // Manage roles and permissions for all users.
$wgGroupPermissions['superadmin']['delete'] = true;              // Delete pages.
$wgGroupPermissions['superadmin']['protect'] = true;             // Protect and unprotect pages.
$wgGroupPermissions['superadmin']['editprotected'] = true;       // Edit protected pages.
$wgGroupPermissions['superadmin']['editinterface'] = true;       // Edit interface pages (e.g., system messages).
$wgGroupPermissions['superadmin']['suppressrevision'] = true;    // Suppress revisions from public view.
$wgGroupPermissions['superadmin']['smitespam'] = true;           // Use SmiteSpam for spam control.
$wgGroupPermissions['superadmin']['managechangetags'] = true;    // Manage edit tags for content categorization.
$wgGroupPermissions['superadmin']['abusefilter-modify'] = true;  # Modify abuse filters.
$wgGroupPermissions['superadmin']['abusefilter-log'] = true;     # View abuse filter logs.
$wgGroupPermissions['superadmin']['abusefilter-view'] = true;    # View abuse filters.
$wgGroupPermissions['superadmin']['abusefilter-privatedetails'] = true; # View private abuse filter details.
$wgGroupPermissions['superadmin']['abusefilter-privatedetails-log'] = true; # View private logs of abuse filter activity.
$wgGroupPermissions['superadmin']['handle-pii'] = true; # Remove PII
$wgGroupPermissions['superadmin']['blocksysop'] = true; # Custom logic to allow superadmins like dog or christoph who are trusted to block/ban normal sysops(mods) that get out of line
$wgGroupPermissions['superadmin']['skipcaptcha'] = true;     // Superadmins are exempt from CAPTCHA.
$wgGroupPermissions['superadmin']['usermerge'] = true;

// Interface admin
$wgGroupPermissions['interface-admin']['editsitecss'] = true;  // Interface admins control site-wide CSS.
$wgGroupPermissions['interface-admin']['gadgets-edit'] = true;
$wgGroupPermissions['interface-admin']['gadgets-definition-edit'] = true;
$wgGroupPermissions['interface-admin']['usermerge'] = false;

// Bureaucrat
$wgGroupPermissions['bureaucrat']['usermerge'] = false;

// Suppress
$wgGroupPermissions['suppress']['usermerge'] = false;