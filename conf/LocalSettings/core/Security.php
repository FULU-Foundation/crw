<?php

# These settings hide certain user preferences from the interface for privacy
$wgHiddenPrefs[] = 'realname';
$wgHiddenPrefs[] = 'language';

# Cap account registrations per IP per day.
$wgAccountCreationThrottle = [ [ 'count' => 3, 'seconds' => 86400 ] ];

# Extend existing IP blocks to also match IPs seen in X-Forwarded-For.
$wgApplyIpBlocksToXff = true;
