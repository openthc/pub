<?php
/**
 * Test Message Failure Modes
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Test\Message;

use OpenTHC\Sodium;

class Create_Fail_Test extends \OpenTHC\Pub\Test\Base
{
	/**
	 * @test
	 * Try to put a message w/o authentication
	 */
	function no_authorization()
	{
		$message_file = sprintf('%s.json', _ulid());
		$message_seed = sprintf('%s.%s', $_ENV['OPENTHC_TEST_LICENSE_A_PK'], $message_file);
		$message_seed = sodium_crypto_generichash($message_seed, '', SODIUM_CRYPTO_GENERICHASH_KEYBYTES);
		$message_kp = sodium_crypto_box_seed_keypair($message_seed);
		$message_pk = sodium_crypto_box_publickey($message_kp);
		$message_sk = sodium_crypto_box_secretkey($message_kp);

		$message_data = json_encode([
			'@version' => 'TEST',
			'inventory' => [],
			'product' => [],
		]);

		$req_head = [
			'content-type' => 'application/json',
		];

		$message_path = Sodium::b64encode($message_pk);

		$req_path = sprintf('%s/%s', $message_path, $message_file);
		$res = $this->_curl_post($req_path, $req_head, $req_body);
		$this->assertEquals(400, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);
		$this->assertObjectHasProperty('note', $obj->meta);
		$this->assertEquals('Invalid Request [PCB-024]', $obj->meta->note);

	}

	/**
	 * @test
	 * Write to Unknown Profile a Message named $ULID.json
	 */
	function unknown_target_profile()
	{
		$source_profile_pk = $_ENV['OPENTHC_TEST_LICENSE_B_PK'];
		$source_profile_sk = $_ENV['OPENTHC_TEST_LICENSE_B_SK'];

		$target_profile_seed = 'PROFILE_404';
		$target_profile_seed = sodium_crypto_generichash($target_profile_seed, '', SODIUM_CRYPTO_GENERICHASH_KEYBYTES);
		$target_profile_kp = sodium_crypto_box_seed_keypair($target_profile_seed);
		$target_profile_pk = sodium_crypto_box_publickey($target_profile_kp);
		$target_profile_sk = sodium_crypto_box_secretkey($target_profile_kp);

		$req_path = sprintf('%s/%s.json', Sodium::b64encode($target_profile_pk), _ulid());

		$req_auth = $this->create_req_auth([
			'message' => $message_auth,
		]);

		$req_head = [
			'authorization' => sprintf('OpenTHC %s.%s', Sodium::b64encode($target_profile_pk), $req_auth),
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
		$this->assertEquals(401, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		// var_dump($obj);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);
		$this->assertObjectHasProperty('note', $obj->meta);
		$this->assertEquals('Invalid Request [PCB-046]', $obj->meta->note);
		// $this->assertEquals('Invalid Target Profile [PCB-071]', $obj->meta->note);

		// Write A Second Time, Should Still Fail
		$res = $this->_curl_post($req_path, $req_head, $req_body);
		$this->assertEquals(401, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		// var_dump($obj);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);

	}

	/**
	 * @test
	 * Write to Known Profile from and Unknown Source
	 */
	function unknown_source_profile()
	{
		$source_profile_seed = 'PROFILE_404';
		$source_profile_seed = sodium_crypto_generichash($source_profile_seed, '', SODIUM_CRYPTO_GENERICHASH_KEYBYTES);
		$source_profile_kp = sodium_crypto_box_seed_keypair($source_profile_seed);
		$source_profile_pk = sodium_crypto_box_publickey($source_profile_kp);
		$source_profile_sk = sodium_crypto_box_secretkey($source_profile_kp);

		$req_path = sprintf('%s/%s.json', $_ENV['OPENTHC_TEST_LICENSE_B_PK'], _ulid());

		// $req_auth = $this->create_req_auth();
		$req_auth = [
			'service' => _ulid(),
			'contact' => _ulid(),
			'company' => _ulid(),
			'license' => _ulid(),
		];
		$req_auth = json_encode($req_auth);
		$req_auth = Sodium::encrypt($req_auth, $source_profile_sk, $this->_service_pk_bin);
		$req_auth = Sodium::b64encode($req_auth);

		$req_head = [
			'authorization' => sprintf('OpenTHC %s.%s', Sodium::b64encode($source_profile_pk), $req_auth),
			'content-type' => 'application/json',
		];

		$req_body = json_encode([
			'@context' => 'http://openthc.org/api/v2017',
			'inventory' => [],
			'product' => [],
			'variety' => [],
			'lab_result' => [],
		]);

		// Write to B-Public-Key a message named RANDOM_ULID
		$res = $this->_curl_post($req_path, $req_head, $req_body);
		$this->assertEquals(201, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		// var_dump($obj);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);
		$this->assertObjectHasProperty('note', $obj->meta);
		// $this->assertEquals('Invalid Source Profile [PCB-071]', $obj->meta->note);

		// Write A Second Time, Should Still Fail
		// $res = $this->_curl_post($req_path, $req_head, $req_body);
		// $this->assertEquals(401, $res['code']);
		// $this->assertEquals('application/json', $res['type']);

		// $obj = json_decode($res['body']);
		// // var_dump($obj);
		// $this->assertIsObject($obj);
		// $this->assertObjectHasProperty('data', $obj);
		// $this->assertObjectHasProperty('meta', $obj);

	}

}
