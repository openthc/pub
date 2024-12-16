<?php
/**
 * Test Message Create
 * From A to B and B to A
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Test\Message;

use OpenTHC\Sodium;

class Create_Test extends \OpenTHC\Pub\Test\Base
{
	static function setupBeforeClass() : void
	{
		$dbc = _dbc();
		$dbc->query('DELETE FROM message WHERE id = :pk', [ ':pk' => sprintf('%s/%s', $_ENV['OPENTHC_TEST_LICENSE_A_PK'], $_ENV['OPENTHC_TEST_LICENSE_B_PK']) ]);
		$dbc->query('DELETE FROM message WHERE id = :pk', [ ':pk' => sprintf('%s/%s', $_ENV['OPENTHC_TEST_LICENSE_B_PK'], $_ENV['OPENTHC_TEST_LICENSE_A_PK']) ]);
		// $dbc->query('DELETE FROM message WHERE id = :pk', [ ':pk' => sprintf('%s/%s', $_ENV['OPENTHC_TEST_LICENSE_B_PK'] ])
		// $dbc->query('DELETE FROM message WHERE id = :pk', [ ':pk' => sprintf('%s/%s', $_ENV['OPENTHC_TEST_LICENSE_C_PK'] ]);

	}

	/**
	 * @test
	 */
	function create_a_to_public()
	{
		$message_file = sprintf('%s.json', _ulid());
		$message_seed = sprintf('%s.%s', $_ENV['OPENTHC_TEST_LICENSE_A_PK'], $message_file);
		$message_seed = sodium_crypto_generichash($message_seed, '', SODIUM_CRYPTO_GENERICHASH_KEYBYTES);
		$message_kp = sodium_crypto_box_seed_keypair($message_seed);
		$message_pk = sodium_crypto_box_publickey($message_kp);
		$message_sk = sodium_crypto_box_secretkey($message_kp);

		$message_auth = Sodium::b64encode($message_pk); // OPENTHC_TEST_LICENSE_A_PK;
		$message_auth = Sodium::encrypt($message_auth, $message_sk, $this->_service_pk_bin);
		$message_auth = Sodium::b64encode($message_auth);
		$req_auth = $this->create_req_auth([
			'message' => $message_auth,
		]);

		$message_data = json_encode([
			'@version' => 'TEST',
			'inventory' => [],
			'product' => [],
		]);

		$req_head = [
			'authorization' => sprintf('OpenTHC %s.%s', $this->_api_client_pk, $req_auth),
			'content-type' => 'application/json',
		];

		$message_path = Sodium::b64encode($message_pk);

		$req_path = sprintf('%s/%s', $message_path, $message_file);
		$res = $this->_curl_post($req_path, $req_head, $req_body);
		$this->assertEquals(201, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);
		$this->assertStringEndsWith($req_path, $obj->data);

	}

	/**
	 * @test
	 */
	function message_a_to_b_plain()
	{
		$pk_source_b64 = $_ENV['OPENTHC_TEST_LICENSE_A_PK'];
		$pk_source_bin = \OpenTHC\Sodium::b64decode($pk_source_b64);
		$sk_source_b64 = $_ENV['OPENTHC_TEST_LICENSE_A_SK'];
		$sk_source_bin = \OpenTHC\Sodium::b64decode($sk_source_b64);
		$pk_target_b64 = $_ENV['OPENTHC_TEST_LICENSE_B_PK'];
		$pk_target_bin = \OpenTHC\Sodium::b64decode($pk_target_b64);

		$req_path = sprintf('%s/%s.json', $_ENV['OPENTHC_TEST_LICENSE_B_PK'], _ulid());

		$req_auth = $this->create_req_auth();

		$req_head = [
			'authorization' => sprintf('OpenTHC %s.%s', $this->_api_client_pk, $req_auth),
			'content-type' => 'application/json',
		];

		$req_body = json_encode([
			'@context' => 'http://openthc.org/api/v2017',
			'inventory' => [],
			'product' => [],
			'variety' => [],
			'lab_result' => [],
		]);

		// Write to B-Public-Key a message named A-Public-Key
		$res = $this->_curl_post($req_path, $req_head, $req_body);
		$this->assertEquals(201, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		// var_dump($obj);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);

		// Write A Second Time, Should Fail
		$res = $this->_curl_post($req_path, $req_head, $req_body);
		$this->assertEquals(409, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		// var_dump($obj);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);

	}

	/**
	 * Profile A Should be Able To Read
	 *
	 * @test
	 * @depends message_a_to_b
	 */
	// function message_a_to_b_read()
	// {
	// 	$res = $this->get_message_zero(OPENTHC_TEST_LICENSE_B_SK, OPENTHC_TEST_LICENSE_B_PK);
	// 	$this->assertNotEmpty($res['id']);
	// 	$this->assertNotEmpty($res['data']);

	// 	// Can I Decrypt?
	// 	$msg = \OpenTHC\Sodium::b64decode($res['data']);
	// 	$msg_nonce = substr($msg, 0, SODIUM_CRYPTO_BOX_NONCEBYTES);
	// 	$msg_crypt = substr($msg, SODIUM_CRYPTO_BOX_NONCEBYTES);

	// 	// Keypair
	// 	$sk = \OpenTHC\Sodium::b64decode(OPENTHC_TEST_LICENSE_B_SK);
	// 	$pk = \OpenTHC\Sodium::b64decode(OPENTHC_TEST_LICENSE_A_PK);
	// 	$kp1 = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk, $pk);
	// 	$plain_data = sodium_crypto_box_open($msg_crypt, $msg_nonce, $kp1);
	// 	$this->assertNotEmpty($plain_data);

	// 	$msg = json_decode($plain_data, true);
	// 	$this->assertNotEmpty($msg);
	// 	$this->assertNotEmpty($msg['@context']);
	// 	$this->assertEquals('http://openthc.org/api/v2017', $msg['@context']);

	// }

	/**
	 * @test
	 */
	function message_b_to_a()
	{
		// $source_pk_b64 = OPENTHC_TEST_LICENSE_B_PK;
		// $source_pk_bin = \OpenTHC\Sodium::b64decode($source_pk_b64);
		// $source_sk_b64 = OPENTHC_TEST_LICENSE_B_SK;
		// $source_sk_bin = \OpenTHC\Sodium::b64decode($source_sk_b64);
		// $target_pk_b64 = OPENTHC_TEST_LICENSE_A_PK;
		// $target_pk_bin = \OpenTHC\Sodium::b64decode($target_pk_b64);

		$req_body = json_encode([
			'@context' => 'http://openthc.org/api/v2017',
			'inventory' => [],
			'product' => [],
			'variety' => [],
			'lab_result' => [],
		]);

		$req_auth = $this->create_req_auth();

		// Plain Text Data
		$req_path = sprintf('%s/%s.txt', $_ENV['OPENTHC_TEST_LICENSE_A_PK'], _ulid());
		$req_body = "This is a Plain Text Message";
		$req_head = [
			'authorization' => sprintf('OpenTHC %s.%s', $this->_api_client_pk, $req_auth),
			'content-type' => 'text/plain',
		];

		$res = $this->_curl_post($req_path, $req_head, $req_body);
		$this->assertEquals(201, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);
		$this->assertEquals($req_path, $obj->data);

		return $req_path;
	}

	/**
	 * @test
	 * @depends message_b_to_a
	 */
	function b_to_a_only_a_can_read($msg_path)
	{
		$req_auth = $this->create_req_auth();
		$req_path = $msg_path;
		$req_head = [
			'authorization' => sprintf('OpenTHC %s.%s', $this->_api_client_pk, $req_auth),
		];

		$res = $this->_curl_get($req_path, $req_head);
		$this->assertEquals(404, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);
		$this->assertEquals('Message Not Found [PCM-130]', $obj->meta->note);

		// Authorized Request
		$profile_auth = Sodium::encrypt($_ENV['OPENTHC_TEST_LICENSE_A_PK'], $_ENV['OPENTHC_TEST_LICENSE_A_SK'], $this->_service_pk_bin);
		$profile_auth = Sodium::b64encode($profile_auth);

		$req_auth = $this->create_req_auth([
			'profile' => $profile_auth
		]);
		$req_head = [
			'authorization' => sprintf('OpenTHC %s.%s', $this->_api_client_pk, $req_auth),
		];

		$res = $this->_curl_get($req_path, $req_head);
		$this->assertEquals(200, $res['code']);
		$this->assertEquals('text/plain;charset=UTF-8', $res['type']);

	}

	/**
	 * @test
	 */
	function message_a_to_random_crypt()
	{
		$target_kp0 = sodium_crypto_box_keypair();
		$target_pk_bin = sodium_crypto_box_publickey($target_kp0);
		$target_pk_b64 = \OpenTHC\Sodium::b64encode($target_pk_bin);

		$req_body = json_encode([
			'@context' => 'http://openthc.org/api/v2017',
			'inventory' => [],
			'product' => [],
			'variety' => [],
			'lab_result' => [],
		]);
		$req_body = Sodium::encrypt($req_body, $_ENV['OPENTHC_TEST_LICENSE_A_SK'], $target_pk_bin);
		// $req_body = Sodium::b64encode($req_body);

		$message_kp0 = sodium_crypto_box_keypair();
		$message_pk_bin = sodium_crypto_box_publickey($message_kp0);
		$message_sk_bin = sodium_crypto_box_secretkey($message_kp0);
		$message_pk_b64 = \OpenTHC\Sodium::b64encode($message_pk_bin);

		$message_auth = $message_pk_b64; // Sodium::b64encode($message_pk_bin);
		$message_auth = Sodium::encrypt($message_auth, $message_sk_bin, $this->_service_pk_bin);
		$message_auth = Sodium::b64encode($message_auth);

		$req_auth = $this->create_req_auth([
			'message' => $message_auth,
		]);

		$req_path = sprintf('%s/%s.bin', $message_pk_b64, _ulid());
		$req_head = [
			'authorization' => sprintf('OpenTHC %s.%s', $this->_api_client_pk, $req_auth),
			'content-type' => 'application/octet-stream',
		];
		$res = $this->_curl_post($req_path, $req_head, $req_body);
		// var_dump($res);
		$this->assertEquals(201, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		// var_dump($obj);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);
		$this->assertStringEndsWith($req_path, $obj->data);

		// Now Get It and Decrypt
		// Ensuring that the Bytes are Good?
		$res = $this->_curl_get($req_path);
		$this->assertEquals(200, $res['code']);
		$this->assertEquals('application/octet-stream', $res['type']);

		// Decrypt It
		$sk = sodium_crypto_box_secretkey($target_kp0);
		$pk = OPENTHC_TEST_LICENSE_A_PK;
		$res_data = Sodium::encrypt($res['body'], $sk, $pk);
		$this->assertNotEmpty($res_data);

	}

	/**
	 * @disabled-test
	 * @depends message_a_to_random
	 */
	function message_random_to_a()
	{
		$raw = sodium_crypto_box_keypair();
		$source_pk_bin = sodium_crypto_box_publickey($raw);
		$source_pk_b64 = \OpenTHC\Sodium::b64encode($source_pk_bin);
		// $source_sk_bin = sodium_crypto_box_secretkey($raw);
		// $source_sk_b64 = \OpenTHC\Sodium::b64encode($source_sk_bin);

		$target_pk_b64 = OPENTHC_TEST_LICENSE_A_PK;
		$target_pk_bin = \OpenTHC\Sodium::b64decode($target_pk_b64);

		$req_body = json_encode([
			'@context' => 'http://openthc.org/api/v2017',
			'lot' => [],
			'product' => [],
			'variety' => [],
			'lab_result' => [],
		]);

		$url = sprintf('%s/%s/%s', $this->_api_base, $target_pk_b64, $source_pk_b64);
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($req, CURLOPT_POSTFIELDS, $req_body);
		curl_setopt($req, CURLOPT_HTTPHEADER, [
			sprintf('content-length: %d', strlen($req_body)),
			'content-type: application/json',
		]);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(201, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);
		$res = json_decode($res, true);
		$this->assertNotEmpty($res['data']);
		$message_id = $res['data'];
		// $this->assertNotEmpty($res['meta']['source']);
		// $this->assertNotEmpty($res['meta']['target']);

		// A should have this message
		$service_pk = \OpenTHC\Config::get('openthc/pub/public');
		// Encrypt Credentials to Service
		$box = \OpenTHC\Sodium::encrypt($_ENV['OPENTHC_TEST_LICENSE_A_PK'], $_ENV['OPENTHC_TEST_LICENSE_A_SK'], $service_pk );
		$res = $this->_curl_get($target_pk_b64, [
			sprintf('authorization: OpenTHC %s', \OpenTHC\Sodium::b64encode($box))
		]);
		// $url = sprintf('%s/%s', $this->_api_base, $target_pk_b64);
		// $req = _curl_init($url);
		// curl_setopt($req, CURLOPT_HTTPHEADER, [
		// 	sprintf('authorization: OpenTHC %s', strlen($req_body)),
		// 	'content-type: application/json',
		// ]);
		// $res = curl_exec($req);
		// $inf = curl_getinfo($req);
		$this->assertEquals(200, $res['code']);
		$this->assertEquals('application/json', $res['type']);
		$res = json_decode($res, true);
		// var_dump($res);
		$this->assertNotEmpty($res['data']);
		$this->assertEquals($target_pk_b64, $res['data']['id']);
		$this->assertNotEmpty($res['data']['file_list']);

		// Find the One We Sent?
		$message = [];
		foreach ($res['data']['file_list'] as $m) {
			if ($m['id'] == $message_id) {
				$message = $m;
				break;
			}
		}
		$this->assertNotEmpty($message);
		$this->assertEquals($message_id, $message['id']);

		// A should have this message
		$url = sprintf('%s/%s/%s', $this->_api_base, $target_pk_b64, $message['id']);
		$req = _curl_init($url);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);
		// var_dump($res);

	}

}
