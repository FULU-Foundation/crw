<?php

# These settings hide certain user preferences from the interface for privacy
$wgHiddenPrefs[] = 'realname';
$wgHiddenPrefs[] = 'language';

# Hides software version information from public view
$wgHideSoftwareVersion = true;

# Cap account registrations per IP per day.
$wgAccountCreationThrottle = [ [ 'count' => 3, 'seconds' => 86400 ] ];

# Extend existing IP blocks to also match IPs seen in X-Forwarded-For.
$wgApplyIpBlocksToXff = true;