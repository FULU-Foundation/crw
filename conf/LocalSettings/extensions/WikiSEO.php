<?php

wfLoadExtension( 'WikiSEO' );

$wgWikiSeoEnableAutoDescription = true;
$wgWikiSeoTryCleanAutoDescription = true;
$wgWikiSeoDefaultLanguage = "en-us";

# Default social preview image for OpenGraph (og:image) when a page has no specific image.
# Ideal: 1200x630px branded image. Using site logo as interim until a proper social image is uploaded.
# TODO: Replace with a dedicated 1200x630 social preview image once created and uploaded.
$wgWikiSeoDefaultImage = '/images/logo/new_fixed_logo.png';
