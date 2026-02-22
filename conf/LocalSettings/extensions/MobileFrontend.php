<?php

wfLoadExtension('MobileFrontend');

$wgMFAutodetectMobileView = true; // Enable automatic mobile view detection
$wgMFDefaultSkinClass = 'SkinMinerva'; // Ensures MobileFrontend uses the Minerva skin

// Desktop behaviour
$wgMinervaTalkAtTop['base'] = true;
$wgMinervaAdvancedMainMenu['base'] = true;
$wgMinervaPersonalMenu['base'] = true;
$wgMinervaHistoryInPageActions['base'] = true;
$wgMinervaOverflowInPageActions['base'] = true;
$wgMinervaShowCategories['base'] = true;
