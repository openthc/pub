<?php
/**
 * OpenTHC Pub
 *
 * SPDX-License-Identifier: MIT
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

openlog('openthc-pub', LOG_ODELAY|LOG_PID, LOG_LOCAL0);

define('APP_ROOT', __DIR__);

require_once(APP_ROOT . '/vendor/autoload.php');

if ( ! \OpenTHC\Config::init(APP_ROOT) ) {
	_exit_html_fail('<h1>Invalid Application Configuration [ABS-015]</h1>', 500);
}

define('OPENTHC_PUB_SK', \OpenTHC\Config::get('pub/secret'));
define('OPENTHC_PUB_PK', \OpenTHC\Config::get('pub/public'));

/**
 * base64 Helper
 */
function enb64($x)
{
	return sodium_bin2base64($x, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
}

function deb64($x)
{
	return sodium_base642bin($x, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
}


function _dbc()
{
	static $dbc;

	if (empty($dbc)) {

		$cfg = \OpenTHC\Config::get('database/pub');
		$dbc = new \Edoceo\Radix\DB\SQL(sprintf('sqlite:%s/var/pub.sqlite', APP_ROOT));
	}

	return $dbc;

}

/**
 *
 */
function _read_post_input()
{
	// Get Body
	$input_data = [];
	$input_type = strtolower(strtok($_SERVER['CONTENT_TYPE'], ';'));
	switch ($input_type) {
		case 'application/json':
			$tmp = file_get_contents('php://input');
			$tmp = json_decode($tmp, true);
			$input_data['nonce'] = $tmp['nonce'];
			$input_data['crypt'] = $tmp['crypt'];
			break;
		case 'application/octet-stream':
		case 'text/plain':
			$tmp = file_get_contents('php://input');
			$input_data['nonce'] = substr($tmp, 0, 32);
			$input_data['crypt'] = substr($tmp, 33);
			break;
		case 'application/x-www-form-urlencoded':
		case 'multipart/form-data':
			$input_data['nonce'] = $_POST['nonce'];
			$input_data['crypt'] = $_POST['crypt'];
			break;
	}

	return $input_data;

}
