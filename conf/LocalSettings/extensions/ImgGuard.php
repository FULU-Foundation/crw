<?php

wfLoadExtension( 'ImgGuard' );

$wgImgGuardEnforce = true;
$wgImgGuardFailClosed = true;
$wgImgGuardSfwThreshold = 0.5;
$wgRateLimits['imgguard']['user'] = [ 20, 600 ];
