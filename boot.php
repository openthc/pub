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

define('OPENTHC_SERVICE_ID', \OpenTHC\Config::get('openthc/pub/id'));
define('OPENTHC_SERVICE_ORIGIN', \OpenTHC\Config::get('openthc/pub/origin'));

define('OPENTHC_PUB_PK', \OpenTHC\Config::get('openthc/pub/public'));

/**
 * Error Handler
 */
function _eh($ex, $em=null, $ef=null, $el=null, $ec=null) {

	while (ob_get_level() > 0) { ob_end_clean(); }

	header('HTTP/1.1 500 Internal Error', true, 500);
	header('content-type: text/plain');

	$msg = [];
	$msg[] = 'Internal Error [PUB-035]';
	if (is_numeric($ex)) {
		// Error Code
		$msg[] = sprintf('Error: %s [%d]', $em, $ex);
	} elseif (is_object($ex)) {
		// Exception
		$msg[] = sprintf('Exception: %s', $ex->getMessage());
		$ef = $ex->getFile();
		$el = $ex->getLine();
	}

	if (!empty($ef)) {
		$ef = substr($ef, strlen($ef) / 2); // don't show full path
		$msg[] = sprintf('File: ...%s:%d', $ef, $el);
	}

	error_log(implode('; ', $msg));

	echo implode("\n", $msg);

	exit(1);

}


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
		$dsn = sprintf('pgsql:host=%s;dbname=%s', $cfg['hostname'], $cfg['database']);
		$dbc = new \Edoceo\Radix\DB\SQL($dsn, $cfg['username'], $cfg['password']);
	}

	return $dbc;

}

function _rdb()
{
	static $rdb;

	if (empty($rdb)) {

		$cfg = \OpenTHC\Config::get('database/pub');
		// $rdb = \Redis();
		// $cfg = \OpenTHC\Config::get('redis');
		$rdb = new \Redis();
		$rdb->connect('127.0.0.1', '6379');
		// $rdb->connect($cfg['hostname'], $cfg['port']);

	}

	return $rdb;

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
