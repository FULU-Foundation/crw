<?php

# Enable and configure email functionality for the wiki.
$wgEnableEmail = true; // Activate email functionality in the wiki.
$wgEnableUserEmail = true; // Allow users to send emails to each other via the wiki interface.

# Administrative email addresses
$wgEmergencyContact = '';
$wgPasswordSender = '';
$wgEmailFrom = '';

# Email notification preferences
$wgEnotifUserTalk = false; // Disable email notifications for user talk page changes (reduces server load).
$wgEnotifWatchlist = false; // Disable email notifications for watchlist changes (reduces unnecessary notifications).
$wgEmailAuthentication = true; // Require email address verification for additional account functionality (enhances security).

# Configure Postmark SMTP for sending emails.
$wgSMTP = [
    'host' => '',
    'port' => 587,
    'auth' => true,
    'username' => '',
    'password' => ''
];

# Enable email functionality and user communication features.
$wgEnableEmail = true;          // Activate email functionality.
$wgEnableUserEmail = true;      // Allow users to email each other through the wiki.
$wgEmailAuthentication = true;  // Require email authentication for account actions.

# Optional: Enable HTML-formatted emails for better readability and design.
$wgAllowHTMLEmail = true;