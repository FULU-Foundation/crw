<?php

# Load the Echo extension to enable notifications.
wfLoadExtension( 'Echo' );

# Enable batch email processing for notifications.
$wgEchoEnableEmailBatch = true;  // Group notifications into a single email.

# Email frequency preferences for users.
$wgDefaultUserOptions['echo-email-frequency'] = 1;  // Daily email summary.

# Set notification preferences for new users.
$wgDefaultUserOptions['echo-subscriptions-email-mention'] = true;  // Email notifications for mentions.
$wgDefaultUserOptions['echo-subscriptions-web-mention'] = true;    // Web notifications for mentions.
