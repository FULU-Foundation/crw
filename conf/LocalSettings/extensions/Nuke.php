<?php

# Load the Nuke extension.
# Allows administrators to mass delete pages, particularly useful for spam cleanup.
wfLoadExtension( 'Nuke' );

# Optional: Limit the maximum age of pages that can be nuked (in seconds)
# By default, Nuke will only list pages as recent as what is shown on Special:RecentChanges
# This sets it to 30 days
$wgNukeMaxAge = 30 * 86400;