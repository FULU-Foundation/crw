<?php

$wgAutoCreateTempUser = [
	'known' => false,
	'enabled' => true,
	'actions' => [ 'edit' ],
	'genPattern' => '~$1',
	'matchPattern' => null,
	'reservedPattern' => '~$1',
	'serialProvider' => [
		'type' => 'local',
		'useYear' => true,
	],
	'serialMapping' => [
		'type' => 'readable-numeric',
	],
	'expireAfterDays' => 90,
	'notifyBeforeExpirationDays' => 10,
];
