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

$ret['openthc'] = [];
$ret['openthc']['pub'] = [
	'origin' => 'https://pub.openthc.example',
	'public' => '',
	'secret' => '',
];

return $ret;
