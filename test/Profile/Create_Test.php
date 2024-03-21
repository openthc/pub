<?php
/**
 * Test Profile Create
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Test\Profile;

use OpenTHC\Sodium;

class Create_Test extends \OpenTHC\Pub\Test\Base
{
	/**
	 *
	 */
	static function setupBeforeClass() : void
	{
		$dbc = _dbc();
		$dbc->query('DELETE FROM profile WHERE id = :pk', [ ':pk' => OPENTHC_TEST_LICENSE_A_PK ]);
		$dbc->query('DELETE FROM profile WHERE id = :pk', [ ':pk' => OPENTHC_TEST_LICENSE_B_PK ]);
		$dbc->query('DELETE FROM profile WHERE id = :pk', [ ':pk' => OPENTHC_TEST_LICENSE_C_PK ]);
	}

	/**
	 * @test
	 * @group account
	 */
	function account_does_not_exist()
	{
		$rand_kp = sodium_crypto_box_keypair();
		$rand_pk_bin = sodium_crypto_box_publickey($rand_kp);

		$profile_list = [
			OPENTHC_TEST_LICENSE_A_PK,
			OPENTHC_TEST_LICENSE_B_PK,
			OPENTHC_TEST_LICENSE_C_PK,
			Sodium::b64encode($rand_pk_bin),
			_ulid(),
			'ANOTHER_INVALID_NAME',
			'and/a/really/invalid$name',
		];

		foreach ($profile_list as $p) {

			$res = $this->_curl_get($p);
			$this->assertEquals(404, $res['code']);
			$this->assertEquals('application/json', $res['type']);

			// Ask again as browser, for HTML
			$req_path = $p;
			$res = $this->_curl_get($req_path, [
				'accept' => 'text/html'
			]);
			$this->assertEquals(404, $res['code']);
			$this->assertEquals('text/html;charset=utf-8', strtolower($res['type']));

		}

	}

	/**
	 * @test
	 */
	function create_profile_abc()
	{
		$profile_list = [
			[
				'pk' => OPENTHC_TEST_LICENSE_A_PK,
				'sk' => OPENTHC_TEST_LICENSE_A_SK,
				'name' => 'LICENSE_A',
			],
			[
				'pk' => OPENTHC_TEST_LICENSE_B_PK,
				'sk' => OPENTHC_TEST_LICENSE_B_SK,
				'name' => 'LICENSE_B',
			],
			[
				'pk' => OPENTHC_TEST_LICENSE_C_PK,
				'sk' => OPENTHC_TEST_LICENSE_C_SK,
				'name' => 'LICENSE_C',
			],
		];

		foreach ($profile_list as $p) {

			$req_path = $p['pk'];

			$profile_data = json_encode([
				'contact' => [],
				'name' => sprintf('TEST Profile %s', $p['name']),
			]);

			$profile_auth = $p['pk'];;
			$profile_auth = Sodium::encrypt($p['pk'], $p['sk'], $this->_service_pk_bin);

			$req_auth = json_encode([
				'service' => _ulid(),
				'contact' => _ulid(),
				'company' => _ulid(),
				'license' => _ulid(),
				'profile' => Sodium::b64encode($profile_auth),
			]);
			$req_auth = Sodium::encrypt($req_auth, $this->_api_client_sk, $this->_service_pk_bin);
			$req_auth = Sodium::b64encode($req_auth);

			$req_body = $profile_data;

			// Create
			$req_head = [
				'authorization' => sprintf('OpenTHC %s.%s', $this->_api_client_pk, $req_auth),
				'content-type' => 'application/json',
			];

			$res = $this->_curl_post($req_path, $req_head, $req_body);
			$this->assertEquals(201, $res['code']);
			$this->assertEquals('application/json', $res['type']);
			$res = json_decode($res['body'], true);
			$this->assertEquals($p['pk'], $res['data']);

			$dbc = _dbc();
			$chk = $dbc->fetchOne('SELECT id FROM profile WHERE id = :pk', [ ':pk' => $p['pk'] ]);
			$this->assertEquals($p['pk'], $chk);

		}

	}

}
