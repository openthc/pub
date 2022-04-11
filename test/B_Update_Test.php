<?php
/**
 * Test Account Update
 *
 * SPDX-License-Identifier: MIT
 */

namespace Test;

class B_Update_Test extends \Test\Base_Case
{

	function update_account_a_post()
	{
	}

	/**
	 * @test
	 */
	function update_account_a_post_text()
	{
		// var_dump($_ENV);
		$pk_source = $_ENV['a_pk'];
		$sk_source = $_ENV['a_sk'];
		// var_dump($sk_source);
		// var_dump(strlen($sk_source));
		// var_dump(SODIUM_CRYPTO_BOX_SECRETKEYBYTES);

		// How to Get PK 0 ?
		$req = _curl_init(sprintf('%s/pk', $this->_api_base));
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(200, $inf['http_code']);
		$this->assertEquals('text/plain; charset=utf-8', $inf['content_type']);
		$this->assertEquals(43, strlen($res));
		$pk_target = \deb64($res);

		$input_data = json_encode([
			'link' => 'https://openthc.pub/PUBLIC_KEY_HERE',
			'body' => 'MARKDOWN TEXT HERE?, include email and phone maybe?'
		]);

		$spk = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk_source, $pk_target);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $spk);

		$message = sprintf('%s:%s'
			, enb64($nonce_data)
			, enb64($crypt_data));

		$req = _curl_init(sprintf('%s/%s', $this->_api_base, \enb64($pk_source)));
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($req, CURLOPT_POSTFIELDS, $message);
		curl_setopt($req, CURLOPT_HTTPHEADER, [
			sprintf('content-length: %d', strlen($message)),
			'content-type: text/plain',
		]);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		// var_dump($inf);

		// echo $res;	sapi_windows_cp_conv

	}

	function update_account_a_post_json()
	{
	}

}
