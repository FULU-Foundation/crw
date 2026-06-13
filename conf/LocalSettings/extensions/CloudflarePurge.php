<?php

wfLoadExtension( 'CloudflarePurge' );

$wgCloudflarePurgeZoneID = getenv( 'CLOUDFLARE_PURGE_ZONE_ID' );
$wgCloudflarePurgeToken = getenv( 'CLOUDFLARE_PURGE_TOKEN' );