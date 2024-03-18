<?php
/**
 * Test Message Update Fails
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Test\Message;

class Update_Fail_Test extends \OpenTHC\Pub\Test\Base
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
			'public-incoming-url' => sprintf('https://openthc.pub/%s', OPENTHC_TEST_LICENSE_B_PK),
		]);

		$sk = \OpenTHC\Sodium::b64decode(OPENTHC_TEST_LICENSE_B_SK);
		$spk = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk, $this->service_public_key);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $spk);

		$post_data = [
			'nonce' => enb64($nonce_data),
			'crypt' => enb64($crypt_data)
		];

		// Post to A with encrypted message for Service
		$url = sprintf('%s/%s', $this->_api_base, OPENTHC_TEST_LICENSE_A_PK);
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_POST, true);
		curl_setopt($req, CURLOPT_POSTFIELDS, $post_data);

		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(403, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);

	}

	/**
	 * Post Update to A using keys for B
	 *
	 * @test
	 */
	function update_account_b_as_c()
	{
		// Fake B Data
		$input_data = json_encode([
			'id' => _ulid(),
			'name' => 'TEST LICENSE B',
			'note' => "MARKDOWN TEXT HERE?\nInclude email and phone maybe?",
			'public-incoming-url' => sprintf('https://openthc.pub/%s', OPENTHC_TEST_LICENSE_B_PK),
		]);

		$req_path = OPENTHC_TEST_LICENSE_B_PK;

		// But Encrypt w/C Keys
		$sk = \OpenTHC\Sodium::b64decode(OPENTHC_TEST_LICENSE_C_SK);
		$spk = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk, $this->service_public_key);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $spk);

		$req_body = sprintf('%s:%s'
			, enb64($nonce_data)
			, enb64($crypt_data)
		);

		$req_head = [
			'content-type' => 'text/plain'
		];

		// Post to B with encrypted message for Service
		$res = $this->_curl_post($req_path, $req_head, $req_body);
		$this->assertEquals(403, $res['code']);
		$this->assertEquals('application/json', $res['type']);

	}

}
