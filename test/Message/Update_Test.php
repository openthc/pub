<?php
/**
 * Test Message Update
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Test\Message;

use OpenTHC\Sodium;

class Update_Test extends \OpenTHC\Pub\Test\Base
{
	/**
	 * @test
	 */
	function update_message()
	{
		$kp = sodium_crypto_box_keypair();

		$message_file = sprintf('%s.json', _ulid());
		$message_seed = sprintf('%s.%s', OPENTHC_TEST_LICENSE_A_PK, $message_file);
		$message_seed = sodium_crypto_generichash($message_seed, '', SODIUM_CRYPTO_GENERICHASH_KEYBYTES);
		$message_kp = sodium_crypto_box_seed_keypair($message_seed);
		$message_pk = sodium_crypto_box_publickey($message_kp);
		$message_sk = sodium_crypto_box_secretkey($message_kp);

		$message_auth = Sodium::b64encode($message_pk); // OPENTHC_TEST_LICENSE_A_PK;
		$message_auth = Sodium::encrypt($message_pk, $message_sk, $this->_service_pk_bin);

		$req_auth = json_encode([
			'service' => _ulid(),
			'contact' => _ulid(),
			'company' => _ulid(),
			'license' => _ulid(),
			'message' => Sodium::b64encode($message_auth),
		]);
		$req_auth = Sodium::encrypt($req_auth, $this->_api_client_sk, $this->_service_pk_bin);
		$req_auth = Sodium::b64encode($req_auth);

		$req_body = json_encode([
			'@version' => 'CREATE',
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
		// echo $res['body'];
		// var_dump($res);
		$this->assertEquals(201, $res['code']);
		$this->assertEquals('application/json', $res['type']);
		// var_dump($res);

		$obj = json_decode($res['body']);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);
		$this->assertEquals($req_path, $obj->data);

		$res = $this->_curl_get($req_path);

		// Update
		$req_body = json_encode([
			'@version' => 'UPDATE',
			'inventory' => [],
			'product' => [],
		]);

		$req_path = sprintf('%s/%s', $message_path, $message_file);
		$res = $this->_curl_post($req_path, $req_head, $req_body);
		echo $res['body'];
		$this->assertEquals(200, $res['code']);
		$this->assertEquals('application/json', $res['type']);
		// var_dump($res);

		$obj = json_decode($res['body']);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);
		$this->assertEquals($req_path, $obj->data);

		$res = $this->_curl_get($req_path);
		var_dump($res);



		// $res = $this
	}

	/**
	 * @test
	 */
	function update_account_b_post_text()
	{
	}

	/**
	 * @test
	 */
	function update_account_c_post_json()
	{
	}

}
