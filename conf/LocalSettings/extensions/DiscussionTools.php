<?php

# Load the DiscussionTools extension.
# This enhances discussion pages with reply and topic features.
wfLoadExtension( 'DiscussionTools' );

# Configure Parsoid settings for DiscussionTools and Linter.
$wgVirtualRestConfig = [
    'modules' => [
        'parsoid' => [
            'url' => 'http://localhost:8000',  // Replace with your Parsoid service endpoint.
            'domain' => 'localhost',          // Set the correct domain for your wiki.
            'forwardCookies' => true
        ]
    ]
];
