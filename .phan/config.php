<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'extensions/AbuseFilter',
		'extensions/ArticleFeedback',
		'extensions/Awards',
		'extensions/ConfirmLogout',
		'extensions/ImgGuard',
		'extensions/MassRollback',
		'extensions/Plausible/includes',
		'extensions/UserImpact',
		'LocalSettings',
	]
);

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'] ?? [],
	[ 'extensions/AbuseFilter' ]
);

$cfg['file_list'] = array_merge(
	$cfg['file_list'] ?? [],
	[ 'LocalSettings.php' ]
);

return $cfg;
