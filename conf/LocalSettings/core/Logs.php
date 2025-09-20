<?php

if(getenv('WIKI_ENV') == "Dev") {
    ini_set('display_errors', False);
    $wgDebugToolbar = true;
}