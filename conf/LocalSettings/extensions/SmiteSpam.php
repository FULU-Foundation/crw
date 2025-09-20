<?php

# SmiteSpam Configuration with enhanced settings
wfLoadExtension( 'SmiteSpam' );
$wgSmiteSpamThreshold = 0.3;                  // More aggressive threshold (was 0.4)
$wgSmiteSpamIgnoreSmallPages = false;
$wgSmiteSpamAutoBlock = true;
$wgSmiteSpamBlockDuration = 'infinite';       // Permanent blocks for detected spammers
$wgSmiteSpamLogActions = true;                // Log all actions for review
