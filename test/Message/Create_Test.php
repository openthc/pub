<?php
/**
 * Test Message Create
 * From A to B and B to A
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Test\Message;

class Create_Test extends \OpenTHC\Pub\Test\Base
{
	static function setupBeforeClass() : void
	{
		$dbc = _dbc();
		$dbc->query('DELETE FROM message WHERE id = :pk', [ ':pk' => sprintf('%s/%s', OPENTHC_TEST_LICENSE_A_PK, OPENTHC_TEST_LICENSE_B_PK) ]);
		$dbc->query('DELETE FROM message WHERE id = :pk', [ ':pk' => sprintf('%s/%s', OPENTHC_TEST_LICENSE_B_PK, OPENTHC_TEST_LICENSE_A_PK ) ]);
		// $dbc->query('DELETE FROM message WHERE id = :pk', [ ':pk' => sprintf('%s/%s', OPENTHC_TEST_LICENSE_B_PK ]);
		// $dbc->query('DELETE FROM message WHERE id = :pk', [ ':pk' => sprintf('%s/%s', OPENTHC_TEST_LICENSE_C_PK ]);

	}

	/**
	 * @test
	 */
	function message_a_to_b()
	{
		$pk_source_b64 = OPENTHC_TEST_LICENSE_A_PK;
		$pk_source_bin = \OpenTHC\Sodium::b64decode($pk_source_b64);
		$sk_source_b64 = OPENTHC_TEST_LICENSE_A_SK;
		$sk_source_bin = \OpenTHC\Sodium::b64decode($sk_source_b64);
		$pk_target_b64 = OPENTHC_TEST_LICENSE_B_PK;
		$pk_target_bin = \OpenTHC\Sodium::b64decode($pk_target_b64);

		$input_data = json_encode([
			'@context' => 'http://openthc.org/api/v2017',
			'inventory' => [],
			'product' => [],
			'variety' => [],
			'lab_result' => [],
		]);

		$spk = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk_source_bin, $pk_target_bin);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $spk);

		// Encrypted Data
		$req_body = \OpenTHC\Sodium::b64encode($nonce_data . $crypt_data);
		$req_head = [
			'content-type' => 'text/plain',
		];

		// Write to B-Public-Key a message named A-Public-Key
		$url = sprintf('%s/%s', $pk_target_b64, $pk_source_b64);
		$res = $this->_curl_post($url, $req_head, $req_body);
		$this->assertEquals(201, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$res = json_decode($res['body'], true);
		$this->assertNotEmpty($res['data']);
		$this->assertEmpty($res['meta']['source']);
		$this->assertEmpty($res['meta']['target']);

	}

	/**
	 * Read the Message
	 *
	 * @test
	 * @depends message_a_to_b
	 */
	function message_a_to_b_read()
	{
		$res = $this->get_message_zero(OPENTHC_TEST_LICENSE_B_SK, OPENTHC_TEST_LICENSE_B_PK);
		$this->assertNotEmpty($res['id']);
		$this->assertNotEmpty($res['data']);

		// Can I Decrypt?
		$msg = \OpenTHC\Sodium::b64decode($res['data']);
		$msg_nonce = substr($msg, 0, SODIUM_CRYPTO_BOX_NONCEBYTES);
		$msg_crypt = substr($msg, SODIUM_CRYPTO_BOX_NONCEBYTES);

		// Keypair
		$sk = \OpenTHC\Sodium::b64decode(OPENTHC_TEST_LICENSE_B_SK);
		$pk = \OpenTHC\Sodium::b64decode(OPENTHC_TEST_LICENSE_A_PK);
		$kp1 = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk, $pk);
		$plain_data = sodium_crypto_box_open($msg_crypt, $msg_nonce, $kp1);
		$this->assertNotEmpty($plain_data);

		$msg = json_decode($plain_data, true);
		$this->assertNotEmpty($msg);
		$this->assertNotEmpty($msg['@context']);
		$this->assertEquals('http://openthc.org/api/v2017', $msg['@context']);

	}

	/**
	 * @test
	 */
	function message_b_to_a()
	{
		$source_pk_b64 = OPENTHC_TEST_LICENSE_B_PK;
		$source_pk_bin = \OpenTHC\Sodium::b64decode($source_pk_b64);
		$source_sk_b64 = OPENTHC_TEST_LICENSE_B_SK;
		$source_sk_bin = \OpenTHC\Sodium::b64decode($source_sk_b64);
		$target_pk_b64 = OPENTHC_TEST_LICENSE_A_PK;
		$target_pk_bin = \OpenTHC\Sodium::b64decode($target_pk_b64);

		$input_data = json_encode([
			'@context' => 'http://openthc.org/api/v2017',
			'inventory' => [],
			'product' => [],
			'variety' => [],
			'lab_result' => [],
		]);

		// Plain Text Data
		$req_path = sprintf('%s/%s.txt', OPENTHC_TEST_LICENSE_A_PK, _ulid());
		$req_body = "This is a Plain Text Message";
		$req_head = [
			'content-type' => 'text/plain',
		];

		$res = $this->_curl_post($req_path, $req_head, $req_body);

		$this->assertEquals(201, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$res = json_decode($res['body'], true);
		$this->assertNotEmpty($res['data']);
		$this->assertEmpty($res['meta']['source']);
		$this->assertEmpty($res['meta']['target']);

	}

	/**
	 * @test
	 */
	function message_a_to_random()
	{
		$source_pk_b64 = OPENTHC_TEST_LICENSE_A_PK;
		$source_pk_bin = \OpenTHC\Sodium::b64decode($source_pk_b64);
		$source_sk_b64 = OPENTHC_TEST_LICENSE_A_SK;
		$source_sk_bin = \OpenTHC\Sodium::b64decode($source_sk_b64);

		$kp0 = sodium_crypto_box_keypair();
		$target_pk_bin = sodium_crypto_box_publickey($kp0);
		$target_pk_b64 =\OpenTHC\Sodium::b64encode($target_pk_bin);

		$input_data = json_encode([
			'@context' => 'http://openthc.org/api/v2017',
			'inventory' => [],
			'product' => [],
			'variety' => [],
			'lab_result' => [],
		]);

		// Encrypted Data
		$req_path = sprintf('%s/%s', $target_pk_b64, $source_pk_b64);
		$req_body = \OpenTHC\Sodium::encrypt($input_data, $source_sk_bin, $target_pk_bin);
		$req_head = [
			'content-type' => 'text/plain',
		];

		$res = $this->_curl_post($req_path, $req_head, $req_body);
		$this->assertEquals(201, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$res = json_decode($res['body'], true);
		$this->assertNotEmpty($res['data']);

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
		$box = \OpenTHC\Sodium::encrypt(OPENTHC_TEST_LICENSE_A_PK, OPENTHC_TEST_LICENSE_A_SK, $service_pk );
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
		var_dump($res);
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
