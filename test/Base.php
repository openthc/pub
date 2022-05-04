<?php
/**
 * Base Test Case
 *
 * SPDX-License-Identifier: MIT
 */

namespace Test;

class Base extends \PHPUnit\Framework\TestCase
{
	protected $_pid = null;
	protected $_api_base = '';

	/**
	 *
	 */
	function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->_pid = getmypid();
		$this->_api_base = rtrim(\OpenTHC\Config::get('app/base'), '/');
	}

	/**
	 * Get the Firest Message for a PK
	 */
	function get_message_zero($pk) : array
	{
		$url = sprintf('%s/%s', $this->_api_base, enb64($pk));
		$req = _curl_init($url);
		// curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'GET');
		// curl_setopt($req, CURLOPT_POSTFIELDS, $req_body);
		// curl_setopt($req, CURLOPT_HTTPHEADER, [
		// 	sprintf('content-length: %d', strlen($req_body)),
		// 	'content-type: text/plain',
		// ]);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(200, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);
		$res = json_decode($res, true);
		$this->assertNotEmpty($res['data']);
		$this->assertNotEmpty($res['data']['incoming_message_list']);
		$this->assertGreaterThan(1, $res['data']['incoming_message_list']);

		$msg = $res['data']['incoming_message_list'][0];

		$url = sprintf('%s/%s/%s', $this->_api_base, enb64($pk), $msg['id']);
		$req = _curl_init($url);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		$this->assertEquals(200, $inf['http_code']);
		$this->assertEquals('application/json', $inf['content_type']);
		$res = json_decode($res, true);
		$this->assertNotEmpty($res['data']);
		$this->assertNotEmpty($res['data']['id']);
		$this->assertNotEmpty($res['data']['nonce']);
		$this->assertNotEmpty($res['data']['crypt']);

		return $res;

	}

}
