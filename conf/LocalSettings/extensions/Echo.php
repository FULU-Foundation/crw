<?php

# Load the Echo extension to enable notifications.
wfLoadExtension( 'Echo' );

# Configure notification sender email.
$wgNotificationSender = "notifications@rossmanngroup.com";  // Sender email for notifications.
$wgNotificationSenderName = 'consumer_defense_wiki';   // Name displayed as sender in email.

# Enable batch email processing for notifications.
$wgEchoEnableEmailBatch = true;  // Group notifications into a single email.

# Enable new message alerts for talk pages.
$wgEchoNewMsgAlert = true;  // Notify users of new talk page messages.

# Email frequency preferences for users.
$wgDefaultUserOptions['echo-email-frequency'] = 1;  // Daily email summary.

# Set notification preferences for new users.
$wgDefaultUserOptions['echo-subscriptions-email-mention'] = true;  // Email notifications for mentions.
$wgDefaultUserOptions['echo-subscriptions-web-mention'] = true;    // Web notifications for mentions.