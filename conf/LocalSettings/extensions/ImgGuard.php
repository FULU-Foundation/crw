<?php

wfLoadExtension( 'ImgGuard' );

$wgImgGuardEnforce = true;
$wgImgGuardFailClosed = true;
$wgImgGuardSfwThreshold = 0.5;
$wgImgGuardScriptPath = '/var/www/html/extensions/ImgGuard/bin/classify.py';
$wgImgGuardLogPasses = true;
$wgImgGuardThreads = 4;
$wgImgGuardMaxConcurrent = 3;
$wgImgGuardAutoBlockEnabled = true;