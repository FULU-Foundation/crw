<?php

# Memory Limit
ini_set('memory_limit', '512M'); // Increase memory limit to handle large operations, such as processing large images.


# Exception and Error Details
$wgShowExceptionDetails = false;    // Display detailed exception information for easier troubleshooting.
$wgShowDBErrorBacktrace = false;    // Show backtraces for database errors to assist with debugging complex queries.
$wgDevelopmentWarnings = false;     // Enable development-related warnings to identify potential issues in code.

# ResourceLoader Debugging
$wgResourceLoaderDebug = false; // Disable ResourceLoader debugging by default to reduce unnecessary logging.
$wgResourceLoaderMaxage['versioned'] = 0; // Set maximum cache age for versioned resources to 0 (improves debugging accuracy).

# Main Debug Log File
$wgDebugLogFile = "/var/log/mediawiki/debug.log"; // Central log file for general debugging information.

# Debug Log Groups
$wgDebugLogGroups['exception'] = '/var/log/mediawiki/exception.log'; // Log all exceptions to a dedicated file.
$wgDebugLogGroups['error'] = '/var/log/mediawiki/error.log';         // Log all errors to a separate file.
