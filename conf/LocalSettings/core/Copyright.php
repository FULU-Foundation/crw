<?php

# Settings for the Creative Commons Attribution-ShareAlike 4.0 International license.
# This determines how licensing information is displayed and handled in the wiki.

$wgRightsText = "Creative Commons Attribution-ShareAlike 4.0 International";  // The license name displayed to users.
$wgRightsUrl = "https://creativecommons.org/licenses/by-sa/4.0/";            // URL linking to the full license details.
$wgRightsIcon = "{$wgScriptPath}/images/cc-by-sa.svg";                       // Path to the license icon (e.g., Creative Commons logo).
$wgRightsPage = "Project:Copyright";                                         // Internal wiki page explaining the licensing terms.

# Enable RDF metadata for the Creative Commons license.
# This adds machine-readable metadata, improving integration with search engines and compliance tools.
$wgEnableCreativeCommonsRdf = true;