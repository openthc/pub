<?php
/**
 * OpenTHC Pub Configuration
 */

$ret = [];

$ret['database'] = [
	'pub' => [
		'hostname' => 'localhost',
		'username' => 'openthc_pub',
		'password' => 'openthc_pub',
		'database' => 'openthc_pub',
	]
];

$ret['redis'] = [
	'hostname' => '10.4.20.69',
];

$ret['openthc'] = [];
$ret['openthc']['pub'] = [
	'origin' => 'https://pub.openthc.example',
	'public' => '',
	'secret' => '',
	'profile' => 'create', // 'create' | 'verify'
	'message' => 'create', // 'create' | 'verify'
];

return $ret;
