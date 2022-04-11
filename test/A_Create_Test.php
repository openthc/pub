<?php
/**
 * Test Account Create
 *
 * SPDX-License-Identifier: MIT
 */

namespace Test;

class A_Create_Test extends \Test\Base
{
	/**
	 * @test
	 */
	function has_config()
	{
		$chk = \OpenTHC\Config::get('app/base');
		$this->assertNotEmpty($chk);

		$chk = \OpenTHC\Config::get('pub/public');
		$this->assertNotEmpty($chk);

		$chk = \OpenTHC\Config::get('pub/secret');
		$this->assertNotEmpty($chk);

	}

	/**
	 * @test
	 */
	function has_system_public()
	{
		// Has the Registered Endpoint
		$req = _curl_init(sprintf('%s/pk', $this->_api_base));
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

		$this->assertEquals($pk0b64, $pk1b64);
		$this->assertEquals('/pk', $res['data']['link']);

	}

	/**
	 * @test
	 */
	function create_account_a()
	{
		$raw = sodium_crypto_box_keypair();
		$pk0 = sodium_crypto_box_publickey($raw);
		$sk0 = sodium_crypto_box_secretkey($raw);
		$pk0b64 = \enb64($pk0);

		$url = sprintf('%s/%s', $this->_api_base, $pk0b64);
		$req = _curl_init($url);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);
		// var_dump($inf);
		$this->assertEquals(404, $inf['http_code']);
		$this->assertEquals('text/plain; charset=utf-8', $inf['content_type']);

		// Empty POST to Create
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'POST');
		$res = curl_exec($req);
		$inf = curl_getinfo($req);
		$this->assertEquals(201, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);
		$res = json_decode($res, true);
		$this->assertEquals($pk0b64, $res['data']);

		$dbc = _dbc();
		$chk = $dbc->fetchOne('SELECT id FROM account WHERE id = :pk', [ ':pk' => $pk0b64 ]);
		$this->assertEquals($pk0b64, $chk);

		$_ENV['a_pk'] = $pk0;
		$_ENV['a_sk'] = $sk0;

	}

	/**
	 * @test
	 */
	function create_account_b()
	{
		$raw = sodium_crypto_box_keypair();
		$pk0 = sodium_crypto_box_publickey($raw);
		$sk0 = sodium_crypto_box_secretkey($raw);
		$pk0b64 = \enb64($pk0);

		$url = sprintf('%s/%s', $this->_api_base, $pk0b64);
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
		$this->assertEquals($pk0b64, $res['data']);

		$dbc = _dbc();
		$chk = $dbc->fetchOne('SELECT id FROM account WHERE id = :pk', [ ':pk' => $pk0b64 ]);
		$this->assertEquals($pk0b64, $chk);

		$_ENV['b_pk'] = $pk0;
		$_ENV['b_sk'] = $sk0;


	}

}
