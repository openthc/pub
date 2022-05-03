<?php
/**
 * Test Message Create
 * From A to B and B to A
 *
 * SPDX-License-Identifier: MIT
 */

namespace Test;

class C_Create_Message_Test extends \Test\Base
{
	/**
	 * @test
	 */
	function message_a_to_b()
	{
		$pk_source = $_ENV['a_pk'];
		$sk_source = $_ENV['a_sk'];
		$pk_target = $_ENV['b_pk'];

		$input_data = json_encode([
			'@context' => 'http://openthc.org/api/v2017',
			'lot' => [],
			'product' => [],
			'variety' => [],
			'lab_result' => [],
		]);

		$spk = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk_source, $pk_target);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $spk);

		// Encrypted Data
		$req_body = sprintf('%s:%s'
			, enb64($nonce_data)
			, enb64($crypt_data));

		$url = sprintf('%s/%s/%s', $this->_api_base, \enb64($pk_source), \enb64($pk_target));
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($req, CURLOPT_POSTFIELDS, $req_body);
		curl_setopt($req, CURLOPT_HTTPHEADER, [
			sprintf('content-length: %d', strlen($req_body)),
			'content-type: text/plain',
		]);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(201, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);
		$res = json_decode($res, true);
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
		$url = sprintf('%s/%s', $this->_api_base, \enb64($_ENV['b_pk']));
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($req, CURLOPT_POSTFIELDS, $req_body);
		curl_setopt($req, CURLOPT_HTTPHEADER, [
			sprintf('content-length: %d', strlen($req_body)),
			'content-type: text/plain',
		]);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(200, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);
		$res = json_decode($res, true);
		$this->assertNotEmpty($res['data']);

		var_dump($res);

	}

	/**
	 * @test
	 */
	function message_b_to_a()
	{
		$pk_source = $_ENV['b_pk'];
		$sk_source = $_ENV['b_sk'];
		$pk_target = $_ENV['a_pk'];

		$input_data = json_encode([
			'@context' => 'http://openthc.org/api/v2017',
			'lot' => [],
			'product' => [],
			'variety' => [],
			'lab_result' => [],
		]);

		$spk = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk_source, $pk_target);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $spk);

		// Encrypted Data
		$req_body = sprintf('%s:%s'
			, enb64($nonce_data)
			, enb64($crypt_data));

		$url = sprintf('%s/%s/%s', $this->_api_base, \enb64($pk_source), \enb64($pk_target));
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($req, CURLOPT_POSTFIELDS, $req_body);
		curl_setopt($req, CURLOPT_HTTPHEADER, [
			sprintf('content-length: %d', strlen($req_body)),
			'content-type: text/plain',
		]);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(201, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);
		$res = json_decode($res, true);
		$this->assertNotEmpty($res['data']);
		$this->assertEmpty($res['meta']['source']);
		$this->assertEmpty($res['meta']['target']);

	}

	/**
	 * @test
	 */
	function message_a_to_random()
	{
		$pk_source = $_ENV['a_pk'];
		$sk_source = $_ENV['a_sk'];

		$kp0 = sodium_crypto_box_keypair();
		$pk_target = sodium_crypto_box_publickey($kp0);
		// $sk0 = sodium_crypto_box_secretkey($raw);

		$input_data = json_encode([
			'@context' => 'http://openthc.org/api/v2017',
			'lot' => [],
			'product' => [],
			'variety' => [],
			'lab_result' => [],
		]);

		$spk = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk_source, $pk_target);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $spk);

		// Encrypted Data
		$req_body = sprintf('%s:%s'
			, enb64($nonce_data)
			, enb64($crypt_data));

		$url = sprintf('%s/%s/%s', $this->_api_base, \enb64($pk_source), \enb64($pk_target));
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($req, CURLOPT_POSTFIELDS, $req_body);
		curl_setopt($req, CURLOPT_HTTPHEADER, [
			sprintf('content-length: %d', strlen($req_body)),
			'content-type: text/plain',
		]);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(201, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);
		$res = json_decode($res, true);
		$this->assertNotEmpty($res['data']);
		// $this->assertNotEmpty($res['meta']['source']);
		// $this->assertNotEmpty($res['meta']['target']);

	}

	/**
	 * @test
	 */
	function message_random_to_a()
	{
		$raw = sodium_crypto_box_keypair();
		$pk_source = sodium_crypto_box_publickey($raw);
		$sk_source = sodium_crypto_box_secretkey($raw);

		$pk_target = $_ENV['a_pk'];

		$input_data = json_encode([
			'@context' => 'http://openthc.org/api/v2017',
			'lot' => [],
			'product' => [],
			'variety' => [],
			'lab_result' => [],
		]);

		$spk = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk_source, $pk_target);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $spk);

		// Encrypted Data
		$req_body = sprintf('%s:%s'
			, enb64($nonce_data)
			, enb64($crypt_data));

		$url = sprintf('%s/%s/%s', $this->_api_base, \enb64($pk_source), \enb64($pk_target));
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($req, CURLOPT_POSTFIELDS, $req_body);
		curl_setopt($req, CURLOPT_HTTPHEADER, [
			sprintf('content-length: %d', strlen($req_body)),
			'content-type: text/plain',
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
		$url = sprintf('%s/%s', $this->_api_base, \enb64($pk_target));
		$req = _curl_init($url);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);
		$this->assertEquals(200, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);
		$res = json_decode($res, true);
		$this->assertNotEmpty($res['data']);
		$this->assertEquals(\enb64($pk_target), $res['data']['id']);
		$this->assertNotEmpty($res['data']['incoming_message_list']);

		// Find the One We Sent?
		$message = [];
		foreach ($res['data']['incoming_message_list'] as $m) {
			if ($m['id'] == $message_id) {
				$message = $m;
				break;
			}
		}
		$this->assertNotEmpty($message);
		$this->assertEquals($message_id, $message['id']);

		// A should have this message
		$url = sprintf('%s/%s/%s', $this->_api_base, \enb64($pk_target), $message['id']);
		$req = _curl_init($url);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);
		// var_dump($res);

	}

}
