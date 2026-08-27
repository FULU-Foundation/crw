<?php

wfLoadExtension( 'TitleBlacklist' );

$wgTitleBlacklistSources = [
    [ 'type' => 'localpage', 'src' => 'MediaWiki:Titleblacklist' ],
    [ 'type' => 'url', 'src' => 'https://meta.wikimedia.org/w/index.php?title=Title_blacklist&action=raw' ],
];

$wgTitleBlacklistLogHits = true;
