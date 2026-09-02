<?php

wfLoadExtension( 'ImgGuard' );

$wgImgGuardEnforce = true;
$wgImgGuardFailClosed = true;
$wgImgGuardSfwThreshold = 0.5;
$wgImgGuardScriptPath = '/var/www/html/extensions/ImgGuard/bin/classify.py';
$wgImgGuardLogPasses = true;
$wgImgGuardMaxConcurrent = 6;
