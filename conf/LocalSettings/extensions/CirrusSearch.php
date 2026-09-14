<?php

wfLoadExtension( 'CirrusSearch' );

# Search servers from SEARCH_SERVER
$wgCirrusSearchServers = [];
foreach ( explode( ',', getenv('SEARCH_SERVER') ?: '' ) as $server ) {
    $server = trim( $server );
    if ( $server === '' ) {
        continue;
    }
    $parts = explode( ':', $server, 2 );
    $wgCirrusSearchServers[] = [ 'host' => $parts[0], 'port' => (int)( $parts[1] ?? 9200 ) ];
}

$wgSearchType = 'CirrusSearch';
