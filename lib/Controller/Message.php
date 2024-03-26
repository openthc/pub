<?php
/**
 * Message put/get/delete
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Controller;

use OpenTHC\Sodium;

class Message extends Base
{
	protected $service_auth;
	protected $profile_auth;

	/**
	 *
	 */
	function del($REQ, $RES, $ARG)
	{
		$chk = $this->authClientVerify($RES);
		if (200 != $chk['code']) {
			$ret_data = $chk;
			$ret_code = $ret_data['code'];
			unset($ret_data['code']);
			return $RES->withJSON($ret_data, $ret_code);
		}
		$client_auth = $chk['data'];

		// $want_list = explode(',', $_SERVER['HTTP_ACCEPT']);
		// $want_type = strtolower($want_list[0]);

		// Am I A Profile?
		$dbc = _dbc();

		$Profile0 = $dbc->fetchRow('SELECT id FROM profile WHERE id = :p0', [ ':p0' => $ARG['pk'] ]);
		if ( ! empty($Profile0['id'])) {
			return $this->delAsProfile($REQ, $RES, $ARG, $chk_auth);
		}

		// Needs Message Auth
		if (empty($client_auth->message)) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Invalid Request [PCM-177]' ]
			], 400);
		}

		$message_pk = $ARG['pk'];
		$message_auth = Sodium::b64decode($client_auth->message);
		$message_auth = Sodium::decrypt($message_auth, \OpenTHC\Config::get('openthc/pub/secret'), $message_pk);
		if (empty($message_auth)) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Invalid Request [PCM-187]' ]
			], 403);
		}
		if (sodium_compare($message_pk, $message_auth) !== 0) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Invalid Message Authentication [PCM-062]' ]
			], 403);
		}

		$message_path = sprintf('%s/%s', $ARG['pk'], $ARG['path']);

		// If this $ARG['pk'] is a Profile do something special
		// Like, Only Signed Requests Can Fetch It?
		$chk = $dbc->fetchRow('SELECT id, name FROM message WHERE id = :pk', [
			':pk' => $message_path,
		]);
		if (empty($chk['id'])) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Message Not Found [PCM-076]' ]
			], 404);
		}

		$res = $dbc->query('DELETE FROM message WHERE id = :m0', [ ':m0' => $message_path ]);
		return $RES->withJSON([
			'data' => null,
			'meta' => [ 'note' => 'Message Deleted' ]
		], 200);

		// $rdb = _rdb();
		// $profile = $rdb->get(sprintf('pub/profile/%s', $ARG['pk']));
		// if ( ! empty($profile)) {
		// 	// Do Something Special
		// }
		// $message_path = sprintf('%s/%s', $ARG['pk'], $ARG['path']);

		return $RES->withJSON([
			'data' => null,
			'meta' => [ 'note' => 'Invalid Delete [PCM-090]' ]
		], 501);

	}

	/**
	 *
	 */
	function get($REQ, $RES, $ARG)
	{
		$want_list = explode(',', $_SERVER['HTTP_ACCEPT']);
		$want_type = strtolower($want_list[0]);

		// Is there any AUTH Header?
		$chk = $this->authClientVerify($RES);
		$client_auth = $chk['data'];

		// Am I A Profile?
		$dbc = _dbc();

		$Profile0 = $dbc->fetchRow('SELECT id FROM profile WHERE id = :p0', [ ':p0' => $ARG['pk'] ]);
		if ( ! empty($Profile0['id'])) {
			return $this->getAsProfile($REQ, $RES, $ARG, $Profile0, $client_auth);
		}

		$message_path = sprintf('%s/%s', $ARG['pk'], $ARG['path']);

		// If this $ARG['pk'] is a Profile do something special
		// Like, Only Signed Requests Can Fetch It?
		$chk = $dbc->fetchOne('SELECT id FROM message WHERE id = :pk', [
			':pk' => $message_path,
		]);
		if (empty($chk)) {
			$ret_data = [
				'data' => null,
				'meta' => [ 'note' => 'Message Not Found [PCM-130]' ]
			];
			switch ($want_type) {
				case 'text/html':
					$RES = $RES->withStatus(404);
					return $RES->write(sprintf('<h1>%s</h1>', $ret_data['meta']['note']));
					break;
				default:
			}

			return $RES->withJSON($ret_data, 404);
		}

		return $this->sendMessage($RES, $message_path);

	}

	function getAsProfile($REQ, $RES, $ARG, $Profile0, $client_auth)
	{
		// $message_pk = $ARG['pk'];
		if (empty($client_auth->profile)) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Message Not Found [PCM-130]' ]
			], 404);
		}

		$profile_auth = Sodium::b64decode($client_auth->profile);
		$profile_auth = Sodium::decrypt($profile_auth, \OpenTHC\Config::get('openthc/pub/secret'), $Profile0['id']);
		if (empty($profile_auth)) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Invalid Request [PCM-187]' ]
			], 403);
		}
		if (sodium_compare($Profile0['id'], $profile_auth) !== 0) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Invalid Profile Message Authentication [PCM-185]' ]
			], 403);
		}

		$message_id = sprintf('%s/%s', $ARG['pk'], $ARG['path']);

		return $this->sendMessage($RES, $message_id);

	}

	/**
	 *
	 */
	function put($REQ, $RES, $ARG)
	{
		$chk = $this->authClientVerify($RES);
		if (200 != $chk['code']) {
			$ret_data = $chk;
			$ret_code = $ret_data['code'];
			unset($ret_data['code']);
			return $RES->withJSON($ret_data, $ret_code);
		}
		$client_auth = $chk['data'];

		$msg = [];
		$msg['id'] = sprintf('%s/%s', $ARG['pk'], $ARG['path']);
		$msg['name'] = $ARG['path'];

		// Type
		$msg['type'] = strtolower(strtok($_SERVER['CONTENT_TYPE'], ';'));
		switch ($msg['type']) {
			case 'application/json':
			case 'application/octet-stream':
			case 'application/pdf':
			case 'text/html':
			case 'text/plain':
				// OK
				break;
			default:
				return $RES->withJSON([
					'data' => null,
					'meta' => [ 'note' => 'Invalid Content Type [CLM-042]' ]
				], 400);
		}

		$msg['body'] = file_get_contents('php://input');

		// Am I A Profile?
		$dbc = _dbc();
		$Profile0 = $dbc->fetchRow('SELECT id FROM profile WHERE id = :p0', [ ':p0' => $ARG['pk'] ]);
		if ( ! empty($Profile0['id'])) {
			return $this->putAsProfile($REQ, $RES, $ARG, $client_auth, $msg);
		}

		// Needs Message Auth
		if (empty($client_auth->message)) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Invalid Request [PCM-177]' ]
			], 400);
		}

		$message_pk = $ARG['pk'];
		$message_auth = Sodium::b64decode($client_auth->message);
		$message_auth = Sodium::decrypt($message_auth, \OpenTHC\Config::get('openthc/pub/secret'), $message_pk);
		if (empty($message_auth)) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Invalid Request [PCM-187]' ]
			], 403);
		}
		// Compare PK
		// Are the Contents of the Decrypted Thing what we want?
		if (sodium_compare($message_pk, $message_auth) !== 0) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Invalid Message Authentication [PCM-194]' ]
			], 403);
		}

		// $opa = \OpenTHC\OPA();
		$ret_code = 200;
		$chk = $dbc->fetchOne('SELECT id FROM message WHERE id = :m0', [
			':m0' => $msg['id'],
		]);
		if (empty($chk)) {
			$ret_code = 201;
		}

		// Verify File Type/Data
		$this->_upsert_file([
			'id' => $msg['id'],
			'type' => $msg['type'],
			'name' => $msg['name'],
			'body' => $msg['body'],
		]);
		return $RES->withJSON([
			'data' => $msg['id'],
			'meta' => [
				'name' => $msg['name'],
				'type' => $msg['type'],
			],
		], $ret_code);

		return $RES->withJSON([
			'data' => '',
			'meta' => [
				'note' => 'Invalid Request [CLM-083]',
			],
		], 400);

	}

	/**
	 * Writing to the Endpoint w/o Message Authorization Creates a Message to this Profile
	 * Writing to the Endpoint w/Message Authorization and Matching MESSAGE_KEY Updates the Message to this Profile
	 *    Or do we allow to write ONCE
	 */
	function putAsProfile($REQ, $RES, $ARG, $chk_auth, $msg)
	{
		$dbc = _dbc();

		$chk = $dbc->fetchOne('SELECT id FROM message WHERE id = :m0', [
			':m0' => $msg['id'],
		]);
		if ( ! empty($chk)) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Conflict']
			], 409);

		}

		// INSERT
		$this->_insert_file([
			'id' => $msg['id'],
			'type' => $msg['type'],
			'name' => $msg['name'],
			'body' => $msg['body'],
		]);

		return $RES->withJSON([
			'data' => $msg['id'],
			'meta' => [ 'note' => 'Message Saved']
		], 201);

	}

	function sendMessage($RES, string $id)
	{
		$dbc = _dbc();

		// check the Etag
		// check If-Match, If-None-Match, If-Modified-Since, If-Unmodified-Since, Vary
		$msg = $dbc->fetchRow('SELECT * FROM message WHERE id = :pk', [
			':pk' => $id,
		]);
		if (empty($msg['id'])) {
			return $RES->withJSON([
				'data' => null,
				'meta' => [ 'note' => 'Message Not Found [PCM-327]' ]
			], 404);
		}

		// cache-control: no-store
		// Content-Encoding: gzip
		// Expires
		// Date: Mon, 18 Jul 2016 16:06:00 GMT
		// Etag: "c561c68d0ba92bbeb8b0f612a9199f722e3a621a"
		// header(sprintf('content-disposition: inline; filename="COA-%s.pdf"', $data['Lab_Result']['id']));
		// header('content-transfer-encoding: binary');
		// header(sprintf('content-type: %s', $data['Lab_Result']['coa_type']));
		// Last-Modified.

		$RES = $RES->withHeader('content-type', $msg['type']);

		return $RES->withBody(new \Slim\Http\Stream($msg['body']));

	}

	/**
	 *
	 */
	function _insert_file($rec)
	{
		$dbc = _dbc();

		$sql = <<<SQL
		INSERT INTO message (id, name, size, type, body)
		VALUES (:p0, :n1, :s1, :t1, :b1)
		SQL;

		$cmd = $dbc->prepare($sql, null);
		$cmd->bindParam(':p0', $rec['id']);
		$cmd->bindParam(':n1', $rec['name']);
		$cmd->bindParam(':s1', strlen($rec['body']));
		$cmd->bindParam(':t1', $rec['type']);
		$cmd->bindParam(':b1', $rec['body'], \PDO::PARAM_LOB);
		$ret = $cmd->execute();

		return $ret;

	}

	/**
	 *
	 */
	function _upsert_file($rec)
	{
		$dbc = _dbc();

		$sql = <<<SQL
		INSERT INTO message (id, name, size, type, body)
		VALUES (:p0, :n1, :s1, :t1, :b1)
		ON CONFLICT (id)
		DO UPDATE
		SET name = :n1, size = :s1, type = :t1, body = :b1
		SQL;

		$cmd = $dbc->prepare($sql, null);
		$cmd->bindParam(':p0', $rec['id']);
		$cmd->bindParam(':n1', $rec['name']);
		$cmd->bindParam(':s1', strlen($rec['body']));
		$cmd->bindParam(':t1', $rec['type']);
		$cmd->bindParam(':b1', $rec['body'], \PDO::PARAM_LOB);
		$ret = $cmd->execute();

		return $ret;

	}

}
