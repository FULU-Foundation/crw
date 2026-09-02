<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'extensions/ArticleFeedback',
		'extensions/ConfirmLogout',
		'extensions/ImgGuard',
		'extensions/MassRollback',
		'extensions/Plausible/includes',
		'LocalSettings',
	]
);

$cfg['file_list'] = array_merge(
	$cfg['file_list'] ?? [],
	[ 'LocalSettings.php' ]
);

return $cfg;
