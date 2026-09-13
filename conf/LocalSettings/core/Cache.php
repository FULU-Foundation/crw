<?php

if(getenv('REDIS_SERVER')) {
    $wgObjectCaches['redis'] = [
        'class'      => 'RedisBagOStuff',
        'servers'    => [ getenv('REDIS_SERVER') ],
        'persistent' => true,
        'loggroup'   => 'redis',
    ];
    $wgMainCacheType = 'redis';
    $wgParserCacheType = 'redis';
} else {
    $wgMainCacheType = CACHE_ACCEL;
}

$wgCacheDirectory = "/var/cache/mediawiki";
