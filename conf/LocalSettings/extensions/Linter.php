<?php

# Linter works with Parsoid to track and display lint errors in the wiki.
wfLoadExtension( 'Linter' );

# Enable Linting in Parsoid's configuration.
# This ensures Parsoid reports lint errors for pages.
$wgParsoidSettings = [
    'linting' => true  // Enable linting in Parsoid.
];

# Enable linting to work alongside DiscussionTools and Parsoid.
$wgVisualEditorParsoidAutoConfig = false;