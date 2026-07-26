<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'extensions/ArticleFeedback',
		'extensions/Awards',
		'extensions/ConfirmLogout',
		'extensions/MassRollback',
		'extensions/SiteLockdown',
		'extensions/UserImpact',
		'LocalSettings',
	]
);

$cfg['file_list'] = array_merge(
	$cfg['file_list'] ?? [],
	[ 'LocalSettings.php' ]
);

return $cfg;
