<?php

wfLoadExtension( 'CirrusSearch' );

$wgCirrusSearchServers = [
	[ 'host' => 'crw-local-opensearch' ]
];

$wgSearchType = 'CirrusSearch';