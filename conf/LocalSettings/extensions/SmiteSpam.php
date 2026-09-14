<?php

# SmiteSpam Configuration with enhanced settings
wfLoadExtension( 'SmiteSpam' );
$wgSmiteSpamThreshold = 0.3;                  // More aggressive threshold (was 0.4)
$wgSmiteSpamIgnoreSmallPages = false;
