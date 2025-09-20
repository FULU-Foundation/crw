<?php

# StopForumSpam Configuration
wfLoadExtension( 'StopForumSpam' );

# Configure StopForumSpam list location
# Store in persistent Docker volume
$wgSFSIPListLocation = "$IP/cache/stopforumspam/listed_ip_30_all.txt";
$wgSFSDenyListCacheDuration = 86400;  # 24 hours in seconds
$wgSFSReportOnly = false;             # Actually block, don't just report
