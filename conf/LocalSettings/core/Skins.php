<?php

# Load all supported skins for the wiki.
wfLoadSkin( 'MinervaNeue' );  // Mobile-friendly skin for responsive design.
wfLoadSkin( 'Vector' );       // Current Wikipedia-style skin.

# Skin configuration.
$wgDefaultSkin = "Vector-2022";  // Set the default skin to "Vector" (classic Wikipedia-style interface).
# Default to light mode
$wgDefaultUserOptions['vector-theme'] = 'day';
# Use Minerva for mobile devices
$wgDefaultMobileSkin = 'minerva'; // Use Minerva for mobile devices

$wgMinervaAdvancedMainMenu['base'] = true; // Advanced main menu

$wgDefaultUserOptions['vector-limited-width'] = 1;

$wgVectorMaxWidthOptions = [ 'exclude' => [ 'mainpage' => false, 'querystring' => [], 'namespaces' => [] ], 'include' => [] ];
$wgVectorFontSizeConfigurableOptions = [ 'exclude' => [ 'mainpage' => true, 'querystring' => [ 'title' => '.*' ] ], 'include' => [] ];
$wgVectorNightModeOptions = [ 'exclude' => [ 'mainpage' => true, 'querystring' => [ 'title' => '.*' ] ], 'include' => [] ];

$wgHiddenPrefs[] = 'vector-limited-width';
$wgHiddenPrefs[] = 'vector-font-size';
$wgHiddenPrefs[] = 'vector-theme';

$wgAllowSiteCSSOnRestrictedPages = true;
