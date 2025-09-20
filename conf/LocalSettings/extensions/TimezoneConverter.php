<?php

# Load the TimezoneConverter extension.
# This extension dynamically replaces dates and times in wiki pages to show them
# in the local timezone of the reader, based on their account preferences.
wfLoadExtension( 'TimezoneConverter' );

# Configure custom datetime formats (optional).
# This array defines the available date/time formats that can be used with the extension.
# Each format has a PHP-side configuration and a JavaScript-side configuration.
$wgTimezoneConverterFormats = [
    // Default formats that come with the extension
    'time' => [
        'php' => 'g:iA',                    // Example: 3:45PM (PHP time format)
        'js' => ['hour' => 'numeric', 'minute' => 'numeric', 'hour12' => true]  // JS time options
    ],
    'exact' => [
        'php' => 'Y F j (l), g:iA',         // Example: 2025 March 6 (Thursday), 11:56AM
        'js' => [
            'year' => 'numeric', 'month' => 'long', 'day' => 'numeric',
            'weekday' => 'long', 'hour' => 'numeric', 'minute' => 'numeric',
            'hour12' => true
        ]
    ],
    // Custom format for short dates (added for wiki consistency)
    'short' => [
        'php' => 'Y-m-d H:i',               // Example: 2025-03-06 11:56
        'js' => [
            'year' => 'numeric', 'month' => '2-digit', 'day' => '2-digit',
            'hour' => '2-digit', 'minute' => '2-digit', 'hour12' => false
        ]
    ]
];

# Enable diagnostic logging for the extension (uncomment if needed)
# $wgDebugLogGroups['TimezoneConverter'] = "/var/log/mediawiki/timezone.log";
?>
