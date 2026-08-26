<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
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

// AbuseFilter is parsed so Phan can resolve VariableHolder in ImgGuard's
// AbuseFilterHooks, but excluded from analysis: we are not linting a
// third-party extension, only using its types.
$cfg['directory_list'][] = 'extensions/AbuseFilter';
$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'] ?? [],
	[ 'extensions/AbuseFilter' ]
);

$cfg['file_list'] = array_merge(
	$cfg['file_list'] ?? [],
	[ 'LocalSettings.php' ]
);

return $cfg;
