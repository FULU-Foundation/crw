<?php

# AbuseFilter Configuration
wfLoadExtension( 'AbuseFilter' );

$wgAbuseFilterActions = [
    'warn' => true,
    'disallow' => true,
    'blockautopromote' => true,
    'block' => true,
    'degroup' => true,
    'tag' => true,
    'throttle' => true
];