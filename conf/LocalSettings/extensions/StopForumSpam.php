<?php

# StopForumSpam Configuration
wfLoadExtension( 'StopForumSpam' );

$wgSFSIPListLocation = 'https://www.stopforumspam.com/downloads/listed_ip_90_ipv46_all.gz';
$wgSFSValidateIPListLocationMD5 = 'https://www.stopforumspam.com/downloads/listed_ip_90_ipv46_all.gz.md5';

$wgSFSDenyListCacheDuration = 86400;  # 24 hours in seconds
$wgSFSReportOnly = false;             # Actually block, don't just report
