<?php
/**
 * Test Profile Create
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Test\Profile;

use OpenTHC\Sodium;

class Update_Test extends \OpenTHC\Pub\Test\Base
{
	/**
	 * @test
	 */
	function update_profile_as_self()
	{
		$req_path = $_ENV['OPENTHC_TEST_LICENSE_A_PK'];

		$profile_auth = $_ENV['OPENTHC_TEST_LICENSE_A_PK'];
		$profile_auth = Sodium::encrypt($profile_auth, $_ENV['OPENTHC_TEST_LICENSE_A_SK'], $this->_service_pk_bin);
		$profile_auth = Sodium::b64encode($profile_auth);

		$req_auth = json_encode([
			// 'service' => _ulid(), // This is the Client, which is indicated in the PK part of our crypted message
			'contact' => _ulid(),
			'company' => _ulid(),
			'license' => _ulid(),
			'profile' => $profile_auth,
		]);
		$req_auth = Sodium::encrypt($req_auth, $this->_api_client_sk, $this->_service_pk_bin);
		$req_auth = Sodium::b64encode($req_auth);

		$req_body = json_encode([
			'contact' => [],
			'name' => sprintf('TEST Profile A UPDATE'),
		]);

		// Create
		$req_head = [
			'authorization' => sprintf('OpenTHC %s.%s', $this->_api_client_pk, $req_auth),
			'content-type' => 'application/json',
		];

		$res = $this->_curl_put($req_path, $req_head, $req_body);
		$this->assertEquals(200, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);
		$this->assertObjectHasProperty('note', $obj->meta);
		$this->assertEquals('Profile Updated', $obj->meta->note);


	}

	/**
	 *
	 * @test
	 */
	function update_profile_that_does_not_exist()
	{
		$kp = sodium_crypto_box_keypair();
		$pk = Sodium::b64encode(sodium_crypto_box_publickey($kp));
		$sk = Sodium::b64encode(sodium_crypto_box_secretkey($kp));

		$profile_auth = $pk;
		$profile_auth = Sodium::encrypt($profile_auth, $sk, $this->_service_pk_bin);
		$profile_auth = Sodium::b64encode($profile_auth);

		$req_auth = json_encode([
			// 'service' => _ulid(), // This is the Client, which is indicated in the PK part of our crypted message
			'contact' => _ulid(),
			'company' => _ulid(),
			'license' => _ulid(),
			'profile' => $profile_auth,
		]);
		$req_auth = Sodium::encrypt($req_auth, $this->_api_client_sk, $this->_service_pk_bin);
		$req_auth = Sodium::b64encode($req_auth);

		$req_path = $pk;

		$req_body = json_encode([
			'name' => sprintf('TEST Profile Random UPDATE'),
		]);

		// Create
		$req_head = [
			'authorization' => sprintf('OpenTHC %s.%s', $this->_api_client_pk, $req_auth),
			'content-type' => 'application/json',
		];


		$res = $this->_curl_post($req_path, $req_head, $req_body);
		$this->assertEquals(201, $res['code']);
		$this->assertEquals('application/json', $res['type']);
		$this->assertNotEmpty($res['body']);

		$obj = json_decode($res['body']);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);
		$this->assertObjectHasProperty('note', $obj->meta);
		$this->assertEquals('Profile Created', $obj->meta->note);

	}


	/**
	 * A Profile that Doesn't Exist
	 * An Encryption that is valid but not to the right person
	 *
	 * @test
	 */
	function bad_encrypt_to_null_profile()
	{
		$kp = sodium_crypto_box_keypair();
		$pk = sodium_crypto_box_publickey($kp);
		$sk = sodium_crypto_box_secretkey($kp);

		// This Path Isn't Real
		$req_path = Sodium::b64encode($pk);
		$req_body = json_encode([
			'name' => 'LICENSE TEST',
		]);

		// This is the Wrong Encryption (wrong target_pk)
		$profile_auth = json_encode([
			'service' => '',
			'contact' => '',
			'company' => '',
			'license' => '',
			'profile' => Sodium::b64encode($pk)
		]);
		$profile_auth = Sodium::encrypt($profile_auth, $sk, $pk);
		$profile_auth = Sodium::b64encode($profile_auth);
		$req_head = [
			'authorization' => sprintf('OpenTHC %s.%s', $this->_api_client_pk, $profile_auth)
		];

		$res = $this->_curl_post($req_path, $req_head, $req_body);
		$this->assertEquals(403, $res['code']);
		$this->assertEquals('application/json', $res['type']);
		$this->assertNotEmpty($res['body']);

		$obj = json_decode($res['body']);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);
		$this->assertObjectHasProperty('note', $obj->meta);
		$this->assertEquals('Invalid Request [PCB-046]', $obj->meta->note);

	}


}
