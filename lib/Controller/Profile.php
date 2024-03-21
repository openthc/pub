<?php
/**
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Controller;

use OpenTHC\Sodium;

class Profile extends Base
{
	/**
	 *
	 */
	function __invoke($REQ, $RES, $ARG)
	{
		$want_list = explode(',', $_SERVER['HTTP_ACCEPT']);
		$want_type = strtolower($want_list[0]);

		$auth = '';
		if ( ! empty($_SERVER['HTTP_AUTHORIZATION'])) {
			$x = $_SERVER['HTTP_AUTHORIZATION'];
			if ( ! preg_match('/^OpenTHC\s+([\w\-\.]{43,1024})$/', $x, $m)) {
				return $RES->withJSON([
					'data' => null,
					'meta' => [ 'note' => 'Invalid Request [PCP-058]' ]
				], 400);
			}
			$auth_box = \OpenTHC\Sodium::b64decode($m[1]);
			$auth = \OpenTHC\Sodium::decrypt($auth_box, \OpenTHC\Config::get('openthc/pub/secret'), $ARG['pk']);
		}

		$ret_data = [];

		$dbc = _dbc();
		$chk = $dbc->fetchRow('SELECT * FROM profile WHERE id = :pk', [
			':pk' => $ARG['pk'],
		]);

		if (empty($chk)) {
			$x = new \Slim\Exception\NotFoundException($REQ, $RES);
			$x->note = 'Profile Not Found';
			throw $x;
		}

		$ret_data = [
			'id' => $chk['id'],
			'created_at' => $chk['created_at'],
			'updated_at' => $chk['updated_at'],
		];

		// If you've provided the necessary header
		// add list of files for this profile
		if ( ! empty($auth)) {

			$ret_data['pk0'] = $ARG['pk'];
			$ret_data['pk1'] = $auth;
			$ret_data['file_list'] = [];

			// Find Message w/Prefix?
			$sql = 'SELECT id, name, type FROM message WHERE id LIKE :p0';
			$arg = [ ':p0' => sprintf('%s/%%', $auth) ];
			$ret_data['file_list'] = $dbc->fetchAll($sql, $arg);
		}

		switch ($want_type) {
			case 'text/html':

				$html = '<h1>Profile</h1>';
				$html.= '<pre>';
				$html.= __h(json_encode($ret_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
				$html.= '</pre>';

				return $RES->write($html);

				break;
			case 'text/plain':
				break;
			default:
		}

		return $RES->withJSON([
			'data' => $ret_data,
			'meta' => [],
		]);

		// header('content-description: Data Download');
		// header(sprintf('content-disposition: inline; filename="%s"', $name));
		// header(sprintf('content-length: %d', filesize($file)));
		// header('content-transfer-encoding: binary');
		// header('content-type: application/pdf');

		// if ('HEAD' == $_SERVER['REQUEST_METHOD']) {
		// 	exit;
		// }

		// readfile($file);

	}

	/**
	 *
	 */
	function update($REQ, $RES, $ARG)
	{
		$chk = $this->authClientVerify();
		if (200 != $chk['code']) {
			$ret_data = $chk;
			$ret_code = $ret_data['code'];
			unset($ret_data['code']);
			return $RES->withJSON($ret_data, $ret_code);
		}

		$client_auth = $chk['data'];

		// Decrypt This with the The API-Client-Public Key
		// Do I trust this PK? That's a Lookup Somewhere
		// $client_ab = Sodium::decrypt($client_ab_bin, \OpenTHC\Config::get('openthc/pub/secret'), $client_pk);
		// if (empty($client_ab)) {
		// 	return $RES->withJSON([
		// 		'data' => null,
		// 		'meta' => [ 'note' => 'Invalid Request [PCP-129]' ]
		// 	], 403);
		// }

		// $client_ab = json_decode($client_ab);
		// if (empty($client_ab)) {
		// 	return $RES->withJSON([
		// 		'data' => null,
		// 		'meta' => [ 'note' => 'Invalid Request [PCP-137]' ]
		// 	], 403);
		// }

		// Needs Profile Auth
		if (empty($client_auth->profile)) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Invalid Request [PCP-132]' ]
			], 400);
		}

		$profile_pk = $ARG['pk'];
		$profile_auth_bin = Sodium::b64decode($client_auth->profile);
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

		// // The POSTed UPDATE should be Encrypted JSON
		// $dbc = _dbc();
		// $Profile0 = $dbc->fetchRow('SELECT * FROM profile WHERE id = :pk', [
		// 	':pk' => $ARG['pk'],
		// ]);

		// if (empty($Profile0)) {

		// 	$dbc->insert('profile', [
		// 		'id' => $ARG['pk'],
		// 		'name' => '-create-',
		// 	]);

		// 	return $RES->withJSON([
		// 		'data' => $ARG['pk'],
		// 		'meta' => [],
		// 	], 201);

		// }
		$profile_data = '';
		$profile_data_type = strtolower(strtok($_SERVER['CONTENT_TYPE'], ';'));
		switch ($profile_data_type) {
			case 'application/json':
				$profile_data = file_get_contents('php://input');
				$profile_data = json_decode($profile_data);
				$profile_data = json_encode($profile_data);
				break;
			case 'text/plain':
				$profile_data = file_get_contents('php://input');
				// Sanatize?
				$profile_data = json_encode([
					'text' => $profile_data
				]);
				break;
			default:
				return $RES->withJSON([
					'data' => null,
					'meta' => [ 'note' => 'Invalid Content Type [PCP-182]' ]
				], 403);
		}

		$profile = [];
		$profile['id'] = $profile_pk;
		$profile['meta'] = $profile_data;

		$dbc = _dbc();
		$chk = $dbc->fetchOne('SELECT id FROM profile WHERE id = :p0', [ ':p0' => $profile_pk ]);

		// Create this Endpoint for Profile
		if (empty($chk)) {
			$dbc->insert('profile', $profile);
			return $RES->withJSON([
				'data' => $profile['id'],
				'meta' => [ 'note' => 'Profile Created' ]
			], 201);
		}

		// Update?
		$res = $dbc->update('profile', [ 'meta' => $profile['meta'] ], [ 'id' => $profile['id'] ]);

		return $RES->withJSON([
			'data' => $profile['id'],
			'meta' => [ 'note' => 'Profile Updated' ],
		], 200);
	}

}
