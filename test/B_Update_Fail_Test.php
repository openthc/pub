<?php
/**
 * Test Account Update Fails
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Test;

class B_Update_Fail_Test extends \Test\Base
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
		$this->service_public_key = \deb64($res);

	}

	/**
	 * Post Update to A using keys for B
	 *
	 * @test
	 */
	function update_account_a_as_b()
	{
		$input_data = json_encode([
			'id' => _ulid(),
			'name' => 'TEST LICENSE A',
			'note' => "MARKDOWN TEXT HERE?\nInclude email and phone maybe?",
			'public-incoming-url' => sprintf('https://openthc.pub/%s', \enb64($_ENV['b_pk'])),
		]);

		$spk = sodium_crypto_box_keypair_from_secretkey_and_publickey($_ENV['b_sk'], $this->service_public_key);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $spk);

		$post_data = [
			'nonce' => enb64($nonce_data),
			'crypt' => enb64($crypt_data)
		];

		// Post to "myself" with encrypted message for Service
		$url = sprintf('%s/%s', $this->_api_base, \enb64($_ENV['a_pk']));
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_POST, true);
		curl_setopt($req, CURLOPT_POSTFIELDS, $post_data);

		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(400, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);

	}

	/**
	 * Post Update to A using keys for B
	 *
	 * @test
	 */
	function update_account_b_as_c()
	{
		$input_data = json_encode([
			'id' => _ulid(),
			'name' => 'TEST LICENSE B',
			'note' => "MARKDOWN TEXT HERE?\nInclude email and phone maybe?",
			'public-incoming-url' => sprintf('https://openthc.pub/%s', \enb64($_ENV['c_pk'])),
		]);

		$spk = sodium_crypto_box_keypair_from_secretkey_and_publickey($_ENV['c_sk'], $this->service_public_key);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $spk);

		$text_body = sprintf('%s:%s'
			, enb64($nonce_data)
			, enb64($crypt_data)
		);

		// Post to "myself" with encrypted message for Service
		$url = sprintf('%s/%s', $this->_api_base, \enb64($_ENV['b_pk']));
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($req, CURLOPT_POSTFIELDS, $text_body);
		curl_setopt($req, CURLOPT_HTTPHEADER, [
			sprintf('content-length: %d', strlen($text_body)),
			'content-type: text/plain',
		]);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(400, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);

	}

}
