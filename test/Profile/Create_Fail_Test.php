<?php
/**
 * Test Profile Create
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Test\Profile;

use OpenTHC\Sodium;

class Create_Fail_Test extends \OpenTHC\Pub\Test\Base
{
	/**
	 * @test
	 */
	function create_profile_fail_auth_none()
	{
		$kp = sodium_crypto_box_keypair();
		$pk = sodium_crypto_box_publickey($kp);
		$pk = sodium_crypto_box_secretkey($kp);

		$req_path = Sodium::b64encode($pk);

		$req_body = json_encode([
			'name' => 'TEST Profile No Auth',
		]);

		// Create
		$req_head = [
			'content-type' => 'application/json',
		];

		$res = $this->_curl_post($req_path, $req_head, $req_body);
		$this->assertEquals(400, $res['code']);
		$this->assertEquals('application/json', $res['type']);

	}

	/**
	 * @test
	 */
	function create_profile_fail_no_auth_profile()
	{
		$kp = sodium_crypto_box_keypair();
		$pk = sodium_crypto_box_publickey($kp);
		$pk = sodium_crypto_box_secretkey($kp);

		$req_auth = json_encode([
			'service' => _ulid(),
			'contact' => _ulid(),
			'company' => _ulid(),
			'license' => _ulid(),
			'profile' => '', // Missing on Purpose
		]);
		$req_auth = Sodium::encrypt($req_auth, $this->_api_client_sk, $this->_service_pk_bin);
		$req_auth = Sodium::b64encode($req_auth);

		$req_path = Sodium::b64encode($pk);

		$req_body = json_encode([
			'name' => 'TEST Profile No Auth',
		]);

		// Create
		$req_head = [
			'authorization' => sprintf('OpenTHC %s.%s', $this->_api_client_pk, $req_auth),
			'content-type' => 'application/json',
		];

		$res = $this->_curl_post($req_path, $req_head, $req_body);
		$this->assertEquals(400, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);
		$this->assertObjectHasProperty('note', $obj->meta);
		$this->assertEquals('Invalid Request [PCP-132]', $obj->meta->note);

	}

	/**
	 * @test
	 */
	function create_profile_fail_auth_invalid()
	{
		$kp1 = sodium_crypto_box_keypair();
		$pk1 = sodium_crypto_box_publickey($kp1);
		$sk1 = sodium_crypto_box_secretkey($kp1);

		$kp2 = sodium_crypto_box_keypair();
		$pk2 = sodium_crypto_box_publickey($kp2);
		$sk2 = sodium_crypto_box_secretkey($kp2);

		$profile_auth = Sodium::b64encode($pk1);
		$profile_auth = Sodium::encrypt($pk1, $sk1, $this->_service_pk_bin);

		$req_auth = json_encode([
			'service' => _ulid(),
			'contact' => _ulid(),
			'company' => _ulid(),
			'license' => _ulid(),
			'profile' => Sodium::b64encode($profile_auth),
		]);
		$req_auth = Sodium::encrypt($req_auth, $this->_api_client_sk, $this->_service_pk_bin);
		$req_auth = Sodium::b64encode($req_auth);

		$req_path = Sodium::b64encode($pk2);

		$req_body = json_encode([
			'name' => 'TEST Profile 2 with Profile 1 Key',
		]);

		// Create
		$req_head = [
			'authorization' => sprintf('OpenTHC %s.%s', $this->_api_client_pk, $req_auth),
			'content-type' => 'application/json',
		];

		$res = $this->_curl_post($req_path, $req_head, $req_body);
		$this->assertEquals(403, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$res = json_decode($res['body']);
		$this->assertIsObject($res);
		$this->assertObjectHasProperty('data', $res);
		$this->assertObjectHasProperty('meta', $res);
		$this->assertObjectHasProperty('note', $res->meta);
		$this->assertEquals('Invalid Profile [PCP-142]', $res->meta->note);

	}

}
