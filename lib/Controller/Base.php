<?php
/**
 * OpenTHC Pub Base Controller
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Controller;

use OpenTHC\Sodium;

class Base extends \OpenTHC\Controller\Base
{
	/**
	 * Get Auth Params - decryptClientAuthData()
	 */
	function authClientVerify()
	{
		$auth = '';
		if (empty($_SERVER['HTTP_AUTHORIZATION'])) {
			return [
				'code' => 400,
				'data' => null,
				'meta' => [ 'note' => 'Invalid Request [PCB-024]' ],
			];
		}

		$x = $_SERVER['HTTP_AUTHORIZATION'];
		if ( ! preg_match('/^OpenTHC\s+([\w\-]{43}).([\w\-]{128,512})$/', $x, $m)) {
			return [
				'code' => 400,
				'data' => null,
				'meta' => [ 'note' => 'Invalid Request [PCB-033]' ]
			];
		}
		$client_pk = $m[1];
		$client_auth = Sodium::b64decode($m[2]);

		// Decrypt This with the The API-Client-Public Key
		$client_auth = Sodium::decrypt($client_auth, \OpenTHC\Config::get('openthc/pub/secret'), $client_pk);
		if (empty($client_auth)) {
			return [
				'code' => 401,
				'data' => null,
				'meta' => [ 'note' => 'Invalid Request [PCB-046]' ]
			];
		}
		$client_auth = json_decode($client_auth);
		if (empty($client_auth)) {
			return [
				'code' => 401,
				'data' => null,
				'meta' => [ 'note' => 'Invalid Request [PCB-053]' ]
			];
		}

		// Do I trust this PK? That's a Lookup Somewhere
		// $mode = \OpenTHC\Config::get('openthc/pub/profile');
		// switch ($mode) {
		// 	case 'create':
		// 		// Allow (only on Profiel Create Request)
		// 		break;
		// 	case 'verify':
		// 		// Do Lookup
		// 		break;
		// }
		// $rdb = _rdb();
		// $key = sprintf('/pub/profile/%s', $client_pk);
		// $chk = $rdb->get($key);
		// if (empty($chk)) {
		// 	$dbc = _dbc();
		// 	$chk = $dbc->fetchRow('SELECT * FROM profile WHERE id = :pk', [
		// 		':pk' => $client_pk
		// 	]);
		// }
		// if (empty($chk)) {
		// 	return [
		// 		'code' => 401,
		// 		'data' => null,
		// 		'meta' => [ 'note' => 'Invalid Profile [PCB-071]' ]
		// 	];
		// }

		return [
			'code' => 200,
			'data' => $client_auth,
			'meta' => [],
		];

	}

	/**
	 *
	 */
	function getCache($key)
	{
		$rdb = _rdb();
		return json_decode($rdb->get($key));
	}

	/**
	 *
	 */
	function setCache(string $key, $val)
	{
		if ( ! is_string($val)) {
			$val = json_encode($val);
		}

		$rdb = _rdb();
		// $rdb->set($key, $val, [ 'ttl' => 240 ]);
	}


}
