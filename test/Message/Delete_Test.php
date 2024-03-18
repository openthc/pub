<?php
/**
 * Test Message Delete
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Test\Message;

use OpenTHC\Sodium;

class Delete_Test extends \OpenTHC\Pub\Test\Base
{
	/**
	 * Does delete work?
	 *
	 * @test
	 */
	function delete_message()
	{
		$msg = $this->get_message_zero(OPENTHC_TEST_LICENSE_A_SK, OPENTHC_TEST_LICENSE_A_PK);

		$profile_auth = Sodium::encrypt(OPENTHC_TEST_LICENSE_A_PK, OPENTHC_TEST_LICENSE_A_SK, $this->_service_pk_bin);
		$profile_auth = Sodium::b64encode($profile_auth);

		// Post to "myself" with encrypted message for Service
		$req_path = $msg['id']; // sprintf('%s/%s', OPENTHC_TEST_LICENSE_A_PK, $msg['id']);
		$req_head = [
			'openthc-profile' => $profile_auth,
		];
		// $url = sprintf('%s/%s/%s?%s', $this->_api_base, enb64(OPENTHC_TEST_LICENSE_A_PK), $msg['id'], http_build_query($arg));
		// $req = _curl_init($url);
		$res = $this->_curl_delete($req_path, $req_head);
		var_dump($res);

		$this->assertEquals(200, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$res = json_decode($res['body'], true);
		$this->assertNotEmpty($res);

	}

	/**
	 * Does delete give a 400 if your argument parameter is bad?
	 *
	 * @test
	 */
	function delete_message_bad_arg()
	{
		$res = $this->get_message_zero(OPENTHC_TEST_LICENSE_A_SK, OPENTHC_TEST_LICENSE_A_PK);
		$msg = $res['data'];

		$sk = Sodium::b64decode(OPENTHC_TEST_LICENSE_A_SK);
		$kp1 = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk, $this->_service_pk_bin);
		$nonce_data = random_bytes(SODIUM_CRYPTO_BOX_NONCEBYTES);
		$input_data = 'INVALID-DELETE-INVALID';
		$crypt_data = sodium_crypto_box($input_data, $nonce_data, $kp1);

		$arg = [
			'n' => enb64($nonce_data),
			'c' => enb64($crypt_data)
		];

		// Post to "myself" with encrypted message for Service
		$url = sprintf('%s/%s/%s?%s', $this->_api_base, enb64(OPENTHC_TEST_LICENSE_A_PK), $msg['id'], http_build_query($arg));
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
		$res = $this->get_message_zero(OPENTHC_TEST_LICENSE_A_SK, OPENTHC_TEST_LICENSE_A_PK);
		$msg = $res['data'];

		$sk = Sodium::b64decode(OPENTHC_TEST_LICENSE_A_SK);
		$kp1 = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk, $this->_service_pk_bin);
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
		$url = sprintf('%s/%s/%s?%s', $this->_api_base, enb64(OPENTHC_TEST_LICENSE_B_PK), $msg['id'], http_build_query($arg));
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
		$res = $this->get_message_zero(OPENTHC_TEST_LICENSE_B_SK, OPENTHC_TEST_LICENSE_B_PK);
		$msg = $res['data'];

		$sk = Sodium::b64decode(OPENTHC_TEST_LICENSE_A_SK);
		$kp1 = sodium_crypto_box_keypair_from_secretkey_and_publickey($sk, $this->_service_pk_bin);
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
		$url = sprintf('%s/%s/%s?%s', $this->_api_base, enb64(OPENTHC_TEST_LICENSE_A_PK), $msg['id'], http_build_query($arg));
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
