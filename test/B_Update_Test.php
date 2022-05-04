<?php
/**
 * Test Account Update
 *
 * SPDX-License-Identifier: MIT
 */

namespace Test;

class B_Update_Test extends \Test\Base
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
		$this->service_public_key = \deb64($res);

	}

	/**
	 * @test
	 */
	function update_account_a_post()
	{
		$pk_source = $_ENV['a_pk'];
		$sk_source = $_ENV['a_sk'];

		$input_data = json_encode([
			'id' => \enb64($pk_source),
			'name' => 'TEST LICENSE A',
			'note' => "MARKDOWN TEXT HERE?\nInclude email and phone maybe?",
			'public-incoming-url' => sprintf('https://openthc.pub/%s', \enb64($pk_source)),
		]);

		$spk = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk_source, $this->service_public_key);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $spk);

		$post_data = [
			'nonce' => enb64($nonce_data),
			'crypt' => enb64($crypt_data)
		];

		// Post to "myself" with encrypted message for Service
		$url = sprintf('%s/%s', $this->_api_base, \enb64($pk_source));
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_POST, true);
		curl_setopt($req, CURLOPT_POSTFIELDS, $post_data);
		// curl_setopt($req, CURLOPT_HTTPHEADER, [
			// sprintf('content-length: %d', strlen($json_data)),
			// 'content-type: application/json',
		// ]);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(200, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);

	}

	/**
	 * @test
	 */
	function update_account_b_post_text()
	{
		$pk_source = $_ENV['b_pk'];
		$sk_source = $_ENV['b_sk'];

		$input_data = json_encode([
			'id' => \enb64($pk_source),
			'name' => 'TEST LICENSE B',
			'note' => "MARKDOWN TEXT HERE?\nInclude email and phone maybe?",
			'public-incoming-url' => sprintf('https://openthc.pub/%s', \enb64($pk_source)),
		]);

		$spk = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk_source, $this->service_public_key);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $spk);

		$text_body = sprintf('%s:%s'
			, enb64($nonce_data)
			, enb64($crypt_data)
		);

		// Post to "myself" with encrypted message for Service
		$url = sprintf('%s/%s', $this->_api_base, \enb64($pk_source));
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($req, CURLOPT_POSTFIELDS, $text_body);
		curl_setopt($req, CURLOPT_HTTPHEADER, [
			sprintf('content-length: %d', strlen($text_body)),
			'content-type: text/plain',
		]);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(200, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);

	}

	/**
	 * @test
	 */
	function update_account_c_post_json()
	{
		$pk_source = $_ENV['c_pk'];
		$sk_source = $_ENV['c_sk'];

		$input_data = json_encode([
			'id' => \enb64($pk_source),
			'name' => 'TEST LICENSE C',
			'note' => "MARKDOWN TEXT HERE?\nInclude email and phone maybe?",
			'public-incoming-url' => sprintf('https://openthc.pub/%s', \enb64($pk_source)),
		]);

		$spk = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk_source, $this->service_public_key);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $spk);

		$json_data = json_encode([
			'nonce' => enb64($nonce_data),
			'crypt' => enb64($crypt_data)
		]);

		$url = sprintf('%s/%s', $this->_api_base, \enb64($pk_source));
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($req, CURLOPT_POSTFIELDS, $json_data);
		curl_setopt($req, CURLOPT_HTTPHEADER, [
			sprintf('content-length: %d', strlen($json_data)),
			'content-type: application/json',
		]);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(200, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);

	}

}
