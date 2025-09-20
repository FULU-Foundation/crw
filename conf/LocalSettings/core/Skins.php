<?php

# Load all supported skins for the wiki.
wfLoadSkin( 'MinervaNeue' );  // Mobile-friendly skin for responsive design.
wfLoadSkin( 'MonoBook' );     // Classic Wiki appearance.
wfLoadSkin( 'Timeless' );     // Modern minimalist skin.
wfLoadSkin( 'Vector' );       // Current Wikipedia-style skin.

# Skin configuration.
$wgDefaultSkin = "Vector-2022";  // Set the default skin to "Vector" (classic Wikipedia-style interface).
$wgDefaultMobileSkin = 'minerva'; // Use Minerva for mobile devices

$wgMinervaAdvancedMainMenu['base'] = true; // Advanced main menu

# Default wide mode for all users
$wgDefaultUserOptions['vector-limited-width'] = 0;
# Disable sidebar
$wgVectorDefaultSidebarVisibleForAnonymousUser = true;