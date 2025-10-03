<?php
ini_set('display_errors', False);

if(getenv('WIKI_ENV') == "Dev") {
    ini_set('display_errors', True);
    $wgDebugToolbar = true;
    $wgShowExceptionDetails = true;
}