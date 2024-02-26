<?php
/**
 * Test Account Update
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Test;

class D_Message_Delete_Test extends \Test\Base
{
	protected $service_public_key = null;

	/**
	 *
	 */
	function setup() : void
	{
		// Get Service Public Key
		$req = _curl_init(sprintf('%s/pk', $this->_api_base));
		$res = curl_exec($req);
		// $inf = curl_getinfo($req);
		// $this->assertEquals(200, $inf['http_code']);
		// $this->assertEquals('text/plain; charset=utf-8', $inf['content_type']);
		// $this->assertEquals(43, strlen($res));
		$this->service_public_key = deb64($res);

	}

	/**
	 * Does delete work?
	 *
	 * @test
	 */
	function delete_message()
	{
		$res = $this->get_message_zero($_ENV['a_pk']);
		$msg = $res['data'];

		$kp1 = sodium_crypto_box_keypair_from_secretkey_and_publickey($_ENV['a_sk'], $this->service_public_key);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$input_data = json_encode([
			'action' => 'DELETE'
		]); //  http_build_query([ 'a' => 'delete' ]);
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $kp1);

		$arg = [
			'n' => enb64($nonce_data),
			'c' => enb64($crypt_data)
		];

		// Post to "myself" with encrypted message for Service
		$url = sprintf('%s/%s/%s?%s', $this->_api_base, enb64($_ENV['a_pk']), $msg['id'], http_build_query($arg));
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'DELETE');
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(200, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);

		$res = json_decode($res, true);
		$this->assertNotEmpty($res);

	}

	/**
	 * Does delete give a 400 if your argument parameter is bad?
	 *
	 * @test
	 */
	function delete_message_bad_arg()
	{
		$res = $this->get_message_zero($_ENV['a_pk']);
		$msg = $res['data'];

		$kp1 = sodium_crypto_box_keypair_from_secretkey_and_publickey($_ENV['a_sk'], $this->service_public_key);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$input_data = 'INVALID-DELETE-INVALID';
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $kp1);

		$arg = [
			'n' => enb64($nonce_data),
			'c' => enb64($crypt_data)
		];

		// Post to "myself" with encrypted message for Service
		$url = sprintf('%s/%s/%s?%s', $this->_api_base, enb64($_ENV['a_pk']), $msg['id'], http_build_query($arg));
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'DELETE');
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(400, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);

		$res = json_decode($res, true);
		$this->assertNotEmpty($res);

	}

	/**
	 * Should 403 if you use a key that doesn't match
	 *
	 * @test
	 */
	function delete_message_bad_key()
	{
		$res = $this->get_message_zero($_ENV['a_pk']);
		$msg = $res['data'];

		$kp1 = sodium_crypto_box_keypair_from_secretkey_and_publickey($_ENV['a_sk'], $this->service_public_key);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$input_data = json_encode([
			'action' => 'DELETE'
		]); //  http_build_query([ 'a' => 'delete' ]);
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $kp1);

		$arg = [
			'n' => enb64($nonce_data),
			'c' => enb64($crypt_data)
		];

		// Post to "myself" with encrypted message for Service
		$url = sprintf('%s/%s/%s?%s', $this->_api_base, enb64($_ENV['b_pk']), $msg['id'], http_build_query($arg));
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'DELETE');
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(403, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);

		$res = json_decode($res, true);
		$this->assertNotEmpty($res);

	}

	/**
	 * Return a 404 if choosing someone else's message or a non-existant one
	 *
	 * @test
	 */
	function delete_message_bad_msg()
	{
		$res = $this->get_message_zero($_ENV['b_pk']);
		$msg = $res['data'];

		$kp1 = sodium_crypto_box_keypair_from_secretkey_and_publickey($_ENV['a_sk'], $this->service_public_key);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$input_data = json_encode([
			'action' => 'DELETE'
		]); //  http_build_query([ 'a' => 'delete' ]);
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $kp1);

		$arg = [
			'n' => enb64($nonce_data),
			'c' => enb64($crypt_data)
		];

		// Post to "myself" with encrypted message for Service
		$url = sprintf('%s/%s/%s?%s', $this->_api_base, enb64($_ENV['a_pk']), $msg['id'], http_build_query($arg));
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'DELETE');
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(404, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);

		$res = json_decode($res, true);
		$this->assertNotEmpty($res);

	}

}
