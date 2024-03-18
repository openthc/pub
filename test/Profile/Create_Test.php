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
	 */
	function create_account_a()
	{
		$res = $this->_curl_get(OPENTHC_TEST_LICENSE_A_PK);
		$this->assertEquals(404, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		// Ask again as browser, for HTML
		$res = $this->_curl_get(OPENTHC_TEST_LICENSE_A_PK, [
			'accept' => 'text/html'
		]);
		$this->assertEquals(404, $res['code']);
		$this->assertEquals('text/html; charset=utf-8', strtolower($res['type']));

		$profile_auth = Sodium::encrypt(OPENTHC_TEST_LICENSE_A_PK, OPENTHC_TEST_LICENSE_A_SK, $this->_service_pk_bin);
		$profile_auth = Sodium::b64encode($profile_auth);

		// Empty POST to Create
		$req_head = [
			'openthc-profile' => $profile_auth,
			'content-type' => 'application/json', // ' => OPENTHC_TEST_LICENSE_A_PK,
			// 'name' => 'TEST Create Account',
		];
		$req_body = json_encode([
			'contact' => [],
			'name' => 'TEST Create Account',
		]);
		// $req_body = \OpenTHC\Sodium::encrypt($req_body, OPENTHC_TEST_LICENSE_A_SK, OPENTHC_PUB_PK);
		$res = $this->_curl_post(OPENTHC_TEST_LICENSE_A_PK, $req_head, $req_body);
		$this->assertEquals(201, $res['code']);
		$this->assertEquals('application/json', $res['type']);
		$res = json_decode($res['body'], true);
		$this->assertEquals(OPENTHC_TEST_LICENSE_A_PK, $res['data']);

		$dbc = _dbc();
		$chk = $dbc->fetchOne('SELECT id FROM profile WHERE id = :pk', [ ':pk' => OPENTHC_TEST_LICENSE_A_PK ]);
		$this->assertEquals(OPENTHC_TEST_LICENSE_A_PK, $chk);

	}

	/**
	 * @test
	 */
	function create_account_b()
	{
		$url = sprintf('%s/%s', $this->_api_base, OPENTHC_TEST_LICENSE_B_PK);
		$req = _curl_init($url);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);
		$this->assertEquals(404, $inf['http_code']);

		// Empty POST to Create
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'POST');
		$res = curl_exec($req);
		$inf = curl_getinfo($req);
		$this->assertEquals(201, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);
		$res = json_decode($res, true);
		$this->assertEquals(OPENTHC_TEST_LICENSE_B_PK, $res['data']);

		$dbc = _dbc();
		$chk = $dbc->fetchOne('SELECT id FROM profile WHERE id = :pk', [ ':pk' => OPENTHC_TEST_LICENSE_B_PK ]);
		$this->assertEquals(OPENTHC_TEST_LICENSE_B_PK, $chk);

	}

	/**
	 * @test
	 */
	function create_account_c()
	{
		$url = sprintf('%s/%s', $this->_api_base, OPENTHC_TEST_LICENSE_C_PK);
		$req = _curl_init($url);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);
		$this->assertEquals(404, $inf['http_code']);

		// Empty POST to Create
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'POST');
		$res = curl_exec($req);
		$inf = curl_getinfo($req);
		$this->assertEquals(201, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);
		$res = json_decode($res, true);
		$this->assertEquals(OPENTHC_TEST_LICENSE_C_PK, $res['data']);

		$dbc = _dbc();
		$chk = $dbc->fetchOne('SELECT id FROM profile WHERE id = :pk', [ ':pk' => OPENTHC_TEST_LICENSE_C_PK ]);
		$this->assertEquals(OPENTHC_TEST_LICENSE_C_PK, $chk);

	}

}
