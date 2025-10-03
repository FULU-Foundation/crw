<?php

$wgObjectCaches['redis'] = [
    'class'                => 'RedisBagOStuff',
    'servers'              => [ getenv('REDIS_SERVER') ],
];

$wgMainCacheType = 'redis';