<?php

# Load the DiscussionTools extension.
# This enhances discussion pages with reply and topic features.
wfLoadExtension( 'DiscussionTools' );

# Enable DiscussionTools on all talk pages.
$wgDiscussionToolsEnable = true;

# Enable linting to work alongside DiscussionTools and Parsoid.
$wgVisualEditorParsoidAutoConfig = false;

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