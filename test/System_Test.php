<?php
/**
 * Test System
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Test;

class System_Test extends Base
{
	/**
	 * @test
	 */
	function has_config()
	{
		$chk = \OpenTHC\Config::get('openthc/pub/origin');
		$this->assertNotEmpty($chk);

		$chk = \OpenTHC\Config::get('openthc/pub/public');
		$this->assertNotEmpty($chk);

		$chk = \OpenTHC\Config::get('openthc/pub/secret');
		$this->assertNotEmpty($chk);

		$this->assertNotEmpty($this->_service_pk_b64);
		$this->assertNotEmpty($this->_service_pk_bin);
		$this->assertNotEmpty($this->_service_sk_b64);
		$this->assertNotEmpty($this->_service_sk_bin);

	}

	/**
	 * @test
	 */
	function has_system_public()
	{
		// Has the Registered Endpoint
		$url = sprintf('%s/pk', $this->_api_base);
		$req = _curl_init($url);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(200, $inf['http_code']);
		$this->assertEquals('text/plain; charset=utf-8', $inf['content_type']);
		$this->assertEquals(43, strlen($res));
		$pk0b64 = $res;

		// Has the Actual Endpoint
		$url = sprintf('%s/%s', $this->_api_base, $pk0b64);
		$req = _curl_init($url);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(200, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);
		$res = json_decode($res, true);
		$pk1b64 = $res['data']['id'];

		// Has a pointer to a Public Key?
		$this->assertEquals($pk0b64, $pk1b64);
		// $this->assertEquals('/pk', $res['data']['link']);

	}

}
