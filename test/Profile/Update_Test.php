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
		$req_path = OPENTHC_TEST_LICENSE_A_PK;
		$req_body = json_encode([
			'name' => sprintf('LICENSE A UPDATE %s', _ulid())
		]);

		$profile_auth = Sodium::encrypt(OPENTHC_TEST_LICENSE_A_PK, OPENTHC_TEST_LICENSE_A_SK, $this->_service_pk_bin);
		$profile_auth = Sodium::b64encode($profile_auth);
		$req_head = [
			'openthc-profile' => $profile_auth
		];

		$res = $this->_curl_put($req_path, $req_head, $req_body);
		$this->assertEquals(200, $res['code']);

	}

	/**
	 * @test
	 */
	function update_profile_as_null()
	{
		$req_path = OPENTHC_TEST_LICENSE_A_PK;
		$req_body = json_encode([
			'name' => sprintf('LICENSE A UPDATE %s', _ulid())
		]);
		$req_head = [
			'openthc-profile' => '',
		];

		$res = $this->_curl_post($req_path, $req_head, $req_body);
		var_dump($res);
		$this->assertEquals(403, $res['code']);

	}

	/**
	 * @test
	 */
	function update_profile_that_does_not_exist()
	{
		$kp = sodium_crypto_box_keypair();
		$pk_bin = sodium_crypto_box_publickey($kp);
		$pk_b64 = Sodium::b64encode($pk_bin);
		$sk_bin = sodium_crypto_box_secretkey($kp);

		$req_path = $pk_b64;
		$req_body = json_encode([
			'name' => 'LICENSE TEST',
		]);

		// $pk = OPENTHC_TEST_LICENSE_A_PK;
		// $sk = OPENTHC_TEST_LICENSE_A_SK;
		$profile_auth = Sodium::encrypt($pk_b64, $sk_bin, $this->_service_pk_bin);
		$profile_auth = Sodium::b64encode($profile_auth);
		$req_head = [
			'openthc-profile' => $profile_auth
		];

		$res = $this->_curl_post($req_path, $req_head, $req_body);
		$this->assertEquals(404, $res['code']);

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

		$req_path = OPENTHC_TEST_LICENSE_A_PK;
		$req_body = json_encode([
			'name' => 'LICENSE TEST',
		]);

		$profile_auth = Sodium::encrypt($pk, $sk, $pk);
		$profile_auth = Sodium::b64encode($profile_auth);
		$req_head = [
			'openthc-profile' => $profile_auth
		];

		$res = $this->_curl_post($req_path, $req_head, $req_body);
		$this->assertEquals(404, $res['code']);

	}


}
