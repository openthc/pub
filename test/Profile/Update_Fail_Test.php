<?php
/**
 * Test Profile Create
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Test\Profile;

use OpenTHC\Sodium;

class Update_Fail_Test extends \OpenTHC\Pub\Test\Base
{
	/**
	 * @test
	 */
	function update_profile_as_null()
	{
		$req_path = OPENTHC_TEST_LICENSE_A_PK;
		$req_body = json_encode([
			'name' => sprintf('LICENSE A UPDATE %s', _ulid())
		]);
		$req_head = [];

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

}
