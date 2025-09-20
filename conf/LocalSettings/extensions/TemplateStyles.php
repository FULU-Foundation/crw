<?php

# Load the TemplateStyles extension
wfLoadExtension( 'TemplateStyles' );

# Security Considerations:
# - Restricts inline CSS to predefined styles.
# - Prevents arbitrary user CSS injection (avoids security issues like XSS).
# - Ensures consistent styling across the wiki while allowing maintainers control.

# Enable TemplateStyles in all namespaces to allow controlled CSS styling.

$wgTemplateStylesNamespaces = [
    NS_MAIN,                  // Main wiki namespace
    NS_PROJECT,               // Project pages
    NS_PRIMARY,               // Custom namespace
    NS_TEMPLATE,              // Templates namespace
    NS_HELP                   // Help documentation
];

# Enforce moderation before publishing major template styles
$wgTemplateStylesEnableReview = true;  // Requires review before deployment

# Debugging and Logging for TemplateStyles
$wgDebugLogGroups['templatestyles'] = "/var/log/mediawiki/templatestyles.log";

