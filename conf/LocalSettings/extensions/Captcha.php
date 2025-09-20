<?php
wfLoadExtensions([ 'ConfirmEdit', 'ConfirmEdit/hCaptcha' ]);

# hCaptcha Configuration
$wgHCaptchaSiteKey = getenv('CAPTCHA_SITEKEY');
$wgHCaptchaSecretKey = getenv('CAPTCHA_SECRETKEY');

# Privacy Settings
$wgHCaptchaSendRemoteIP = false; // Prevent user IPs from being sent to hCaptcha (enhances privacy).

# Ensure CAPTCHA applies to "new-section" (Talk pages)
$wgCaptchaTriggers = [
    'edit' => true,           // CAPTCHA for normal edits
    'create' => true,         // CAPTCHA for creating new pages
    'addurl' => true,         // CAPTCHA for adding external links
    'createaccount' => true,  // CAPTCHA for account creation
    'badlogin' => true,       // CAPTCHA for failed login attempts
    'badloginperuser' => true, // CAPTCHA after repeated failed logins
    'new-section' => true      // ✅ Force CAPTCHA for starting a new topic
];

# Fix: Explicitly Apply CAPTCHA to Talk Pages
$wgCaptchaTriggersOnNamespace = [
    NS_TALK => true,                  # Normal Talk Pages
    NS_USER_TALK => true,             # User Talk Pages
    NS_PROJECT_TALK => true,          # Project Talk Pages
    NS_PRIMARY_TALK => true           # Custom namespace talk page
];


# CAPTCHA Storage and Application
$wgCaptchaWhitelist = [];                        // No whitelisted actions or users for CAPTCHA bypass.

# 🛠 FIX: Ensure DiscussionTools properly handles CAPTCHA
$wgDiscussionToolsEnable = true; // Keep enabled
$wgDiscussionToolsEnableTopicSubscription = true; // Allow users to subscribe to topics
$wgVisualEditorParsoidAutoConfig = false; // Prevent conflicts with CAPTCHA handling