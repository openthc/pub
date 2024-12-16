<?php
/**
 * Base Test Case
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Test;

use OpenTHC\Sodium;

class Base extends \PHPUnit\Framework\TestCase
{
	protected $_pid = null;
	protected $_api_base = '';

	protected $_api_client_pk = '';
	protected $_api_client_sk = '';

	protected $_service_pk_b64 = '';
	protected $_service_pk_bin = '';
	protected $_service_sk_b64 = '';
	protected $_service_sk_bin = '';

	/**
	 *
	 */
	function __construct($name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->_pid = getmypid();
		$this->_api_base = \OpenTHC\Config::get('openthc/pub/origin');

		$this->_api_client_pk = $_ENV['OPENTHC_TEST_API_CLIENT_PK'];
		$this->_api_client_sk = $_ENV['OPENTHC_TEST_API_CLIENT_SK'];

		$this->_service_pk_b64 = \OpenTHC\Config::get('openthc/pub/public');
		$this->_service_pk_bin = Sodium::b64decode($this->_service_pk_b64);
		$this->_service_sk_b64 = \OpenTHC\Config::get('openthc/pub/secret');
		$this->_service_sk_bin = Sodium::b64decode($this->_service_sk_b64);

	}

	function setup() : void
	{
		// Do Stuff?
	}

	function _curl_delete(string $path, $head=[])
	{
		$head = $this->_curl_header_fix($head);

		$url = sprintf('%s/%s', $this->_api_base, $path);
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($req, CURLOPT_HTTPHEADER, $head);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		return [
			'body' => $res,
			'code' => $inf['http_code'],
			'type' => $inf['content_type'],
		];

	}
	function _curl_header_fix($head0=[])
	{
		$head1 = array_merge([
			'accept' => 'application/json',
			'openthc-test' => '1',
		], $head0);

		$head2 = [];
		foreach ($head1 as $k => $v) {
			$head2[] = sprintf('%s: %s', $k, $v);
		}

		return $head2;

	}

	function _curl_get(string $path, $head=[])
	{
		$head = $this->_curl_header_fix($head);

		$url = sprintf('%s/%s', $this->_api_base, $path);
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_HTTPHEADER, $head);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		return [
			'body' => $res,
			'code' => $inf['http_code'],
			'type' => $inf['content_type'],
		];

	}

	function _curl_post(string $path, $head=[], $body)
	{
		$head = $this->_curl_header_fix(array_merge([
			'content-length' => strlen($body),
			'content-type' => 'text/plain',
		], $head));

		$url = sprintf('%s/%s', $this->_api_base, $path);
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($req, CURLOPT_POSTFIELDS, $body);
		curl_setopt($req, CURLOPT_HTTPHEADER, $head);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		return [
			'body' => $res,
			'code' => $inf['http_code'],
			'type' => $inf['content_type'],
		];

	}

	function _curl_put(string $path, $head=[], $body)
	{
		$head = $this->_curl_header_fix(array_merge([
			'content-length' => strlen($body),
			'content-type' => 'text/plain',
		], $head));

		$url = sprintf('%s/%s', $this->_api_base, $path);
		$req = _curl_init($url);
		curl_setopt($req, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($req, CURLOPT_POSTFIELDS, $body);
		curl_setopt($req, CURLOPT_HTTPHEADER, $head);
		$res = curl_exec($req);
		$inf = curl_getinfo($req);

		return [
			'body' => $res,
			'code' => $inf['http_code'],
			'type' => $inf['content_type'],
		];
	}

	/**
	 *
	 */
	function create_req_auth($add=[])
	{
		$req_auth = array_merge([
			'service' => _ulid(),
			'contact' => _ulid(),
			'company' => _ulid(),
			'license' => _ulid(),
			// 'message' => Sodium::b64encode($message_auth),
		], $add);

		$req_auth = json_encode($req_auth);
		$req_auth = Sodium::encrypt($req_auth, $this->_api_client_sk, $this->_service_pk_bin);
		$req_auth = Sodium::b64encode($req_auth);

		return $req_auth;
	}


	/**
	 * Get the Firest Message for a PK
	 */
	function get_message_zero(string $sk, string $pk) : array
	{
		$target_pk = \OpenTHC\Config::get('openthc/pub/public');

		$res = $this->_curl_get($pk, [
			'authorization' => sprintf('OpenTHC %s', \OpenTHC\Sodium::b64encode(\OpenTHC\Sodium::encrypt($pk, $sk, $target_pk ))),
		]);

		$this->assertEquals(200, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$res = json_decode($res['body'], true);
		$this->assertNotEmpty($res['data']);
		$this->assertNotEmpty($res['data']['file_list']);
		$this->assertGreaterThan(1, $res['data']['file_list']);

		$msg = $res['data']['file_list'][0];

		// $url = sprintf('%s/%s', $this->_api_base, $msg['id']);
		// $res = $this->_curl_get($msg['id']);
		$res = $this->_curl_get(sprintf('%s/%s', $pk, $msg['name']));
		$this->assertEquals(200, $res['code']);
		// $this->assertEquals('text/plain', $inf['content_type']);
		// $res = json_decode($res, true);
		// $this->assertNotEmpty($res['data']);
		// $this->assertNotEmpty($res['data']['id']);
		// $this->assertNotEmpty($res['data']['nonce']);
		// $this->assertNotEmpty($res['data']['crypt']);

		return [
			'id' => $msg['id'],
			'data' => $res['body'],
			'name' => $msg['name'],
			'type' => $msg['type'],
		];

	}

}
