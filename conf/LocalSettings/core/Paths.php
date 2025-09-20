<?php

## The URL base path to the directory containing the wiki;
## defaults for all runtime URL paths are based off of this.
## For more information on customizing the URLs
## (like /w/index.php/Page_title to /wiki/Page_title) please see:
## https://www.mediawiki.org/wiki/Manual:Short_URL
$wgScriptPath = "";

# Article path
$wgArticlePath = "/w/$1";

#pathinfo
$wgUsePathInfo = true;

## The protocol and server name to use in fully-qualified URLs
$wgServer = getenv('FULL_URL');
$wgCanonicalServer = getenv('CANONICAL_URL');

## The URL path to static resources (images, scripts, etc.)
$wgResourceBasePath = $wgScriptPath;

$wgUploadDirectory = "/var/www/html/images/";