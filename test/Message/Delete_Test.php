<?php
/**
 * Test Message Delete
 *
 * SPDX-License-Identifier: MIT
 */

namespace OpenTHC\Pub\Test\Message;

use OpenTHC\Sodium;

class Delete_Test extends \OpenTHC\Pub\Test\Base
{
	/**
	 * @test
	 */
	function create_message() : string
	{
		$seed = sodium_crypto_generichash('TEST MESSAGE TO DELETE', '', SODIUM_CRYPTO_GENERICHASH_KEYBYTES);
		$message_kp = sodium_crypto_box_seed_keypair($seed);
		$message_pk = sodium_crypto_box_publickey($message_kp);
		$message_sk = sodium_crypto_box_secretkey($message_kp);

		$msg['name'] = sprintf('%s.txt', _ulid());
		$msg['path'] = Sodium::b64encode($message_pk);

		$message_auth = Sodium::b64encode($message_pk);
		$message_auth = Sodium::encrypt($message_auth, $message_sk, $this->_service_pk_bin);
		$message_auth = Sodium::b64encode($message_auth);
		$req_auth = $this->create_req_auth([
			'message' => $message_auth
		]);

		$req_path = sprintf('%s/%s', $msg['path'], $msg['name']);

		$req_body = 'TEST MESSAGE';

		$req_head = [
			'authorization' => sprintf('OpenTHC %s.%s', $this->_api_client_pk, $req_auth),
			'content-type' => 'text/plain',
		];


		$res = $this->_curl_post($req_path, $req_head, $req_body);
		$this->assertEquals(201, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);
		$this->assertEquals($req_path, $obj->data);

		return $obj->data;

	}

	/**
	 * Does delete work?
	 *
	 * @test
	 * @depends create_message
	 */
	function missing_auth_box(string $msg_path) : string
	{
		$res = $this->_curl_get($msg_path);
		$this->assertEquals(200, $res['code']);
		$this->assertEquals('text/plain;charset=UTF-8', $res['type']);

		$res = $this->_curl_delete($msg_path);
		$this->assertEquals(400, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);

		return $msg_path;
	}

	/**
	 * Does delete give a 400 if your argument parameter is bad?
	 *
	 * @test
	 * @depends missing_auth_box
	 */
	function missing_message_auth_box(string $msg_path) : string
	{
		$res = $this->_curl_get($msg_path);
		$this->assertEquals(200, $res['code']);
		$this->assertEquals('text/plain;charset=UTF-8', $res['type']);

		$req_auth = $this->create_req_auth();
		$req_head = [
			'authorization' => sprintf('OpenTHC %s.%s', $this->_api_client_pk, $req_auth),
		];

		$res = $this->_curl_delete($msg_path, $req_head);
		$this->assertEquals(400, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);
		$this->assertObjectHasProperty('note', $obj->meta);
		$this->assertEquals('Invalid Request [PCM-177]', $obj->meta->note);

		return $msg_path;

	}

	/**
	 * Should 403 if you use a key that doesn't match
	 *
	 * @test
	 * @depends missing_message_auth_box
	 */
	function incorrect_message_auth_box(string $msg_path) : string
	{
		$res = $this->_curl_get($msg_path);
		$this->assertEquals(200, $res['code']);
		$this->assertEquals('text/plain;charset=UTF-8', $res['type']);

		// Message Authentication
		$message_kp = sodium_crypto_box_keypair();
		$message_pk = sodium_crypto_box_publickey($message_kp);
		$message_sk = sodium_crypto_box_secretkey($message_kp);

		$message_auth = Sodium::b64encode($message_pk);
		$message_auth = Sodium::encrypt($message_auth, $message_sk, $this->_service_pk_bin);
		$message_auth = Sodium::b64encode($message_auth);

		$req_auth = $this->create_req_auth([
			'message' => $message_auth
		]);
		$req_head = [
			'authorization' => sprintf('OpenTHC %s.%s', $this->_api_client_pk, $req_auth),
		];

		$res = $this->_curl_delete($msg_path, $req_head);
		$this->assertEquals(403, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);

		return $msg_path;

	}

	/**
	 * Return a 404 if choosing someone else's message or a non-existant one
	 *
	 * @test
	 * @depends incorrect_message_auth_box
	 */
	function delete(string $msg_path) : void
	{
		$seed = sodium_crypto_generichash('TEST MESSAGE TO DELETE', '', SODIUM_CRYPTO_GENERICHASH_KEYBYTES);
		$message_kp = sodium_crypto_box_seed_keypair($seed);
		$message_pk = sodium_crypto_box_publickey($message_kp);
		$message_sk = sodium_crypto_box_secretkey($message_kp);

		$message_auth = Sodium::b64encode($message_pk);
		$message_auth = Sodium::encrypt($message_auth, $message_sk, $this->_service_pk_bin);
		$message_auth = Sodium::b64encode($message_auth);
		$req_auth = $this->create_req_auth([
			'message' => $message_auth
		]);

		$res = $this->_curl_get($msg_path, $req_auth);
		$this->assertEquals(200, $res['code']);
		$this->assertEquals('text/plain;charset=UTF-8', $res['type']);

		$req_auth = $this->create_req_auth([
			'message' => $message_auth
		]);
		$req_head = [
			'authorization' => sprintf('OpenTHC %s.%s', $this->_api_client_pk, $req_auth),
		];

		$res = $this->_curl_delete($msg_path, $req_head);
		$this->assertEquals(200, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);


		$res = $this->_curl_get($msg_path);
		$this->assertEquals(404, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);
		$this->assertEquals('Message Not Found [PCM-130]', $obj->meta->note);

	}

	/**
	 * @twest
	 */
	function not_found() : void
	{
		$message_kp = sodium_crypto_box_keypair();
		$message_pk = sodium_crypto_box_publickey($message_kp);
		$message_sk = sodium_crypto_box_secretkey($message_kp);

		$req_path = sprintf('%s/%s.txt', Sodium::b64encode($message_pk), _ulid());

		$res = $this->_curl_get($req_path);
		$this->assertEquals(404, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		// Message Authentication
		$message_auth = Sodium::b64encode($message_pk);
		$message_auth = Sodium::encrypt($message_auth, $message_sk, $this->_service_pk_bin);
		$message_auth = Sodium::b64encode($message_auth);

		$req_auth = $this->create_req_auth([
			// 'message' => $message_auth
		]);
		$req_head = [
			// 'authorization' => sprintf('OpenTHC %s.%s', $this->_api_client_pk, $req_auth),
		];

		$res = $this->_curl_delete($req_path, $req_head);
		$this->assertEquals(404, $res['code']);
		$this->assertEquals('application/json', $res['type']);

		$obj = json_decode($res['body']);
		$this->assertIsObject($obj);
		$this->assertObjectHasProperty('data', $obj);
		$this->assertObjectHasProperty('meta', $obj);

	}

}
