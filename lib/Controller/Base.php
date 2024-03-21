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
	 * Get Auth Params
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
				'code' => 403,
				'data' => null,
				'meta' => [ 'note' => 'Invalid Request [PCB-046]' ]
			];
		}
		$client_auth = json_decode($client_auth);
		if (empty($client_auth)) {
			return [
				'code' => 403,
				'data' => null,
				'meta' => [ 'note' => 'Invalid Request [PCP-137]' ]
			];
		}

		// Do I trust this PK? That's a Lookup Somewhere
		// $rdb = _rdb();

		return [
			'code' => 200,
			'data' => $client_auth,
			'meta' => [],
		];

		// Needs Profile Auth
		if (empty($client_ab->profile)) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Invalid Request [PCP-132]' ]
			], 400);
		}

		$profile_pk = $ARG['pk'];
		$profile_auth_bin = Sodium::b64decode($client_ab->profile);
		$profile_auth = Sodium::decrypt($profile_auth_bin, \OpenTHC\Config::get('openthc/pub/secret'), $profile_pk);
		if (empty($profile_auth)) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Invalid Profile [PCP-142]' ]
			], 403);
		}
		// Are the Contents of the Decrypted Thing what we want?
		// (we want the content of the box to be the pk of the sk that encrypted the box)
		if (sodium_compare($profile_pk, $profile_auth) !== 0) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Invalid Profile [PCM-142]' ]
			], 403);
		}

		return $RES;
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
