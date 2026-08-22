<?php

// *
$wgGroupPermissions['*']['runcargoqueries'] = false;    # Prevent anonymous users
$wgGroupPermissions['*']['skipcaptcha'] = false;             // Anonymous users must complete CAPTCHA.
$wgGroupPermissions['*']['createpage'] = true;
$wgGroupPermissions['*']['usermerge'] = false;
$wgGroupPermissions['*']['editcontentmodel'] = false;        // Prevent anonymous users from changing content models.

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
$wgGroupPermissions['user']['editcontentmodel'] = false;      // Prevent users from changing content models.
$wgGroupPermissions['user']['spamblacklistlog'] = false;      // Restrict the spam blacklist log to sysops.
$wgGroupPermissions['user']['torunblocked'] = false;           // Override extension default. Blocked until AC.

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
$wgGroupPermissions['autoconfirmed']['editsemiprotected'] = false; // Prevent editing of semi-protected pages.
$wgGroupPermissions['autoconfirmed']['editcontentmodel'] = false;  // Prevent autoconfirmed users from changing content models.
$wgGroupPermissions['autoconfirmed']['edit-template'] = true; // Allows autoconfirmed users to edit templates in the Template namespace (NS_TEMPLATE).
$wgGroupPermissions['autoconfirmed']['sboverride'] = true; // Allow autoconfirmed users to bypass the spam blacklist.
$wgGroupPermissions['autoconfirmed']['torunblocked'] = true; // Allow bypassing Tor blocks.

// Confirmed
$wgGroupPermissions['confirmed'] = $wgGroupPermissions['autoconfirmed'];
$wgGroupPermissions['confirmed']['skipcaptcha'] = true;
$wgGroupPermissions['confirmed']['usermerge'] = false;
$wgGroupPermissions['confirmed']['move'] = true;             // Allow moving normal pages.
$wgGroupPermissions['confirmed']['move-subpages'] = true;    // Allow moving pages along with subpages.
$wgGroupPermissions['confirmed']['movefile'] = true;         // Allow moving uploaded files.
$wgGroupPermissions['confirmed']['move-categorypages'] = true; // Allow moving category pages.
$wgGroupPermissions['confirmed']['editcontentmodel'] = false; // Prevent confirmed users from changing content models.

// Super confirmed
$wgGroupPermissions['superconfirmed']['delete'] = true;          // Delete pages.
$wgGroupPermissions['superconfirmed']['undelete'] = true;        // Undelete pages
$wgGroupPermissions['superconfirmed']['editsemiprotected'] = true; // Edit semi-protected pages.
$wgGroupPermissions['superconfirmed']['sboverride'] = true; // Allow bypassing the spam blacklist.
$wgGroupPermissions['superconfirmed']['editcontentmodel'] = false; // Prevent superconfirmed users from changing content models.
$wgGroupPermissions['superconfirmed']['torunblocked'] = true; // Allow bypassing Tor blocks.

// Additionally can now remove site-notices

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
$wgGroupPermissions['sysop']['userrights'] = false; # See A/R Groups
$wgGroupPermissions['sysop']['skipcaptcha'] = true;          // Sysops are exempt from CAPTCHA.
$wgGroupPermissions['sysop']['sfsblock-bypass'] = true;
$wgGroupPermissions['sysop']['abusefilter-modify'] = true;
$wgGroupPermissions['sysop']['abusefilter-view'] = true;
$wgGroupPermissions['sysop']['abusefilter-log'] = true;
$wgGroupPermissions['sysop']['editinterface'] = true;
$wgGroupPermissions['sysop']['edit'] = true;
$wgGroupPermissions['sysop']['edit-cat'] = true; //allow sysop to edit
$wgGroupPermissions['sysop']['usermerge'] = false;
$wgGroupPermissions['sysop']['edituserjson'] = false;
$wgGroupPermissions['sysop']['editsitejson'] = false;
$wgGroupPermissions['sysop']['editcontentmodel'] = true;     // Allow sysops to change content models.
$wgGroupPermissions['sysop']['spamblacklistlog'] = true;     // View the spam blacklist log.
$wgGroupPermissions['sysop']['manageawards'] = true;        // Issue and revoke awards.
$wgGroupPermissions['sysop']['sboverride'] = true;            // Bypass the spam blacklist.
$wgGroupPermissions['sysop']['protectsite'] = true;           // Activate and deactivate site protection.
$wgGroupPermissions['sysop']['torunblocked'] = true;          // Allow bypassing Tor blocks.

$wgAddGroups['sysop'] = ['confirmed'];
$wgRemoveGroups['sysop'] = ['confirmed'];

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
$wgGroupPermissions['superadmin']['blocksysop'] = true; # Custom logic to allow superadmins to block/ban normal sysops
$wgGroupPermissions['superadmin']['skipcaptcha'] = true;     // Superadmins are exempt from CAPTCHA.
$wgGroupPermissions['superadmin']['usermerge'] = true;
$wgGroupPermissions['superadmin']['editcontentmodel'] = true;    // Allow superadmins to change content models.
$wgGroupPermissions['superadmin']['spamblacklistlog'] = true;    // View the spam blacklist log.
$wgGroupPermissions['superadmin']['manageawards'] = true;        // Issue and revoke awards.
$wgGroupPermissions['superadmin']['massrollback'] = true;        // Mass rollback a user's edits.
$wgGroupPermissions['superadmin']['sboverride'] = true;          // Bypass the spam blacklist.
$wgGroupPermissions['superadmin']['protectsite'] = true;         // Activate and deactivate site protection.
$wgGroupPermissions['superadmin']['torunblocked'] = true;        // Allow bypassing Tor blocks.
$wgGroupPermissions['superadmin']['checkuser'] = true;            // Check IP addresses/usernames via CheckUser.
$wgGroupPermissions['superadmin']['checkuser-log'] = true;        // View the CheckUser action log.
$wgGroupPermissions['superadmin']['checkuser-temporary-account'] = true; // Reveal IPs behind temporary accounts.
$wgGroupPermissions['superadmin']['checkuser-temporary-account-log'] = true; // View temporary account CheckUser log.

// Interface admin
$wgGroupPermissions['interface-admin']['editsitecss'] = true;  // Interface admins control site-wide CSS.
$wgGroupPermissions['interface-admin']['gadgets-edit'] = true;
$wgGroupPermissions['interface-admin']['gadgets-definition-edit'] = true;
$wgGroupPermissions['interface-admin']['usermerge'] = false;
$wgGroupPermissions['interface-admin']['editcontentmodel'] = true; // Allow interface admins to change content models.

// Bureaucrat
$wgGroupPermissions['bureaucrat']['usermerge'] = false;
$wgGroupPermissions['bureaucrat']['protectsite'] = false;

// Site protection exempt groups
$wgProtectSiteExempt = [ 'autoconfirmed', 'confirmed', 'superconfirmed', 'sysop', 'superadmin' ];

// Suppress
$wgGroupPermissions['suppress']['usermerge'] = false;
